<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\EmployeeLocationAssignment;
use App\Models\Admin\Employee;
use App\Models\Admin\Location;
use App\Models\Admin\Branch;

class EmployeeLocationAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(EmployeeLocationAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-location-assignment');
        CRUD::setEntityNameStrings('location assignment', 'location assignments');
    }

    public function index()
    {
        $assignments = EmployeeLocationAssignment::with(['employee.person', 'location', 'branch'])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $assignments->map(function ($assignment, $index) {
            $employeeName = $assignment->employee && $assignment->employee->person
                ? trim($assignment->employee->person->first_name . ' ' . $assignment->employee->person->last_name)
                : '-';

            return [
                'id'              => $assignment->id,
                'serial_no'       => $index + 1,
                'employee_code'   => $assignment->employee->code ?? $assignment->employee_code ?? '-',
                'employee_name'   => $employeeName,
                'location_name'   => $assignment->location->name ?? $assignment->location_code ?? '-',
                'branch_name'     => $assignment->branch->name ?? $assignment->branch_code ?? '-',
                'from_date'       => $assignment->from_date?->format('d/m/Y'),
                'to_date'         => $assignment->to_date?->format('d/m/Y') ?? 'Ongoing',
                'is_current'      => $assignment->is_current ? 'Yes' : 'No',
                'action' => '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . backpack_url("employee-location-assignment/{$assignment->id}/edit") . '"
                       class="btn btn-sm btn-primary py-1 px-2">Edit</a>
                </div>
                '
            ];
        });

        return view('admin.employee-location-assignment.list', [
            'title' => 'Employee Location Assignments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',       'headerName' => 'S.No'],
                    ['field' => 'employee_code',   'headerName' => 'Employee Code'],
                    ['field' => 'employee_name',   'headerName' => 'Employee Name'],
                    ['field' => 'location_name',   'headerName' => 'Location'],
                    ['field' => 'branch_name',     'headerName' => 'Branch'],
                    ['field' => 'from_date',       'headerName' => 'From Date'],
                    ['field' => 'to_date',         'headerName' => 'To Date'],
                    ['field' => 'is_current',      'headerName' => 'Current'],
                    ['field' => 'action',          'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        return view('admin.employee-location-assignment.create', [
            'title'     => 'Assign Location to Employee',
            'employees' => Employee::with('person')->orderBy('code')->get(),
            'locations' => Location::orderBy('name')->get(),
            'branches'  => Branch::orderBy('name')->get(),
        ]);
    }

    public function edit($id)
    {
        $assignment = EmployeeLocationAssignment::with(['employee.person', 'location', 'branch'])
            ->findOrFail($id);

        return view('admin.employee-location-assignment.edit', [
            'title'      => 'Edit Location Assignment',
            'assignment' => $assignment,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'  => 'required|exists:xlr8_admin_employee,id',
            'location_id'  => 'required|exists:xlr8_admin_location,id',
            'branch_id'    => 'nullable|exists:xlr8_admin_branch,id',
            'from_date'    => 'required|date',
            'to_date'      => 'nullable|date|after_or_equal:from_date',
            'is_current'   => 'boolean',
        ]);

        $employeeCode = Employee::findOrFail($validated['employee_id'])->code;
        $locationCode = Location::findOrFail($validated['location_id'])->code;
        $branchCode   = $validated['branch_id']
            ? Branch::findOrFail($validated['branch_id'])->code
            : null;

        $userId = backpack_user()->id ?? auth()->id();   // ← Correct User ID

        EmployeeLocationAssignment::create([
            'employee_code' => $employeeCode,
            'location_code' => $locationCode,
            'branch_code'   => $branchCode,
            'from_date'     => $validated['from_date'],
            'to_date'       => $validated['to_date'],
            'is_current'    => $validated['is_current'] ?? 1,
            'created_by'    => $userId,
        ]);

        \Alert::success('Location assignment created successfully!')->flash();
        return redirect(backpack_url('employee-location-assignment'));
    }

    public function update(Request $request, $id)
    {
        $assignment = EmployeeLocationAssignment::findOrFail($id);

        $validated = $request->validate([
            'location_id' => 'required|exists:xlr8_admin_location,id',
            'branch_id'   => 'nullable|exists:xlr8_admin_branch,id',
            'from_date'   => 'required|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'is_current'  => 'boolean',
        ]);

        $locationCode = Location::findOrFail($validated['location_id'])->code;

        // Fix for branch_code - never set to null if column is NOT NULL
        $branchCode = null;
        if ($validated['branch_id']) {
            $branchCode = Branch::findOrFail($validated['branch_id'])->code;
        } else {
            // If no branch selected, you can either:
            // Option A: Keep existing branch_code (recommended)
            $branchCode = $assignment->branch_code;

            // Option B: Set a default value if you have one (e.g. '')
            // $branchCode = '';
        }

        $userId = backpack_user()->id ?? auth()->id();

        $assignment->update([
            'location_code' => $locationCode,
            'branch_code'   => $branchCode,           // ← Now safe
            'from_date'     => $validated['from_date'],
            'to_date'       => $validated['to_date'],
            'is_current'    => $validated['is_current'] ?? $assignment->is_current,
            'updated_by'    => $userId,
        ]);

        \Alert::success('Location assignment updated successfully!')->flash();
        return redirect(backpack_url('employee-location-assignment'));
    }
}

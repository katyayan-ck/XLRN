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
                'serial_no'        => $index + 1,
                'employee_code'    => $assignment->employee_code ?? '-',
                'employee_name'    => $employeeName,
                'location_name'    => $assignment->location?->name ?? $assignment->location_code ?? '-',
                'branch_name'      => $assignment->branch?->name ?? $assignment->branch_code ?? '-',
                'assignment_type'  => ucfirst($assignment->assignment_type ?? 'explicit'),
                'is_primary_work'  => $assignment->is_primary_work ? 'Yes' : 'No',
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
                    ['field' => 'serial_no',        'headerName' => 'S.No'],
                    ['field' => 'employee_code',    'headerName' => 'Employee Code'],
                    ['field' => 'employee_name',    'headerName' => 'Employee Name'],
                    ['field' => 'location_name',    'headerName' => 'Location'],
                    ['field' => 'branch_name',      'headerName' => 'Branch'],
                    ['field' => 'assignment_type',  'headerName' => 'Assignment Type'],
                    ['field' => 'is_primary_work',  'headerName' => 'Primary Work'],
                    ['field' => 'action',           'headerName' => 'Actions']
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code'   => 'required|exists:xlr8_admin_employee,code',
            'location_code'   => 'required|exists:xlr8_admin_location,code',
            'branch_code'     => 'nullable|exists:xlr8_admin_branch,code',
            'assignment_type' => 'required|in:explicit,inherited,excluded',
            'is_primary_work' => 'boolean',
        ]);

        EmployeeLocationAssignment::create($validated);

        \Alert::success('Location assignment created successfully!')->flash();
        return redirect(backpack_url('employee-location-assignment'));
    }

    public function edit($id)
    {
        $assignment = EmployeeLocationAssignment::with(['employee.person', 'location', 'branch'])
            ->findOrFail($id);

        return view('admin.employee-location-assignment.edit', [
            'title'      => 'Edit Location Assignment',
            'assignment' => $assignment,
            'locations'  => Location::orderBy('name')->get(),
            'branches'   => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = EmployeeLocationAssignment::findOrFail($id);

        $validated = $request->validate([
            'location_code'   => 'required|exists:xlr8_admin_location,code',
            'branch_code'     => 'nullable|exists:xlr8_admin_branch,code',
            'assignment_type' => 'required|in:explicit,inherited,excluded',
            'is_primary_work' => 'boolean',
        ]);

        $assignment->update($validated);

        \Alert::success('Location assignment updated successfully!')->flash();
        return redirect(backpack_url('employee-location-assignment'));
    }
}

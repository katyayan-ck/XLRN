<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\EmployeeBranchAssignment;
use App\Models\Admin\Employee;
use App\Models\Admin\Branch;

class EmployeeBranchAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(EmployeeBranchAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-branch-assignment');
        CRUD::setEntityNameStrings('branch assignment', 'branch assignments');
    }

    // ================= LIST =================
    public function index()
    {
        $assignments = EmployeeBranchAssignment::with(['employee.person', 'branch'])
            ->select([
                'id',
                'employee_code',
                'branch_code',
                'from_date',
                'to_date',
                'is_current'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $assignments->map(function ($assignment, $index) {

            $employeeName = $assignment->employee && $assignment->employee->person
                ? trim($assignment->employee->person->first_name . ' ' . $assignment->employee->person->last_name)
                : '-';

            return [
                'id'            => $assignment->id,
                'serial_no'     => $index + 1,
                'employee_code' => $assignment->employee_code ?? '-',
                'employee_name' => $employeeName,
                'branch_name'   => $assignment->branch?->name ?? $assignment->branch_code ?? '-',
                'from_date'     => $assignment->from_date?->format('d/m/Y') ?? '-',
                'to_date'       => $assignment->to_date?->format('d/m/Y') ?? 'Ongoing',
                'is_current'    => $assignment->is_current ? 'Yes' : 'No',
                'action'        => '
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="' . backpack_url("employee-branch-assignment/{$assignment->id}/edit") . '"
                        class="btn btn-sm btn-primary py-1 px-2">Edit</a>
                    </div>
                '
            ];
        });

        return view('admin.employee-branch-assignment.list', [
            'title' => 'Employee Branch Assignments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',     'headerName' => 'S.No'],
                    ['field' => 'employee_code', 'headerName' => 'Employee Code'],
                    ['field' => 'employee_name', 'headerName' => 'Employee Name'],
                    ['field' => 'branch_name',   'headerName' => 'Branch'],
                    ['field' => 'from_date',     'headerName' => 'From Date'],
                    ['field' => 'to_date',       'headerName' => 'To Date'],
                    ['field' => 'is_current',    'headerName' => 'Current'],
                    ['field' => 'action',        'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    // ================= CREATE =================
    public function create()
    {
        return view('admin.employee-branch-assignment.create', [
            'title'     => 'Assign Branch to Employee',
            'employees' => Employee::with('person')->orderBy('code')->get(),
            'branches'  => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|exists:xlr8_admin_employee,code',
            'branch_code'   => 'required|exists:xlr8_admin_branch,code',
            'from_date'     => 'required|date',
            'to_date'       => 'nullable|date|after_or_equal:from_date',
            'is_current'    => 'boolean',
        ]);

        // ✅ DUPLICATE CHECK
        $exists = EmployeeBranchAssignment::where('employee_code', $validated['employee_code'])
            ->where('branch_code', $validated['branch_code'])
            ->exists();

        if ($exists) {
            \Alert::error('This employee is already assigned to this branch!')->flash();
            return back()->withInput();
        }

        // default current
        $validated['is_current'] = $validated['is_current'] ?? 1;

        EmployeeBranchAssignment::create($validated);

        \Alert::success('Branch assignment created successfully!')->flash();
        return redirect(backpack_url('employee-branch-assignment'));
    }

    // ================= EDIT =================
    public function edit($id)
    {
        $assignment = EmployeeBranchAssignment::with(['employee.person', 'branch'])->findOrFail($id);

        return view('admin.employee-branch-assignment.edit', [
            'title'      => 'Edit Branch Assignment',
            'assignment' => $assignment,
            'branches'   => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = EmployeeBranchAssignment::findOrFail($id);

        $validated = $request->validate([
            'branch_code' => 'required|exists:xlr8_admin_branch,code',
            'from_date'   => 'required|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'is_current'  => 'boolean',
        ]);

        $assignment->update($validated);

        \Alert::success('Branch assignment updated successfully!')->flash();
        return redirect(backpack_url('employee-branch-assignment'));
    }
}

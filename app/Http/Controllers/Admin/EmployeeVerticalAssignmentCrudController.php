<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\EmployeeVerticalAssignment;
use App\Models\Admin\Employee;
use App\Models\Admin\Vertical;

class EmployeeVerticalAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(EmployeeVerticalAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-vertical-assignment');
        CRUD::setEntityNameStrings('vertical assignment', 'vertical assignments');
    }

    public function index()
    {
        $assignments = EmployeeVerticalAssignment::with(['employee.person', 'vertical'])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $assignments->map(function ($assignment, $index) {
            $employeeName = $assignment->employee && $assignment->employee->person
                ? trim($assignment->employee->person->first_name . ' ' . $assignment->employee->person->last_name)
                : '-';

            return [
                'serial_no'       => $index + 1,
                'employee_code'   => $assignment->employee_code ?? '-',
                'employee_name'   => $employeeName,
                'vertical_name'   => $assignment->vertical?->name ?? '-',
                'is_current'      => $assignment->is_current ? 'Yes' : 'No',
                'action' => '
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="' . backpack_url("employee-vertical-assignment/{$assignment->id}/edit") . '"
                           class="btn btn-sm btn-primary py-1 px-2">Edit</a>
                    </div>
                '
            ];
        });

        return view('admin.employee-vertical-assignment.list', [
            'title' => 'Employee Vertical Assignments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',       'headerName' => 'S.No'],
                    ['field' => 'employee_code',   'headerName' => 'Employee Code'],
                    ['field' => 'employee_name',   'headerName' => 'Employee Name'],
                    ['field' => 'vertical_name',   'headerName' => 'Vertical'],
                    ['field' => 'is_current',      'headerName' => 'Current'],
                    ['field' => 'action',          'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        return view('admin.employee-vertical-assignment.create', [
            'title'     => 'Assign Vertical to Employee',
            'employees' => Employee::with('person')->orderBy('code')->get(),
            'verticals' => Vertical::orderBy('name')->get(),   // Better name
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|exists:xlr8_admin_employee,code',
            'vertical_code' => 'required|exists:xlr8_admin_vertical,code',
            'is_current'    => 'boolean',
        ]);

        EmployeeVerticalAssignment::create($validated);

        \Alert::success('Vertical assignment created successfully!')->flash();
        return redirect(backpack_url('employee-vertical-assignment'));
    }

    public function edit($id)
    {
        $assignment = EmployeeVerticalAssignment::with(['employee.person', 'vertical'])
            ->findOrFail($id);

        return view('admin.employee-vertical-assignment.edit', [
            'title'      => 'Edit Vertical Assignment',
            'assignment' => $assignment,
            'verticals'  => Vertical::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = EmployeeVerticalAssignment::findOrFail($id);

        $validated = $request->validate([
            'vertical_code' => 'required|exists:xlr8_admin_vertical,code',
            'is_current'    => 'boolean',
        ]);

        $assignment->update($validated);

        \Alert::success('Vertical assignment updated successfully!')->flash();
        return redirect(backpack_url('employee-vertical-assignment'));
    }
}

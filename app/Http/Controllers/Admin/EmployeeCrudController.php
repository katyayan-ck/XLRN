<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Person;
use App\Models\Admin\Designation;
use App\Models\Admin\Branch;
use App\Models\Admin\Department;
use App\Models\Admin\Employee;
use Illuminate\Validation\Rule;

class EmployeeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Employee::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee');
        CRUD::setEntityNameStrings('employee', 'employees');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.employee.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.employee.list');

        $employees = Employee::with(['person', 'designation', 'primaryBranch', 'primaryDepartment'])
            ->select([
                'id',
                'code',
                'person_code',
                'desig_code',
                'primary_branch_code',
                'primary_dept_code',
                'primary_div_code',
                'primary_loc_code',
                'vertical_code',
                'segment_code',
                'sub_segment_code'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $employees->map(function ($emp, $index) {
            $mapped = $emp->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['person_name'] = $emp->person
                ? trim($emp->person->first_name . ' ' . $emp->person->last_name)
                : '—';
            $mapped['designation_name'] = $emp->designation?->name ?? '—';
            $mapped['branch_name'] = $emp->primaryBranch?->name ?? '—';
            $mapped['department_name'] = $emp->primaryDepartment?->name ?? '—';
            $mapped['division'] = $emp->primary_div_code;
            $mapped['location'] = $emp->primary_loc_code;
            $mapped['vertical'] = $emp->vertical_code;
            $mapped['segment'] = $emp->segment_code;
            $mapped['sub_segment'] = $emp->sub_segment_code;

            $editUrl = backpack_url("employee/{$emp->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.employee.list', [
            'title' => 'All Employees',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no', 'headerName' => 'S.No'],
                    ['field' => 'code', 'headerName' => 'Code'],
                    ['field' => 'person_name', 'headerName' => 'Name'],
                    ['field' => 'designation_name', 'headerName' => 'Designation'],
                    ['field' => 'branch_name', 'headerName' => 'Branch'],
                    ['field' => 'department_name', 'headerName' => 'Department'],

                    ['field' => 'division', 'headerName' => 'Division'],
                    ['field' => 'location', 'headerName' => 'Location'],
                    ['field' => 'vertical', 'headerName' => 'Vertical'],
                    ['field' => 'segment', 'headerName' => 'Segment'],
                    ['field' => 'sub_segment', 'headerName' => 'Sub Segment'],

                    ['field' => 'action', 'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.employee.create');

        return view('admin.employee.create', [
            'title'        => 'Add New Employee',
            'persons'      => Person::select('person_code', 'first_name', 'last_name')->orderBy('first_name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'branches'     => Branch::orderBy('name')->get(),
            'departments'  => Department::orderBy('name')->get(),
            'divisions'    => \App\Models\Admin\Division::orderBy('name')->get(),  // ← Add this
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:xlr8_admin_employee,code',

            'person_code'         => 'required|exists:xlr8_admin_person,person_code',
            'desig_code'          => 'required|exists:xlr8_admin_designation,code',
            'primary_branch_code' => 'required|exists:xlr8_admin_branch,code',
            'primary_dept_code'   => 'required|exists:xlr8_admin_department,code',

            'primary_div_code'    => 'nullable|string|max:10',
            'primary_loc_code'    => 'nullable|string|max:5',
            'vertical_code'       => 'nullable|string|max:10',
            'segment_code'        => 'nullable|string|max:5',
            'sub_segment_code'    => 'nullable|string|max:5',
        ], [
            'primary_div_code.max'    => 'Division Code cannot exceed 10 characters.',
            'primary_loc_code.max'    => 'Location Code cannot exceed 5 characters.',
            'vertical_code.max'       => 'Vertical Code cannot exceed 10 characters.',
            'segment_code.max'        => 'Segment Code cannot exceed 5 characters.',
            'sub_segment_code.max'    => 'Sub Segment Code cannot exceed 5 characters.',
        ]);

        Employee::create($validated);

        \Alert::success('Employee created successfully!')->flash();
        return redirect(backpack_url('employee'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.employee.edit');

        $employee = Employee::with(['person', 'designation', 'primaryBranch', 'primaryDepartment'])
            ->findOrFail($id);

        return view('admin.employee.edit', [
            'title'        => 'Edit Employee',
            'employee'     => $employee,
            'persons'      => Person::select('person_code', 'first_name', 'last_name')->orderBy('first_name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'branches'     => Branch::orderBy('name')->get(),
            'departments'  => Department::orderBy('name')->get(),
            'divisions'    => \App\Models\Admin\Division::orderBy('name')->get(),  // ← Add this
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('xlr8_admin_employee', 'code')->ignore($employee->id)
            ],
            'person_code'         => 'required|exists:xlr8_admin_person,person_code',
            'desig_code'          => 'required|exists:xlr8_admin_designation,code',
            'primary_branch_code' => 'required|exists:xlr8_admin_branch,code',
            'primary_dept_code'   => 'required|exists:xlr8_admin_department,code',

            'primary_div_code'    => 'nullable|string|max:10',
            'primary_loc_code'    => 'nullable|string|max:5',
            'vertical_code'       => 'nullable|string|max:10',
            'segment_code'        => 'nullable|string|max:5',
            'sub_segment_code'    => 'nullable|string|max:5',
        ], [
            'primary_div_code.max'    => 'Division Code cannot exceed 10 characters.',
            'primary_loc_code.max'    => 'Location Code cannot exceed 5 characters.',
            'vertical_code.max'       => 'Vertical Code cannot exceed 10 characters.',
            'segment_code.max'        => 'Segment Code cannot exceed 5 characters.',
            'sub_segment_code.max'    => 'Sub Segment Code cannot exceed 5 characters.',
        ]);

        $employee->update($validated);

        \Alert::success('Employee updated successfully!')->flash();
        return redirect(backpack_url('employee'));
    }
}

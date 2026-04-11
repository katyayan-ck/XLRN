<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Core\Branch;
use App\Models\Core\Department;

class DepartmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/department');
        CRUD::setEntityNameStrings('department', 'departments');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.department.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.department.list');

        $departments = Department::with('branch')
            ->select([
                'id',
                'code',
                'name',
                'branch_id',
                'description',
                'is_active'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $departments->map(function ($dept, $index) {
            $mapped = $dept->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['branch'] = $dept->branch?->name ?? '—';

            $editUrl = backpack_url("department/{$dept->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2"
                       title="Edit">
                         Edit
                    </a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.department.list', [
            'title' => 'All Departments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'code',         'headerName' => 'Code'],
                    ['field' => 'name',         'headerName' => 'Department Name'],
                    ['field' => 'branch',       'headerName' => 'Branch'],
                    ['field' => 'description',  'headerName' => 'Description'],
                    ['field' => 'is_active',    'headerName' => 'Active'],
                    ['field' => 'action',       'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.department.create');

        return view('admin.department.create', [
            'title'     => 'Add New Department',
            'branches'  => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|unique:departments,code',
            'name'        => 'required|string|max:255',
            'branch_id'   => 'required|exists:branches,id',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        Department::create($validated);

        \Alert::success('Department created successfully!')->flash();

        return redirect(backpack_url('department'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.department.edit');

        $department = Department::with('branch')->findOrFail($id);

        return view('admin.department.edit', [
            'title'       => 'Edit Department - ' . $department->name,
            'department'  => $department,
            'branches'    => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|unique:departments,code,' . $id,
            'name'        => 'required|string|max:255',
            'branch_id'   => 'required|exists:branches,id',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $department->update($validated);

        \Alert::success('Department updated successfully!')->flash();

        return redirect(backpack_url('department'));
    }
}

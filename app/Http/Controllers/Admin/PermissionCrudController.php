<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\IAM\Permission;

class PermissionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Permission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/permission');
        CRUD::setEntityNameStrings('permission', 'permissions');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.permission.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.permission.list');

        $permissions = Permission::select([
            'id',
            'name',
            'guard_name',
            'module_id',
            'process_id',
            'created_at'
        ])
            ->orderBy('name')
            ->get();

        $gridData = $permissions->map(function ($permission, $index) {
            $mapped = $permission->toArray();
            $mapped['serial_no'] = $index + 1;

            $mapped['name_display'] = str_replace(['.', '_'], ' ', $permission->name);

            $editUrl = backpack_url("permission/{$permission->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';

            return $mapped;
        })->values();

        return view('admin.permission.list', [
            'title' => 'All Permissions',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'name',         'headerName' => 'Permission Name'],
                    ['field' => 'guard_name',   'headerName' => 'Guard'],
                    ['field' => 'action',       'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.permission.create');

        return view('admin.permission.create', [
            'title' => 'Add New Permission'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|unique:xlr8_iam_permissions,name|max:255',
            'guard_name'  => 'required|in:web,api',
        ]);

        Permission::create($validated);

        \Alert::success('Permission created successfully!')->flash();
        return redirect(backpack_url('permission'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.permission.edit');

        $permission = Permission::findOrFail($id);

        return view('admin.permission.edit', [
            'title'       => 'Edit Permission',
            'permission'  => $permission
        ]);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|unique:xlr8_iam_permissions,name,' . $id . '|max:255',
            'guard_name'  => 'required|in:web,api',
        ]);

        $permission->update($validated);

        \Alert::success('Permission updated successfully!')->flash();
        return redirect(backpack_url('permission'));
    }
}

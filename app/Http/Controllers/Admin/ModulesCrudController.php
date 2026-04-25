<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\IAM\Module;

class ModulesCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Module::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/modules');
        CRUD::setEntityNameStrings('module', 'modules');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.modules.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.modules.list');

        $modules = Module::select([
            'id',
            'code',
            'name',
            'description',
            'is_active',
            'created_at'
        ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $modules->map(function ($module, $index) {
            $mapped = $module->toArray();
            $mapped['serial_no'] = $index + 1;

            $editUrl = backpack_url("modules/{$module->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';

            // Boolean ko readable banao
            $mapped['is_active'] = $module->is_active ? 'Active' : 'Inactive';

            return $mapped;
        })->values();

        return view('admin.modules.list', [
            'title' => 'All Modules',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',     'headerName' => 'S.No'],
                    ['field' => 'code',          'headerName' => 'Code'],
                    ['field' => 'name',          'headerName' => 'Module Name'],
                    ['field' => 'description',   'headerName' => 'Description'],
                    ['field' => 'is_active',     'headerName' => 'Active'],
                    ['field' => 'action',        'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.modules.create');

        return view('admin.modules.create', [
            'title' => 'Add New Module'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|unique:modules,code|max:255',
            'name'        => 'required|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        Module::create($validated);

        \Alert::success('Module created successfully!')->flash();
        return redirect(backpack_url('modules'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.modules.edit');

        $module = Module::findOrFail($id);

        return view('admin.modules.edit', [
            'title'  => 'Edit Module',
            'module' => $module
        ]);
    }

    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|unique:modules,code,' . $id . '|max:255',
            'name'        => 'required|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $module->update($validated);

        \Alert::success('Module updated successfully!')->flash();
        return redirect(backpack_url('modules'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Designation;

class DesignationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Designation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/designation');
        CRUD::setEntityNameStrings('designation', 'designations');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.designation.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.designation.list');

        $designations = Designation::select([
            'id',
            'code',
            'name',
            'description',
            'is_active'
        ])->orderBy('id', 'desc')->get();

        $gridData = $designations->map(function ($desig, $index) {
            $mapped = $desig->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['is_active'] = $desig->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("designation/{$desig->id}/edit");

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

        return view('admin.designation.list', [
            'title' => 'All Designations',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'code',         'headerName' => 'Code'],
                    ['field' => 'name',         'headerName' => 'Designation Name'],
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
        $this->crud->setCreateView('admin.designation.create');

        return view('admin.designation.create', [
            'title' => 'Add New Designation',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|unique:xlr8_admin_designation,code',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        Designation::create($validated);

        \Alert::success('Designation created successfully!')->flash();

        return redirect(backpack_url('designation'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.designation.edit');

        $designation = Designation::findOrFail($id);

        return view('admin.designation.edit', [
            'title'       => 'Edit Designation - ' . $designation->name,
            'designation' => $designation,
        ]);
    }

    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|unique:xlr8_admin_designation,code,' . $id,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $designation->update($validated);

        \Alert::success('Designation updated successfully!')->flash();

        return redirect(backpack_url('designation'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Branch;   // ← Correct model

class BranchCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \App\Http\Controllers\Admin\Traits\ScopedCrud;

    protected function getScopeType(): string
    {
        return 'branch';
    }

    public function setup()
    {
        CRUD::setModel(Branch::class);                    // ← Fixed
        CRUD::setRoute(config('backpack.base.route_prefix') . '/branch');
        CRUD::setEntityNameStrings('branch', 'branches');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.branch.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.branch.list');

        $branches = Branch::select([
            'id',
            'code',
            'name',
            'short_name',
            'description',   // ✅ ADD
            'phone',
            'email',
            'address',       // ✅ ADD
            'city',
            'state',
            'pincode',
            'country',       // ✅ ADD
            'latitude',      // ✅ ADD
            'longitude',     // ✅ ADD
            'is_head_office',
            'is_active'
        ])->orderBy('id', 'desc')->get();

        $gridData = $branches->map(function ($branch, $index) {
            $mapped = $branch->toArray();
            $mapped['serial_no'] = $index + 1;

            $mapped['is_active'] = $branch->is_active ? 'Active' : 'Inactive';
            $mapped['is_head_office'] = $branch->is_head_office ? 'Yes' : 'No';

            $editUrl = backpack_url("branch/{$branch->id}/edit");

            $mapped['action'] = '
            <div class="d-flex gap-2 justify-content-center">
                <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
            </div>';

            return $mapped;
        })->values();

        return view('admin.branch.list', [
            'title' => 'All Branches',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',      'headerName' => 'S.No'],
                    ['field' => 'code',           'headerName' => 'Code'],
                    ['field' => 'name',           'headerName' => 'Branch Name'],
                    ['field' => 'short_name',     'headerName' => 'Short Name'],
                    ['field' => 'description',    'headerName' => 'Description'], // ✅
                    ['field' => 'phone',          'headerName' => 'Phone'],
                    ['field' => 'email',          'headerName' => 'Email'],
                    ['field' => 'address',        'headerName' => 'Address'],     // ✅
                    ['field' => 'city',           'headerName' => 'City'],
                    ['field' => 'state',          'headerName' => 'State'],
                    ['field' => 'pincode',        'headerName' => 'Pincode'],
                    ['field' => 'country',        'headerName' => 'Country'],     // ✅
                    ['field' => 'latitude',       'headerName' => 'Latitude'],    // ✅
                    ['field' => 'longitude',      'headerName' => 'Longitude'],   // ✅
                    ['field' => 'is_head_office', 'headerName' => 'Head Office'],
                    ['field' => 'is_active',      'headerName' => 'Status'],
                    ['field' => 'action',         'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.branch.edit');

        $branch = Branch::findOrFail($id);   // ← Fixed

        return view('admin.branch.edit', [
            'title'  => 'Edit Branch - ' . $branch->name,
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);   // ← Fixed

        $validated = $request->validate([
            'code'           => 'required|string|unique:xlr8_admin_branch,code,' . $id,
            'name'           => 'required|string|max:255',
            'short_name'     => 'required|string|max:100',
            'description'    => 'nullable|string',     // ✅
            'phone'          => 'nullable|string',
            'email'          => 'nullable|email',
            'address'        => 'nullable|string',     // ✅
            'city'           => 'required|string',
            'state'          => 'required|string',
            'pincode'        => 'required|string',
            'country'        => 'nullable|string',     // ✅
            'latitude'  => 'nullable|numeric|between:-90,90',    // ✅
            'longitude' => 'nullable|numeric|between:-180,180',    // ✅
            'is_head_office' => 'boolean',
            'is_active'      => 'boolean',
        ]);

        $branch->update($validated);

        \Alert::success('Branch updated successfully!')->flash();

        return redirect(backpack_url('branch'));
    }

    public function create()
    {
        $this->crud->setCreateView('admin.branch.create');

        return view('admin.branch.create', [
            'title' => 'Add New Branch',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::field('code');
        CRUD::field('name');
        CRUD::field('short_name');
        CRUD::field('description')->type('textarea');
        CRUD::field('phone');
        CRUD::field('email');
        CRUD::field('address')->type('textarea');
        CRUD::field('city');
        CRUD::field('state');
        CRUD::field('pincode');
        CRUD::field('country');
        CRUD::field('latitude');
        CRUD::field('longitude');
        CRUD::field('is_head_office')->type('checkbox');
        CRUD::field('is_active')->type('checkbox');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}

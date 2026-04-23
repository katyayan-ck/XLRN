<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\Brand;


class BrandCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/brand');
        CRUD::setEntityNameStrings('brand', 'brands');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.brand.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.brand.list');

        $brands = Brand::select([
            'id',
            'code',
            'name',
            'description',
            'is_active'
        ])->orderBy('id', 'desc')->get();

        $gridData = $brands->map(function ($brand, $index) {
            $mapped = $brand->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['is_active'] = $brand->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("brand/{$brand->id}/edit");

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

        return view('admin.brand.list', [
            'title' => 'All Brands',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'code',         'headerName' => 'Code'],
                    ['field' => 'name',         'headerName' => 'Brand Name'],
                    ['field' => 'description',  'headerName' => 'Description'],
                    ['field' => 'is_active',    'headerName' => 'Active'],
                    ['field' => 'action',       'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.brand.edit');

        $brand = Brand::findOrFail($id);

        return view('admin.brand.edit', [
            'title' => 'Edit Brand - ' . $brand->name,
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:xlr8_vehicle_brand,code,' . $id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $brand->update($validated);

        \Alert::success('Brand updated successfully!')->flash();

        return redirect(backpack_url('brand'));
    }

    public function create()
    {
        $this->crud->setCreateView('admin.brand.create');

        return view('admin.brand.create', [
            'title' => 'Add New Brand',
        ]);
    }
}

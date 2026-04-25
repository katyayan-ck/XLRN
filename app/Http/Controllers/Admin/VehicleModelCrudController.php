<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\VehicleModel;
use App\Models\Vehicle\Brand;
use App\Models\Vehicle\Segment;
use App\Models\Vehicle\SubSegment;

class VehicleModelCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(VehicleModel::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vehicle-model');
        CRUD::setEntityNameStrings('vehicle model', 'vehicle models');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.vehicle-model.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.vehicle-model.list');

        $models = VehicleModel::with(['brand', 'segment', 'subSegment'])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $models->map(function ($model, $index) {
            $mapped = $model->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand']       = $model->brand?->name ?? '—';
            $mapped['segment']     = $model->segment?->name ?? '—';
            $mapped['sub_segment'] = $model->subSegment?->name ?? '—';

            $editUrl = backpack_url("vehicle-model/{$model->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2"
                       title="Edit">
                         Edit
                    </a>
                </div>
            ';

            $mapped['is_active'] = $model->is_active ? 'Active' : 'Inactive';

            return $mapped;
        })->values();

        return view('admin.vehicle-model.list', [
            'title' => 'All Vehicle Models',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'brand',        'headerName' => 'Brand'],
                    ['field' => 'segment',      'headerName' => 'Segment'],
                    ['field' => 'sub_segment',  'headerName' => 'Sub Segment'],
                    ['field' => 'name',         'headerName' => 'Model Name'],
                    ['field' => 'oem_code',     'headerName' => 'OEM Code'],
                    ['field' => 'custom_name',  'headerName' => 'Custom Name'],
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
        $this->crud->setEditView('admin.vehicle-model.edit');

        $vehiclemodel = VehicleModel::with(['brand', 'segment', 'subSegment'])->findOrFail($id);

        return view('admin.vehicle-model.edit', [
            'title'        => 'Edit Vehicle Model - ' . $vehiclemodel->name,
            'vehiclemodel' => $vehiclemodel,
            'brands'       => Brand::orderBy('name')->get(),
            'segments'     => Segment::orderBy('name')->get(),
            'subsegments'  => SubSegment::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $vehiclemodel = VehicleModel::findOrFail($id);

        $validated = $request->validate([
            'brand_id'       => 'required|exists:brands,id',
            'segment_id'     => 'required|exists:segments,id',
            'sub_segment_id' => 'nullable|exists:sub_segments,id',
            'name'           => 'required|string|max:255',
            'custom_name'    => 'nullable|string|max:255',
            'oem_code'       => 'nullable|string|max:255|unique:vehicle_models,oem_code,' . $id,
            'description'    => 'nullable|string',
            'is_active'      => 'boolean',
        ]);

        $vehiclemodel->update($validated);

        \Alert::success('Vehicle Model updated successfully!')->flash();

        return redirect(backpack_url('vehicle-model'));
    }

    public function create()
    {
        $this->crud->setCreateView('admin.vehicle-model.create');

        return view('admin.vehicle-model.create', [
            'title'        => 'Add New Vehicle Model',
            'brands'       => Brand::orderBy('name')->get(),
            'segments'     => Segment::orderBy('name')->get(),
            'subsegments'  => SubSegment::orderBy('name')->get(),
        ]);
    }
}

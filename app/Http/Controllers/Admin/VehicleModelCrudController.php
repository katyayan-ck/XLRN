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
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

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

    // ====================== LIST ======================
    public function index()
    {
        $models = VehicleModel::with(['brand', 'segment', 'subSegment'])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $models->map(function ($model, $index) {
            $mapped = $model->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand']       = $model->brand?->name ?? '—';
            $mapped['segment']     = $model->segment?->name ?? '—';
            $mapped['sub_segment'] = $model->subSegment?->name ?? '—';
            $mapped['is_active']   = $model->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("vehicle-model/{$model->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2" title="Edit">
                         Edit
                    </a>
                </div>
            ';

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

    // ====================== CREATE ======================
    public function create()
    {
        $this->crud->setCreateView('admin.vehicle-model.create');

        return view('admin.vehicle-model.create', [
            'title'        => 'Add New Vehicle Model',
            'brands'       => Brand::orderBy('name')->get(),
            'segments'     => Segment::with('brand')->orderBy('name')->get(),
            'subsegments'  => SubSegment::with('brand')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id'       => 'required|exists:xlr8_vehicle_brand,id',
            'segment_id'     => 'required|exists:xlr8_vehicle_segment,id',
            'sub_segment_id' => 'nullable|exists:xlr8_vehicle_subsegment,id',
            'name'           => 'required|string|max:255',
            'custom_name'    => 'nullable|string|max:255',
            'oem_code'       => 'nullable|string|max:255|unique:xlr8_vehicle_model,oem_code',
            'description'    => 'nullable|string',
            'is_active'      => 'boolean',
        ]);

        $brand     = Brand::findOrFail($validated['brand_id']);
        $segment   = Segment::findOrFail($validated['segment_id']);
        $subsegment = $validated['sub_segment_id']
            ? SubSegment::findOrFail($validated['sub_segment_id'])
            : null;

        $vehiclemodel = new VehicleModel();
        $vehiclemodel->brand_code      = $brand->code;
        $vehiclemodel->segment_code    = $segment->code;
        $vehiclemodel->sub_segment_code = $subsegment?->code;
        $vehiclemodel->code            = VehicleModel::generateCode($validated['name']); // optional
        $vehiclemodel->name            = $validated['name'];
        $vehiclemodel->custom_name     = $validated['custom_name'] ?? null;
        $vehiclemodel->oem_code        = $validated['oem_code'] ?? null;
        $vehiclemodel->description     = $validated['description'] ?? null;
        $vehiclemodel->is_active       = $validated['is_active'] ?? true;
        $vehiclemodel->save();

        \Alert::success('Vehicle Model created successfully!')->flash();
        return redirect(backpack_url('vehicle-model'));
    }

    // ====================== EDIT ======================
    public function edit($id)
    {
        $this->crud->setEditView('admin.vehicle-model.edit');

        $vehiclemodel = VehicleModel::with(['brand', 'segment', 'subSegment'])->findOrFail($id);

        return view('admin.vehicle-model.edit', [
            'title'        => 'Edit Vehicle Model - ' . $vehiclemodel->name,
            'vehiclemodel' => $vehiclemodel,
            'brands'       => Brand::orderBy('name')->get(),
            'segments'     => Segment::with('brand')->orderBy('name')->get(),
            'subsegments'  => SubSegment::with('brand')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $vehiclemodel = VehicleModel::findOrFail($id);

        $validated = $request->validate([
            'brand_id'       => 'required|exists:xlr8_vehicle_brand,id',
            'segment_id'     => 'required|exists:xlr8_vehicle_segment,id',
            'sub_segment_id' => 'nullable|exists:xlr8_vehicle_subsegment,id',
            'name'           => 'required|string|max:255',
            'custom_name'    => 'nullable|string|max:255',
            'oem_code'       => 'nullable|string|max:255|unique:xlr8_vehicle_model,oem_code,' . $id,
            'description'    => 'nullable|string',
            'is_active'      => 'boolean',
        ]);

        $brand     = Brand::findOrFail($validated['brand_id']);
        $segment   = Segment::findOrFail($validated['segment_id']);
        $subsegment = $validated['sub_segment_id']
            ? SubSegment::findOrFail($validated['sub_segment_id'])
            : null;

        $vehiclemodel->update([
            'brand_code'      => $brand->code,
            'segment_code'    => $segment->code,
            'sub_segment_code' => $subsegment?->code,
            'name'            => $validated['name'],
            'custom_name'     => $validated['custom_name'] ?? null,
            'oem_code'        => $validated['oem_code'] ?? null,
            'description'     => $validated['description'] ?? null,
            'is_active'       => $validated['is_active'] ?? true,
        ]);

        \Alert::success('Vehicle Model updated successfully!')->flash();
        return redirect(backpack_url('vehicle-model'));
    }
}

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

            return [
                'serial_no' => $index + 1,

                // ✅ CODE FIELDS (IMPORTANT)
                'brand_code'       => $model->brand_code ?? '—',
                'segment_code'     => $model->segment_code ?? '—',
                'sub_segment_code' => $model->sub_segment_code ?? '—',

                // ✅ MAIN DATA
                'code'     => $model->code ?? '—',
                'name'     => $model->name ?? '—',
                'oem_name' => $model->oem_name ?? '—',

                // ✅ STATUS
                'is_active' => $model->is_active ? 'Active' : 'Inactive',

                // ✅ ACTION BUTTON
                'action' => '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . backpack_url("vehicle-model/{$model->id}/edit") . '"
                       class="btn btn-sm btn-primary py-1 px-2" title="Edit">
                        Edit
                    </a>
                </div>
            '
            ];
        })->values();

        return view('admin.vehicle-model.list', [
            'title' => 'All Vehicle Models',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',        'headerName' => 'S.No'],

                    ['field' => 'brand_code',       'headerName' => 'Brand Code'],
                    ['field' => 'segment_code',     'headerName' => 'Segment Code'],
                    ['field' => 'sub_segment_code', 'headerName' => 'Sub Segment Code'],

                    ['field' => 'code',             'headerName' => 'Model Code'],
                    ['field' => 'name',             'headerName' => 'Model Name'],
                    ['field' => 'oem_name',         'headerName' => 'OEM Name'],

                    ['field' => 'is_active',        'headerName' => 'Status'],
                    ['field' => 'action',           'headerName' => 'Actions']
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
            'brand_code'       => 'required|exists:xlr8_vehicle_brand,code',
            'segment_code'     => 'required|exists:xlr8_vehicle_segment,code',
            'sub_segment_code' => 'nullable|exists:xlr8_vehicle_subsegment,code',
            'name'             => 'required|string|max:255',
            'oem_name'         => 'nullable|string|max:255|unique:xlr8_vehicle_model,oem_name',
            'is_active'        => 'boolean',
        ]);

        $vehiclemodel = new VehicleModel();

        $vehiclemodel->brand_code       = $validated['brand_code'];
        $vehiclemodel->segment_code     = $validated['segment_code'];
        $vehiclemodel->sub_segment_code = $validated['sub_segment_code'] ?? null;

        $vehiclemodel->code = VehicleModel::generateCode($validated['name']);
        $vehiclemodel->name = $validated['name'];

        $vehiclemodel->oem_name  = $validated['oem_name'] ?? null;
        $vehiclemodel->is_active = $validated['is_active'] ?? true;

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
            'brand_code'       => 'required|exists:xlr8_vehicle_brand,code',
            'segment_code'     => 'required|exists:xlr8_vehicle_segment,code',
            'sub_segment_code' => 'nullable|exists:xlr8_vehicle_subsegment,code',
            'name'             => 'required|string|max:255',
            'oem_name'         => 'nullable|string|max:255|unique:xlr8_vehicle_model,oem_name,' . $id,
            'is_active'        => 'boolean',
        ]);

        $vehiclemodel->update([
            'brand_code'       => $validated['brand_code'],
            'segment_code'     => $validated['segment_code'],
            'sub_segment_code' => $validated['sub_segment_code'] ?? null,

            'name'        => $validated['name'],
            'oem_name'    => $validated['oem_name'] ?? null,

            'is_active'   => $validated['is_active'] ?? true,
        ]);

        \Alert::success('Vehicle Model updated successfully!')->flash();
        return redirect(backpack_url('vehicle-model'));
    }
}

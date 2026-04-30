<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\Variant;
use App\Models\Vehicle\Brand;
use App\Models\Vehicle\Segment;
use App\Models\Vehicle\VehicleModel;
use App\Models\Vehicle\SubSegment;

class VariantCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Variant::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/variant');
        CRUD::setEntityNameStrings('variant', 'variants');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.variant.list');
    }

    // ====================== LIST ======================
    public function index()
    {
        $variants = Variant::with(['brand', 'segment', 'subSegment', 'vehicleModel'])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $variants->map(function ($item, $index) {
            $mapped = $item->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand']       = $item->brand?->name ?? '—';
            $mapped['segment']     = $item->segment?->name ?? '—';
            $mapped['sub_segment'] = $item->subSegment?->name ?? '—';
            $mapped['model']       = $item->vehicleModel?->name ?? '—';
            $mapped['is_active']   = $item->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("variant/{$item->id}/edit");

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

        return view('admin.variant.list', [
            'title' => 'All Variants',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'brand',        'headerName' => 'Brand'],
                    ['field' => 'segment',      'headerName' => 'Segment'],
                    ['field' => 'sub_segment',  'headerName' => 'Sub Segment'],
                    ['field' => 'model',        'headerName' => 'Model'],
                    ['field' => 'name',         'headerName' => 'Variant Name'],
                    ['field' => 'oem_code',     'headerName' => 'OEM Code'],
                    ['field' => 'custom_name',  'headerName' => 'Custom Name'],
                    ['field' => 'seating_capacity', 'headerName' => 'Seating'],
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
        $this->crud->setCreateView('admin.variant.create');

        return view('admin.variant.create', [
            'title'         => 'Add New Variant',
            'brands'        => Brand::orderBy('name')->get(),
            'segments'      => Segment::with('brand')->orderBy('name')->get(),
            'vehiclemodels' => VehicleModel::with('brand')->orderBy('name')->get(),
            'subsegments'   => SubSegment::with('brand')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id'         => 'required|exists:xlr8_vehicle_brand,id',
            'segment_id'       => 'required|exists:xlr8_vehicle_segment,id',
            'vehicle_model_id' => 'required|exists:xlr8_vehicle_model,id',
            'sub_segment_id'   => 'nullable|exists:xlr8_vehicle_subsegment,id',
            'name'             => 'required|string|max:255',
            'custom_name'      => 'nullable|string|max:255',
            'oem_code'         => 'nullable|string|max:255|unique:xlr8_vehicle_variant,oem_code',
            'description'      => 'nullable|string',
            'seating_capacity' => 'nullable|integer|min:1',
            'wheels'           => 'nullable|integer|min:2',
            'gvw'              => 'nullable|integer',
            'cc_capacity'      => 'nullable|string|max:255',
            'is_csd'           => 'boolean',
            'is_active'        => 'boolean',
        ]);

        // Fetch parent records to get codes
        $brand   = Brand::findOrFail($validated['brand_id']);
        $segment = Segment::findOrFail($validated['segment_id']);
        $model   = VehicleModel::findOrFail($validated['vehicle_model_id']);
        $subsegment = $validated['sub_segment_id']
            ? SubSegment::findOrFail($validated['sub_segment_id'])
            : null;

        $variant = new Variant();
        $variant->brand_code       = $brand->code;
        $variant->segment_code     = $segment->code;
        $variant->sub_segment_code = $subsegment?->code;
        $variant->model_code       = $model->code;

        $variant->code             = strtoupper($validated['name']); // ya generateCode() use kar sakte ho
        $variant->name             = $validated['name'];
        $variant->custom_name      = $validated['custom_name'] ?? null;
        $variant->oem_code         = $validated['oem_code'] ?? null;
        $variant->description      = $validated['description'] ?? null;
        $variant->seating_capacity = $validated['seating_capacity'];
        $variant->wheels           = $validated['wheels'] ?? 4;
        $variant->gvw              = $validated['gvw'];
        $variant->cc_capacity      = $validated['cc_capacity'];
        $variant->is_csd           = $validated['is_csd'] ?? false;
        $variant->is_active        = $validated['is_active'] ?? true;

        $variant->save();

        \Alert::success('Variant created successfully!')->flash();
        return redirect(backpack_url('variant'));
    }

    // ====================== EDIT ======================
    public function edit($id)
    {
        $this->crud->setEditView('admin.variant.edit');

        $variant = Variant::with(['brand', 'segment', 'subSegment', 'vehicleModel'])
            ->findOrFail($id);

        return view('admin.variant.edit', [
            'title'         => 'Edit Variant - ' . $variant->name,
            'variant'       => $variant,
            'brands'        => Brand::orderBy('name')->get(),
            'segments'      => Segment::with('brand')->orderBy('name')->get(),
            'vehiclemodels' => VehicleModel::with('brand')->orderBy('name')->get(),
            'subsegments'   => SubSegment::with('brand')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $variant = Variant::findOrFail($id);

        $validated = $request->validate([
            'brand_id'         => 'required|exists:xlr8_vehicle_brand,id',
            'segment_id'       => 'required|exists:xlr8_vehicle_segment,id',
            'vehicle_model_id' => 'required|exists:xlr8_vehicle_model,id',
            'sub_segment_id'   => 'nullable|exists:xlr8_vehicle_subsegment,id',
            'name'             => 'required|string|max:255',
            'custom_name'      => 'nullable|string|max:255',
            'oem_code'         => 'nullable|string|max:255|unique:xlr8_vehicle_variant,oem_code,' . $id,
            'description'      => 'nullable|string',
            'seating_capacity' => 'nullable|integer|min:1',
            'wheels'           => 'nullable|integer|min:2',
            'gvw'              => 'nullable|integer',
            'cc_capacity'      => 'nullable|string|max:255',
            'is_csd'           => 'boolean',
            'is_active'        => 'boolean',
        ]);

        $brand   = Brand::findOrFail($validated['brand_id']);
        $segment = Segment::findOrFail($validated['segment_id']);
        $model   = VehicleModel::findOrFail($validated['vehicle_model_id']);
        $subsegment = $validated['sub_segment_id']
            ? SubSegment::findOrFail($validated['sub_segment_id'])
            : null;

        $variant->update([
            'brand_code'       => $brand->code,
            'segment_code'     => $segment->code,
            'sub_segment_code' => $subsegment?->code,
            'model_code'       => $model->code,
            'name'             => $validated['name'],
            'custom_name'      => $validated['custom_name'] ?? null,
            'oem_code'         => $validated['oem_code'] ?? null,
            'description'      => $validated['description'] ?? null,
            'seating_capacity' => $validated['seating_capacity'],
            'wheels'           => $validated['wheels'] ?? 4,
            'gvw'              => $validated['gvw'],
            'cc_capacity'      => $validated['cc_capacity'],
            'is_csd'           => $validated['is_csd'] ?? false,
            'is_active'        => $validated['is_active'] ?? true,
        ]);

        \Alert::success('Variant updated successfully!')->flash();
        return redirect(backpack_url('variant'));
    }
}

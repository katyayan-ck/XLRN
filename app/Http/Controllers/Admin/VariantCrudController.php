<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use \App\Models\Vehicle\Variant;
use \App\Models\Admin\Brand;
use \App\Models\Admin\Segment;
use \App\Models\Admin\VehicleModel;
use \App\Models\Admin\SubSegment;


class VariantCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

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

    // public function index()
    // {
    //     $this->crud->setListView('admin.variant.list');

    //     $variants = \App\Models\Core\Variant::with(['brand', 'segment', 'subSegment', 'vehicleModel'])
    //         ->orderBy('id', 'desc')
    //         ->get();

    //     $gridData = $variants->map(function ($item, $index) {
    //         $mapped = $item->toArray();
    //         $mapped['serial_no'] = $index + 1;
    //         $mapped['brand']       = $item->brand?->name ?? '—';
    //         $mapped['segment']     = $item->segment?->name ?? '—';
    //         $mapped['sub_segment'] = $item->subSegment?->name ?? '—';
    //         $mapped['model']       = $item->vehicleModel?->name ?? '—';

    //         $editUrl = backpack_url("variant/{$item->id}/edit");

    //         $mapped['action'] = '
    //             <div class="d-flex gap-2 justify-content-center">
    //                 <a href="' . $editUrl . '"
    //                    class="btn btn-sm btn-primary py-1 px-2"
    //                    title="Edit">
    //                      Edit
    //                 </a>
    //             </div>
    //         ';

    //         $mapped['is_active'] = $item->is_active ? 'Active' : 'Inactive';

    //         return $mapped;
    //     })->values();

    //     return view('admin.variant.list', [
    //         'title' => 'All Variants',
    //         'gridConfig' => [
    //             'columns' => [
    //                 ['field' => 'serial_no',      'headerName' => 'S.No'],
    //                 ['field' => 'brand',          'headerName' => 'Brand'],
    //                 ['field' => 'segment',        'headerName' => 'Segment'],
    //                 ['field' => 'sub_segment',    'headerName' => 'Sub Segment'],
    //                 ['field' => 'model',          'headerName' => 'Model'],
    //                 ['field' => 'name',           'headerName' => 'Variant Name'],
    //                 ['field' => 'oem_code',       'headerName' => 'OEM Code'],
    //                 ['field' => 'seating_capacity', 'headerName' => 'Seating'],
    //                 ['field' => 'is_active',      'headerName' => 'Active'],
    //                 ['field' => 'action',         'headerName' => 'Actions']
    //             ],
    //             'data' => $gridData
    //         ]
    //     ]);
    // }
    public function index()
    {
        $this->crud->setListView('admin.variant.list');

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

            // Extra fields jo ab dikhenge
            $mapped['custom_name']     = $item->custom_name ?? '—';
            $mapped['wheels']          = $item->wheels ?? '—';
            $mapped['gvw']             = $item->gvw ?? '—';
            $mapped['cc_capacity']     = $item->cc_capacity ?? '—';

            $editUrl = backpack_url("variant/{$item->id}/edit");

            $mapped['action'] = '
            <div class="d-flex gap-2 justify-content-center">
                <a href="' . $editUrl . '"
                   class="btn btn-sm btn-primary py-1 px-2"
                   title="Edit">
                     Edit
                </a>
            </div>
        ';

            $mapped['is_active'] = $item->is_active ? 'Active' : 'Inactive';

            return $mapped;
        })->values();

        return view('admin.variant.list', [
            'title' => 'All Variants',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',        'headerName' => 'S.No'],
                    ['field' => 'brand',            'headerName' => 'Brand'],
                    ['field' => 'segment',          'headerName' => 'Segment'],
                    ['field' => 'sub_segment',      'headerName' => 'Sub Segment'],
                    ['field' => 'model',            'headerName' => 'Model'],
                    ['field' => 'name',             'headerName' => 'Variant Name'],
                    ['field' => 'oem_code',         'headerName' => 'OEM Code'],
                    ['field' => 'custom_name',      'headerName' => 'Custom Name'],
                    ['field' => 'seating_capacity', 'headerName' => 'Seating'],
                    ['field' => 'wheels',           'headerName' => 'Wheels'],
                    ['field' => 'gvw',              'headerName' => 'GVW (kg)'],
                    ['field' => 'cc_capacity',      'headerName' => 'Engine CC'],
                    ['field' => 'is_active',        'headerName' => 'Active'],
                    ['field' => 'action',           'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.variant.edit');

        $variant = Variant::with(['brand', 'segment', 'subSegment', 'vehicleModel'])->findOrFail($id);

        return view('admin.variant.edit', [
            'title'         => 'Edit Variant - ' . $variant->name,
            'variant'       => $variant,
            'brands'        => Brand::orderBy('name')->get(),
            'segments'      => Segment::orderBy('name')->get(),
            'vehiclemodels' => VehicleModel::orderBy('name')->get(),
            'subsegments'   => SubSegment::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $variant = Variant::findOrFail($id);

        $validated = $request->validate([
            'brand_id'         => 'required|exists:brands,id',
            'segment_id'       => 'required|exists:segments,id',
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'sub_segment_id'   => 'nullable|exists:sub_segments,id',
            'name'             => 'required|string|max:255',
            'custom_name'      => 'nullable|string|max:255',
            'oem_code'         => 'nullable|string|max:255|unique:variants,oem_code,' . $id,
            'description'      => 'nullable|string',
            'seating_capacity' => 'nullable|integer|min:1',
            'wheels'           => 'nullable|integer|min:2',
            'gvw'              => 'nullable|integer',
            'cc_capacity'      => 'nullable|string|max:255',
            'is_csd'           => 'boolean',
            'csd_index'        => 'nullable|string|max:255',
            'is_active'        => 'boolean',
        ]);

        $variant->update($validated);

        \Alert::success('Variant updated successfully!')->flash();

        return redirect(backpack_url('variant'));
    }

    public function create()
    {
        $this->crud->setCreateView('admin.variant.create');

        return view('admin.variant.create', [
            'title'         => 'Add New Variant',
            'brands'        => Brand::orderBy('name')->get(),
            'segments'      => Segment::orderBy('name')->get(),
            'vehiclemodels' => VehicleModel::orderBy('name')->get(),
            'subsegments'   => SubSegment::orderBy('name')->get(),
        ]);
    }
}

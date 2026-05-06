<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\Segment;
use App\Models\Vehicle\Brand;

class SegmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Segment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/segment');
        CRUD::setEntityNameStrings('segment', 'segments');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.segment.list');
    }

    // ====================== LIST ======================
    public function index()
    {
        $segments = Segment::with('brand')
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $segments->map(function ($segment, $index) {
            $mapped = $segment->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand'] = $segment->brand?->name ?? '—';
            $mapped['is_active'] = $segment->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("segment/{$segment->id}/edit");

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

        return view('admin.segment.list', [
            'title' => 'All Segments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',   'headerName' => 'S.No'],
                    ['field' => 'code',        'headerName' => 'Code'],
                    ['field' => 'name',        'headerName' => 'Segment Name'],
                    ['field' => 'brand',       'headerName' => 'Brand'],

                    ['field' => 'is_active',   'headerName' => 'Active'],
                    ['field' => 'action',      'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    // ====================== CREATE ======================
    public function create()
    {
        $this->crud->setCreateView('admin.segment.create');

        return view('admin.segment.create', [
            'title'  => 'Add New Segment',
            'brands' => Brand::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id'    => 'required|exists:xlr8_vehicle_brand,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:xlr8_vehicle_segment,code,NULL,id,brand_code,' . Brand::find($request->brand_id)->code,

            'is_active'   => 'boolean',
        ], [
            'code.size'   => 'Segment Code must be exactly 5 characters long.',
            'code.unique' => 'This Segment Code already exists for this brand.',
        ]);

        $brand = Brand::findOrFail($validated['brand_id']);

        $segment = new Segment();
        $segment->brand_code = $brand->code;           // ← Important
        $segment->code       = strtoupper($validated['code']);
        $segment->name       = $validated['name'];

        $segment->is_active  = $validated['is_active'] ?? true;
        $segment->save();

        \Alert::success('Segment created successfully!')->flash();
        return redirect(backpack_url('segment'));
    }

    // ====================== EDIT ======================
    public function edit($id)
    {
        $this->crud->setEditView('admin.segment.edit');

        $segment = Segment::findOrFail($id);

        return view('admin.segment.edit', [
            'title'   => 'Edit Segment - ' . $segment->name,
            'segment' => $segment,
            'brands'  => Brand::orderBy('name')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $segment = Segment::findOrFail($id);

        $validated = $request->validate([
            'brand_id'    => 'required|exists:xlr8_vehicle_brand,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:xlr8_vehicle_segment,code,' . $id . ',id,brand_code,' . Brand::find($request->brand_id)->code,

            'is_active'   => 'boolean',
        ], [
            'code.size'   => 'Segment Code must be exactly 5 characters long.',
            'code.unique' => 'This Segment Code already exists for this brand.',
        ]);

        $brand = Brand::findOrFail($validated['brand_id']);

        $segment->update([
            'brand_code'  => $brand->code,
            'code'        => strtoupper($validated['code']),
            'name'        => $validated['name'],

            'is_active'   => $validated['is_active'] ?? true,
        ]);

        \Alert::success('Segment updated successfully!')->flash();
        return redirect(backpack_url('segment'));
    }
}

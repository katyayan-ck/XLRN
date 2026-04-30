<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\SubSegment;
use App\Models\Vehicle\Segment;

class SubSegmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(SubSegment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sub-segment');
        CRUD::setEntityNameStrings('sub segment', 'sub segments');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.sub-segment.list');
    }

    // ====================== LIST ======================
    public function index()
    {
        $subsegments = SubSegment::with(['segment.brand'])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $subsegments->map(function ($item, $index) {
            $mapped = $item->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand']     = $item->segment?->brand?->name ?? '—';
            $mapped['segment']   = $item->segment?->name ?? '—';
            $mapped['is_active'] = $item->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("sub-segment/{$item->id}/edit");

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

        return view('admin.sub-segment.list', [
            'title' => 'All Sub Segments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',   'headerName' => 'S.No'],
                    ['field' => 'code',        'headerName' => 'Code'],
                    ['field' => 'name',        'headerName' => 'Sub Segment Name'],
                    ['field' => 'brand',       'headerName' => 'Brand'],
                    ['field' => 'segment',     'headerName' => 'Segment'],
                    ['field' => 'description', 'headerName' => 'Description'],
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
        $this->crud->setCreateView('admin.sub-segment.create');

        return view('admin.sub-segment.create', [
            'title'    => 'Add New Sub Segment',
            'segments' => Segment::with('brand')->orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'segment_id'  => 'required|exists:xlr8_vehicle_segment,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ], [
            'code.size'   => 'Sub Segment Code must be exactly 5 characters long.',
        ]);

        $segment = Segment::with('brand')->findOrFail($validated['segment_id']);

        $subsegment = new SubSegment();
        $subsegment->brand_code   = $segment->brand_code;     // ← Important
        $subsegment->segment_code = $segment->code;           // ← Important
        $subsegment->code         = strtoupper($validated['code']);
        $subsegment->name         = $validated['name'];
        $subsegment->description  = $validated['description'] ?? null;
        $subsegment->is_active    = $validated['is_active'] ?? true;
        $subsegment->save();

        \Alert::success('Sub Segment created successfully!')->flash();
        return redirect(backpack_url('sub-segment'));
    }

    // ====================== EDIT ======================
    public function edit($id)
    {
        $this->crud->setEditView('admin.sub-segment.edit');

        $subsegment = SubSegment::with(['segment.brand'])->findOrFail($id);

        return view('admin.sub-segment.edit', [
            'title'      => 'Edit Sub Segment - ' . $subsegment->name,
            'subsegment' => $subsegment,
            'segments'   => Segment::with('brand')->orderBy('name')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $subsegment = SubSegment::findOrFail($id);

        $validated = $request->validate([
            'segment_id'  => 'required|exists:xlr8_vehicle_segment,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ], [
            'code.size'   => 'Sub Segment Code must be exactly 5 characters long.',
        ]);

        $segment = Segment::with('brand')->findOrFail($validated['segment_id']);

        $subsegment->update([
            'brand_code'   => $segment->brand_code,
            'segment_code' => $segment->code,
            'code'         => strtoupper($validated['code']),
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'is_active'    => $validated['is_active'] ?? true,
        ]);

        \Alert::success('Sub Segment updated successfully!')->flash();
        return redirect(backpack_url('sub-segment'));
    }
}

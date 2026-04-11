<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class SegmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Vehicle\Segment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/segment');
        CRUD::setEntityNameStrings('segment', 'segments');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.segment.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.segment.list');

        $segments = \App\Models\Core\Segment::with('brand')
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $segments->map(function ($segment, $index) {
            $mapped = $segment->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand'] = $segment->brand?->name ?? '—';

            $editUrl = backpack_url("segment/{$segment->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2"
                       title="Edit">
                         Edit
                    </a>
                </div>
            ';

            // Display Active/Inactive nicely
            $mapped['is_active'] = $segment->is_active ? 'Active' : 'Inactive';

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
                    ['field' => 'description', 'headerName' => 'Description'],
                    ['field' => 'is_active',   'headerName' => 'Active'],
                    ['field' => 'action',      'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.segment.edit');

        $segment = \App\Models\Core\Segment::findOrFail($id);

        return view('admin.segment.edit', [
            'title'   => 'Edit Segment - ' . $segment->name,
            'segment' => $segment,
            'brands'  => \App\Models\Core\Brand::orderBy('name')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $segment = \App\Models\Core\Segment::findOrFail($id);

        $validated = $request->validate([
            'brand_id'    => 'required|exists:brands,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:segments,code,' . $id . ',id,brand_id,' . $request->brand_id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $segment->update($validated);

        \Alert::success('Segment updated successfully!')->flash();

        return redirect(backpack_url('segment'));
    }

    public function create()
    {
        $this->crud->setCreateView('admin.segment.create');

        return view('admin.segment.create', [
            'title'  => 'Add New Segment',
            'brands' => \App\Models\Core\Brand::orderBy('name')->get()
        ]);
    }
}

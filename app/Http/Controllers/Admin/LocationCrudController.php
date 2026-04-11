<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Core\Location;
use App\Models\Core\Branch;

class LocationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Location::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/location');
        CRUD::setEntityNameStrings('location', 'locations');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.location.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.location.list');

        $locations = Location::with('branch')
            ->select([
                'id',
                'code',
                'name',
                'branch_id',
                'city',
                'state',
                'pincode',
                'is_active'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $locations->map(function ($loc, $index) {
            $mapped = $loc->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['branch'] = $loc->branch?->name ?? '—';

            $editUrl = backpack_url("location/{$loc->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.location.list', [
            'title' => 'All Locations',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no', 'headerName' => 'S.No'],
                    ['field' => 'code',      'headerName' => 'Code'],
                    ['field' => 'name',      'headerName' => 'Name'],
                    ['field' => 'branch',    'headerName' => 'Branch'],
                    ['field' => 'city',      'headerName' => 'City'],
                    ['field' => 'state',     'headerName' => 'State'],
                    ['field' => 'is_active', 'headerName' => 'Active'],
                    ['field' => 'action',    'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.location.create');

        return view('admin.location.create', [
            'title'    => 'Add New Location',
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'      => 'required|string|unique:locations,code',
            'name'      => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'city'      => 'nullable|string',
            'state'     => 'nullable|string',
            'pincode'   => 'nullable|string',
            'address'   => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Location::create($validated);

        \Alert::success('Location created successfully!')->flash();
        return redirect(backpack_url('location'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.location.edit');

        $location = Location::findOrFail($id);

        return view('admin.location.edit', [
            'title'    => 'Edit Location',
            'location' => $location,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'code'      => 'required|string|unique:locations,code,' . $id,
            'name'      => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'city'      => 'nullable|string',
            'state'     => 'nullable|string',
            'pincode'   => 'nullable|string',
            'address'   => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        \Alert::success('Location updated successfully!')->flash();
        return redirect(backpack_url('location'));
    }
}

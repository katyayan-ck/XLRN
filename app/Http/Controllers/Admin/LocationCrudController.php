<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Location;
use App\Models\Admin\Branch;

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
                'branch_code',
                'code',
                'name',
                'description',
                'phone',
                'email',
                'address',
                'city',
                'state',
                'pincode',
                'latitude',
                'longitude',
                'is_active'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $locations->map(function ($loc, $index) {
            $mapped = $loc->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['is_active'] = $loc->is_active ? 'Active' : 'Inactive';


            // Show Branch Name in List (Important)
            $mapped['branch'] = $loc->branch?->name ?? $loc->branch_code ?? '—';

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
                    ['field' => 'serial_no',   'headerName' => 'S.No'],
                    ['field' => 'branch',      'headerName' => 'Branch'],
                    ['field' => 'code',        'headerName' => 'Code'],
                    ['field' => 'name',        'headerName' => 'Name'],
                    ['field' => 'description', 'headerName' => 'Description'],
                    ['field' => 'phone',       'headerName' => 'Phone'],
                    ['field' => 'email',       'headerName' => 'Email'],
                    ['field' => 'address',     'headerName' => 'Address'],
                    ['field' => 'city',        'headerName' => 'City'],
                    ['field' => 'state',       'headerName' => 'State'],
                    ['field' => 'pincode',     'headerName' => 'Pincode'],
                    ['field' => 'latitude',    'headerName' => 'Latitude'],
                    ['field' => 'longitude',   'headerName' => 'Longitude'],
                    ['field' => 'is_active',   'headerName' => 'Status'],
                    ['field' => 'action',      'headerName' => 'Action']
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
        // print_r($request->all()); // Debugging line, remove in production
        // die();
        $validated = $request->validate([
            'code'        => 'required|string|unique:xlr8_admin_location,code',
            'name'        => 'required|string|max:255',
            'branch_code' => 'required|exists:xlr8_admin_branch,code',

            'description' => 'nullable|string',
            'phone'       => 'nullable|string',
            'email'       => 'nullable|email',
            'address'     => 'nullable|string',

            'city'        => 'nullable|string',
            'state'       => 'nullable|string',

            'pincode'     => ['required', 'regex:/^[1-9][0-9]{5}$/'],

            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',

            'is_active'   => 'boolean',
        ]);

        Location::create($validated);

        \Alert::success('Location created successfully!')->flash();
        return redirect(backpack_url('location'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.location.edit');

        $location = Location::findOrFail($id);

        $branches = Branch::orderBy('name')->get();   // ← Variable mein store kiya

        // dd($branches);   // ← Debugging line (yeh data dikhaayega)

        return view('admin.location.edit', [
            'title'     => 'Edit Location - ' . $location->name,
            'location'  => $location,
            'branches'  => $branches,        // ← Yahan bhi same variable use kiya
        ]);
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|unique:xlr8_admin_location,code,' . $id,
            'name'        => 'required|string|max:255',
            'branch_code' => 'required|exists:xlr8_admin_branch,code',

            'description' => 'nullable|string',
            'phone'       => 'nullable|string',
            'email'       => 'nullable|email',
            'address'     => 'nullable|string',

            'city'        => 'nullable|string',
            'state'       => 'nullable|string',

            'pincode'     => ['required', 'regex:/^[1-9][0-9]{5}$/'],

            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',

            'is_active'   => 'boolean',
        ]);

        $location->update($validated);

        \Alert::success('Location updated successfully!')->flash();
        return redirect(backpack_url('location'));
    }
}

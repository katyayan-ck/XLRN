<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Person;
use App\Models\Admin\PersonAddress;

class PersonAddressCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(PersonAddress::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-address');
        CRUD::setEntityNameStrings('person address', 'person addresses');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.person-address.list');
    }

    public function index()
    {
        $addresses = PersonAddress::with('person')
            ->select([
                'id',
                'person_code',
                'address_type',
                'address_line_1',
                'address_line_2',
                'landmark',
                'city',
                'taluka',
                'district',
                'state',
                'country',
                'pincode',
                'latitude',
                'longitude',
                'created_at'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $addresses->map(function ($address, $index) {
            $mapped = $address->toArray();

            $mapped['serial_no'] = $index + 1;

            $mapped['person_name'] = $address->person
                ? trim(($address->person->first_name ?? '') . ' ' . ($address->person->last_name ?? ''))
                : '—';

            $editUrl = backpack_url("person-address/{$address->id}/edit");

            $mapped['action'] = '
        <div class="d-flex gap-2 justify-content-center">
            <a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>
        </div>
    ';

            return $mapped;
        })->values();

        return view('admin.person-address.list', [
            'title' => 'All Person Addresses',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',        'headerName' => 'S.No'],
                    ['field' => 'person_code', 'headerName' => 'Person Code'],
                    ['field' => 'person_name',      'headerName' => 'Person'],

                    ['field' => 'address_type',     'headerName' => 'Address Type'],

                    ['field' => 'address_line_1',   'headerName' => 'Address Line 1'],
                    ['field' => 'address_line_2',   'headerName' => 'Address Line 2'],
                    ['field' => 'landmark',         'headerName' => 'Landmark'],

                    ['field' => 'city',             'headerName' => 'City'],
                    ['field' => 'taluka',           'headerName' => 'Taluka'],
                    ['field' => 'district',         'headerName' => 'District'],
                    ['field' => 'state',            'headerName' => 'State'],
                    ['field' => 'country',          'headerName' => 'Country'],
                    ['field' => 'pincode',          'headerName' => 'Pincode'],

                    ['field' => 'latitude',         'headerName' => 'Latitude'],
                    ['field' => 'longitude',        'headerName' => 'Longitude'],

                    ['field' => 'action',           'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.person-address.create');

        return view('admin.person-address.create', [
            'title'   => 'Add New Person Address',
            'persons' => Person::select('id', 'person_code', 'first_name', 'last_name', 'display_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_code'    => 'required|exists:xlr8_admin_person,person_code',
            'address_type'   => 'required|in:Primary,Office,Home,Alternate,Permanent',
            'address_line_1' => 'required|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'landmark'       => 'nullable|string|max:80',
            'city'           => 'required|string|max:60',
            'taluka'         => 'nullable|string|max:60',
            'district'       => 'nullable|string|max:60',
            'state'          => 'required|string|max:60',
            'country'        => 'required|in:India,USA,UK',
            'pincode'        => 'nullable|string|max:10',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        // ✅ ADD THIS BLOCK HERE
        if ($request->address_type === 'Primary') {
            PersonAddress::where('person_code', $request->person_code)
                ->where('address_type', 'Primary')
                ->update(['address_type' => 'Alternate']);
        }

        PersonAddress::create($validated);

        \Alert::success('Person Address created successfully!')->flash();
        return redirect(backpack_url('person-address'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.person-address.edit');

        $address = PersonAddress::with('person')->findOrFail($id);

        return view('admin.person-address.edit', [
            'title'    => 'Edit Person Address',
            'address'  => $address,
            'persons'  => Person::select('id', 'person_code', 'first_name', 'last_name', 'display_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $address = PersonAddress::findOrFail($id);

        $validated = $request->validate([
            'person_code'    => 'required|exists:xlr8_admin_person,person_code',
            'address_type'   => 'required|in:Primary,Office,Home,Alternate,Permanent',
            'address_line_1' => 'required|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'landmark'       => 'nullable|string|max:80',
            'city'           => 'required|string|max:60',
            'taluka'         => 'nullable|string|max:60',
            'district'       => 'nullable|string|max:60',
            'state'          => 'required|string|max:60',
            'country'        => 'required|in:India,USA,UK',
            'pincode'        => 'nullable|string|max:10',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        // ✅ ADD THIS BLOCK HERE
        if ($request->address_type === 'Primary') {
            PersonAddress::where('person_code', $request->person_code)
                ->where('address_type', 'Primary')
                ->where('id', '!=', $id) // IMPORTANT
                ->update(['address_type' => 'Alternate']);
        }

        $address->update($validated);

        \Alert::success('Person Address updated successfully!')->flash();
        return redirect(backpack_url('person-address'));
    }
}

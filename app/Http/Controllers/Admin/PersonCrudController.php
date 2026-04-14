<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Core\Person;

class PersonCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Person::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person');
        CRUD::setEntityNameStrings('person', 'persons');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.person.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.person.list');

        $persons = Person::select([
            'id',
            'code',
            'salutation',
            'first_name',
            'middle_name',
            'last_name',
            'display_name',
            'gender',
            'dob',
            'occupation',
            'mobile_primary',
            'email_primary'
        ])->orderBy('id', 'desc')->get();

        $gridData = $persons->map(function ($person, $index) {
            $mapped = $person->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['full_name'] = trim("{$person->first_name} {$person->middle_name} {$person->last_name}");

            // Format DOB as dd/mm/yyyy
            $mapped['dob'] = $person->dob?->format('d/m/Y') ?? '—';

            $editUrl = backpack_url("person/{$person->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.person.list', [
            'title' => 'All Persons',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',      'headerName' => 'S.No'],
                    ['field' => 'code',           'headerName' => 'Code'],
                    ['field' => 'salutation',     'headerName' => 'Salutation'],
                    ['field' => 'full_name',      'headerName' => 'Full Name'],
                    ['field' => 'display_name',   'headerName' => 'Display Name'],
                    ['field' => 'gender',         'headerName' => 'Gender'],
                    ['field' => 'dob',            'headerName' => 'Date of Birth'],
                    ['field' => 'occupation',     'headerName' => 'Occupation'],
                    ['field' => 'mobile_primary', 'headerName' => 'Mobile'],
                    ['field' => 'email_primary',  'headerName' => 'Email'],
                    ['field' => 'action',         'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.person.create');
        return view('admin.person.create', ['title' => 'Add New Person']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'            => 'required|string|unique:persons,code',
            'salutation'      => 'nullable|in:Mr,Mrs,Ms,Dr',
            'first_name'      => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'last_name'       => 'nullable|string|max:100',           // ← Changed to nullable
            'display_name'    => 'nullable|string|max:255',
            'gender'          => 'nullable|in:male,female,other,prefer_not_to_say',
            'dob'             => 'nullable|date',
            'marital_status'  => 'nullable|in:single,married,divorced,widowed',
            'spouse_name'     => 'nullable|string',
            'occupation'      => 'nullable|string',
            'aadhaar_no'      => 'nullable|string|max:12',
            'pan_no'          => 'nullable|string|max:10',
            'gst_no'          => 'nullable|string|max:15',
            'email_primary'   => 'required|email',
            'email_secondary' => 'nullable|email',
            'mobile_primary'  => 'required|digits:10',                // ← 10 digits only
            'mobile_secondary' => 'nullable|digits:10',                // ← 10 digits only
        ]);

        Person::create($validated);

        \Alert::success('Person created successfully!')->flash();
        return redirect(backpack_url('person'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.person.edit');
        $person = Person::findOrFail($id);

        return view('admin.person.edit', [
            'title'  => 'Edit Person - ' . ($person->display_name ?? $person->first_name . ' ' . $person->last_name),
            'person' => $person,
        ]);
    }

    public function update(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'code'            => 'required|string|unique:persons,code,' . $id,
            'salutation'      => 'nullable|in:Mr,Mrs,Ms,Dr',
            'first_name'      => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'last_name'       => 'nullable|string|max:100',           // ← Changed to nullable
            'display_name'    => 'nullable|string|max:255',
            'gender'          => 'nullable|in:male,female,other,prefer_not_to_say',
            'dob'             => 'nullable|date',
            'marital_status'  => 'nullable|in:single,married,divorced,widowed',
            'spouse_name'     => 'nullable|string',
            'occupation'      => 'nullable|string',
            'aadhaar_no'      => 'nullable|string|max:12',
            'pan_no'          => 'nullable|string|max:10',
            'gst_no'          => 'nullable|string|max:15',
            'email_primary'   => 'required|email',
            'email_secondary' => 'nullable|email',
            'mobile_primary'  => 'required|digits:10',                // ← 10 digits only
            'mobile_secondary' => 'nullable|digits:10',                // ← 10 digits only
        ]);

        $person->update($validated);

        \Alert::success('Person updated successfully!')->flash();
        return redirect(backpack_url('person'));
    }
}

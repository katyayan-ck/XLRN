<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Person;
use App\Models\Admin\PersonContact;

class PersonContactCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(PersonContact::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-contact');
        CRUD::setEntityNameStrings('person contact', 'person contacts');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.person-contact.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.person-contact.list');

        $contacts = PersonContact::with('person')
            ->select([
                'id',
                'person_id',
                'type',
                'name',
                'mobile',
                'email',
                'relationship',
                'is_primary',
                'notes'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $contacts->map(function ($contact, $index) {
            $mapped = $contact->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['person_name'] = $contact->person ? $contact->person->first_name . ' ' . $contact->person->last_name : '—';

            $editUrl = backpack_url("person-contact/{$contact->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.person-contact.list', [
            'title' => 'All Person Contacts',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'person_name',  'headerName' => 'Person'],
                    ['field' => 'type',         'headerName' => 'Type'],
                    ['field' => 'name',         'headerName' => 'Name'],
                    ['field' => 'mobile',       'headerName' => 'Mobile'],
                    ['field' => 'email',        'headerName' => 'Email'],
                    ['field' => 'relationship', 'headerName' => 'Relationship'],
                    ['field' => 'is_primary',   'headerName' => 'Primary'],
                    ['field' => 'action',       'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.person-contact.create');

        return view('admin.person-contact.create', [
            'title'   => 'Add New Person Contact',
            'persons' => Person::select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id'    => 'required|exists:persons,id',
            'type'         => 'required|string|max:50',
            'name'         => 'required|string|max:100',
            'mobile'       => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100',
            'relationship' => 'nullable|string|max:50',
            'notes'        => 'nullable|string',
            'is_primary'   => 'boolean',
        ]);

        PersonContact::create($validated);

        \Alert::success('Person Contact created successfully!')->flash();
        return redirect(backpack_url('person-contact'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.person-contact.edit');

        $contact = PersonContact::with('person')->findOrFail($id);

        return view('admin.person-contact.edit', [
            'title'   => 'Edit Person Contact',
            'contact' => $contact,
            'persons' => Person::select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $contact = PersonContact::findOrFail($id);

        $validated = $request->validate([
            'person_id'    => 'required|exists:persons,id',
            'type'         => 'required|string|max:50',
            'name'         => 'required|string|max:100',
            'mobile'       => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100',
            'relationship' => 'nullable|string|max:50',
            'notes'        => 'nullable|string',
            'is_primary'   => 'boolean',
        ]);

        $contact->update($validated);

        \Alert::success('Person Contact updated successfully!')->flash();
        return redirect(backpack_url('person-contact'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Person;

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
        $persons = Person::select([
            'id',
            'person_code',
            'entity_type',
            'salutation',
            'first_name',
            'middle_name',
            'last_name',
            'display_name',
            'gender',
            'dob',
            'marital_status',
            'spouse_name',
            'occupation',
            'aadhaar_no',
            'pan_no',
            'tan_no',
            'gst_no'
        ])->orderBy('id', 'desc')->get();

        $gridData = $persons->map(function ($person, $index) {
            $mapped = $person->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['full_name'] = $person->full_name ?? trim("{$person->first_name} {$person->middle_name} {$person->last_name}");
            $mapped['dob'] = $person->dob?->format('d/m/Y') ?? '—';
            $mapped['entity_type'] = ucwords(str_replace('_', ' ', $person->entity_type ?? ''));

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
                    ['field' => 'person_code',    'headerName' => 'Person Code'],
                    ['field' => 'entity_type',    'headerName' => 'Entity Type'],
                    ['field' => 'salutation', 'headerName' => 'Salutation'],
                    ['field' => 'full_name',      'headerName' => 'Full Name'],
                    ['field' => 'display_name',   'headerName' => 'Display Name'],
                    ['field' => 'gender',         'headerName' => 'Gender'],
                    ['field' => 'dob',            'headerName' => 'Date of Birth'],
                    ['field' => 'occupation',     'headerName' => 'Occupation'],
                    ['field' => 'pan_no',         'headerName' => 'PAN No'],
                    ['field' => 'aadhaar_no',     'headerName' => 'Aadhaar No'],
                    ['field' => 'gst_no',         'headerName' => 'GSTIN'],
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
            'entity_type'     => 'required|in:individual,legal_entity',
            'salutation'      => 'nullable|in:Mr,Mrs,Ms,Dr',
            'first_name'      => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'last_name'       => 'nullable|string|max:100',
            'display_name'    => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'dob'             => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'spouse_name'     => 'nullable|string|max:100',
            'occupation'      => 'nullable|string|max:100',
            'aadhaar_no' => [
                'nullable',
                'digits:12',
                'unique:xlr8_admin_person,aadhaar_no,' . $id
            ],
            'pan_no' => [
                'nullable',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'tan_no'          => 'nullable|string|max:20',
            'gst_no'          => 'nullable|string|max:15',
        ]);

        // Code field automatically generate kar rahe hain
        $validated['code'] = 'PERS-' . str_pad(Person::withTrashed()->max('id') ?? 0 + 1, 5, '0', STR_PAD_LEFT);

        Person::create($validated);

        \Alert::success('Person created successfully!')->flash();
        return redirect(backpack_url('person'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.person.edit');
        $person = Person::findOrFail($id);

        return view('admin.person.edit', [
            'title'  => 'Edit Person - ' . ($person->display_name ?? $person->full_name),
            'person' => $person,
        ]);
    }

    public function update(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        // ✅ Fix PAN case (important)
        if ($request->pan_no) {
            $request->merge([
                'pan_no' => strtoupper($request->pan_no)
            ]);
        }

        $validated = $request->validate([
            'salutation'      => 'nullable|in:Mr,Mrs,Ms,Dr',
            'first_name'      => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'last_name'       => 'nullable|string|max:100',
            'display_name'    => 'nullable|string|max:255',
            'gender'          => 'nullable|in:male,female,other,prefer_not_to_say',
            'dob'             => 'nullable|date',
            'marital_status'  => 'nullable|in:single,married,divorced,widowed',
            'spouse_name'     => 'nullable|string|max:100',
            'occupation'      => 'nullable|string|max:100',
            'aadhaar_no' => [
                'nullable',
                'digits:12',
                'unique:xlr8_admin_person,aadhaar_no,' . $id
            ],

            // ✅ FIXED PAN VALIDATION
            'pan_no' => [
                'nullable',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],

            'tan_no'          => 'nullable|string|max:20',
            'gst_no'          => 'nullable|string|max:15',
        ]);

        // ✅ Update data
        $person->update($validated);

        \Alert::success('Person updated successfully!')->flash();

        return redirect(backpack_url('person'));
    }
}

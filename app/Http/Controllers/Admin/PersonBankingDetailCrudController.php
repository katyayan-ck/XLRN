<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Person;
use App\Models\Admin\PersonBankingDetail;

class PersonBankingDetailCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(PersonBankingDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-banking-detail');
        CRUD::setEntityNameStrings('person banking detail', 'person banking details');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.person-banking-detail.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.person-banking-detail.list');

        $bankings = PersonBankingDetail::with('person')
            ->select([
                'id',
                'person_code',
                'account_type',
                'bank_name',
                'branch_name',
                'account_number',
                'account_holder_name',
                'ifsc_code',
                'micr_code',
                'account_nature',
                'is_verified',
                'verified_at'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $bankings->map(function ($banking, $index) {
            $mapped = $banking->toArray();
            $mapped['serial_no'] = $index + 1;

            $mapped['is_verified'] = $banking->is_verified;
            $mapped['person_code'] = $banking->person_code ?? '—';

            $editUrl = backpack_url("person-banking-detail/{$banking->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.person-banking-detail.list', [
            'title' => 'All Person Banking Details',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',           'headerName' => 'S.No'],
                    ['field' => 'person_code',         'headerName' => 'Person code'],

                    ['field' => 'bank_name',           'headerName' => 'Bank'],
                    ['field' => 'account_holder_name', 'headerName' => 'Holder Name'],
                    ['field' => 'account_number',      'headerName' => 'Account No'],

                    ['field' => 'ifsc_code',           'headerName' => 'IFSC'],
                    ['field' => 'micr_code',           'headerName' => 'MICR'],

                    ['field' => 'branch_name',         'headerName' => 'Branch'],
                    ['field' => 'account_type',        'headerName' => 'Type'],
                    ['field' => 'account_nature',      'headerName' => 'Nature'],

                    ['field' => 'is_verified',         'headerName' => 'Verified'],

                    ['field' => 'action',              'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.person-banking-detail.create');

        return view('admin.person-banking-detail.create', [
            'title'   => 'Add New Banking Detail',
            'persons' => Person::select('person_code', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_code' => 'required|exists:xlr8_admin_person,person_code',
            'bank_name'           => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255|regex:/^[a-zA-Z\s.]+$/u', // Letters, space, dot only
            'account_number'      => 'required|numeric|digits_between:8,20',           // Only numbers, 8-20 digits
            'ifsc_code'           => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/', // Strict IFSC format
            'account_type' => 'required|in:Primary,Secondary,Joint,Trust',
            'branch_name'         => 'nullable|string|max:255',

            'micr_code'      => 'nullable|string|max:20',
            'account_nature' => 'nullable|in:Savings,Current,Salary,NRO,NRE',

            'is_verified'         => 'boolean',
        ]);

        PersonBankingDetail::create($validated); // now contains person_code

        \Alert::success('Banking Detail created successfully!')->flash();
        return redirect(backpack_url('person-banking-detail'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.person-banking-detail.edit');

        $banking = PersonBankingDetail::with('person')->findOrFail($id);

        return view('admin.person-banking-detail.edit', [
            'title'   => 'Edit Banking Detail',
            'banking' => $banking,
            'persons' => Person::select('person_code', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $banking = PersonBankingDetail::findOrFail($id);

        $validated = $request->validate([
            'person_code' => 'required|exists:xlr8_admin_person,person_code',
            'bank_name'           => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255|regex:/^[a-zA-Z\s.]+$/u',
            'account_number'      => 'required|numeric|digits_between:8,20',
            'ifsc_code'           => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'account_type' => 'required|in:Primary,Secondary,Joint,Trust',
            'branch_name'         => 'nullable|string|max:255',
            'micr_code'      => 'nullable|string|max:20',
            'account_nature' => 'nullable|in:Savings,Current,Salary,NRO,NRE',
            'is_verified'         => 'boolean',
        ]);

        $banking->update($validated);

        \Alert::success('Banking Detail updated successfully!')->flash();
        return redirect(backpack_url('person-banking-detail'));
    }
}

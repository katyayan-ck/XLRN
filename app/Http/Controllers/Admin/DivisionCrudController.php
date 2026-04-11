<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Core\Department;
use App\Models\Core\Division;

class DivisionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Division::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/division');
        CRUD::setEntityNameStrings('division', 'divisions');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.division.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.division.list');

        $divisions = Division::with('department')
            ->select([
                'id',
                'code',
                'name',
                'department_id',
                'description',
                'is_active'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $divisions->map(function ($division, $index) {
            $mapped = $division->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['department'] = $division->department?->name ?? '—';

            $editUrl = backpack_url("division/{$division->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2"
                       title="Edit">
                         Edit
                    </a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.division.list', [
            'title' => 'All Divisions',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',     'headerName' => 'S.No'],
                    ['field' => 'code',          'headerName' => 'Code'],
                    ['field' => 'name',          'headerName' => 'Division Name'],
                    ['field' => 'department',    'headerName' => 'Department'],
                    ['field' => 'description',   'headerName' => 'Description'],
                    ['field' => 'is_active',     'headerName' => 'Active'],
                    ['field' => 'action',        'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.division.create');

        return view('admin.division.create', [
            'title'       => 'Add New Division',
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => 'required|string|unique:divisions,code',
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        Division::create($validated);

        \Alert::success('Division created successfully!')->flash();

        return redirect(backpack_url('division'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.division.edit');

        $division = Division::with('department')->findOrFail($id);

        return view('admin.division.edit', [
            'title'       => 'Edit Division - ' . $division->name,
            'division'    => $division,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $division = Division::findOrFail($id);

        $validated = $request->validate([
            'code'          => 'required|string|unique:divisions,code,' . $id,
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $division->update($validated);

        \Alert::success('Division updated successfully!')->flash();

        return redirect(backpack_url('division'));
    }
}

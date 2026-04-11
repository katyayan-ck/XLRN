<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Vertical;

class VerticalCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Vertical::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vertical');
        CRUD::setEntityNameStrings('vertical', 'verticals');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.vertical.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.vertical.list');

        $verticals = Vertical::select([
            'id',
            'code',
            'name',
            'description',
            'is_active'
        ])->orderBy('id', 'desc')->get();

        $gridData = $verticals->map(function ($vertical, $index) {
            $mapped = $vertical->toArray();
            $mapped['serial_no'] = $index + 1;

            $editUrl = backpack_url("vertical/{$vertical->id}/edit");

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

        return view('admin.vertical.list', [
            'title' => 'All Verticals',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'code',         'headerName' => 'Code'],
                    ['field' => 'name',         'headerName' => 'Vertical Name'],
                    ['field' => 'description',  'headerName' => 'Description'],
                    ['field' => 'is_active',    'headerName' => 'Active'],
                    ['field' => 'action',       'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.vertical.create');

        return view('admin.vertical.create', [
            'title' => 'Add New Vertical',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:5|unique:verticals,code',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        Vertical::create($validated);

        \Alert::success('Vertical created successfully!')->flash();

        return redirect(backpack_url('vertical'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.vertical.edit');

        $vertical = Vertical::findOrFail($id);

        return view('admin.vertical.edit', [
            'title'    => 'Edit Vertical - ' . $vertical->name,
            'vertical' => $vertical,
        ]);
    }

    public function update(Request $request, $id)
    {
        $vertical = Vertical::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|max:5|unique:verticals,code,' . $id,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $vertical->update($validated);

        \Alert::success('Vertical updated successfully!')->flash();

        return redirect(backpack_url('vertical'));
    }
}

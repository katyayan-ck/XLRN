<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\Color;
use App\Models\Vehicle\Brand;
use App\Models\Vehicle\Segment;
use App\Models\Vehicle\SubSegment;
use App\Models\Vehicle\VehicleModel;

class ColorCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Color::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/color');
        CRUD::setEntityNameStrings('color', 'colors');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.color.list');
    }

    // ====================== LIST ======================
    public function index()
    {
        $colors = Color::with('brand')
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $colors->map(function ($color, $index) {
            $mapped = $color->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['brand'] = $color->brand?->name ?? '—';

            $editUrl = backpack_url("color/{$color->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2" title="Edit">
                         Edit
                    </a>
                </div>
            ';

            $mapped['is_active'] = $color->is_active ? 'Active' : 'Inactive';

            return $mapped;
        })->values();

        return view('admin.color.list', [
            'title' => 'All Colors',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no', 'headerName' => 'S.No'],
                    ['field' => 'brand',     'headerName' => 'Brand'],
                    ['field' => 'name',      'headerName' => 'Color Name'],
                    ['field' => 'code',      'headerName' => 'Color Code'],
                    ['field' => 'hex_code',  'headerName' => 'Hex Code'],
                    ['field' => 'is_active', 'headerName' => 'Active'],
                    ['field' => 'action',    'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    // ====================== CREATE ======================
    public function create()
    {
        $this->crud->setCreateView('admin.color.create');

        return view('admin.color.create', [
            'title'  => 'Add New Color',
            'brands' => Brand::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:xlr8_vehicle_brand,id',
            'name'     => 'required|string|max:255',
            'code'     => 'required|string|max:50|regex:/^[A-Za-z]+$/|unique:xlr8_vehicle_color,code',
            'hex_code' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ], [
            'code.regex' => 'Color Code must contain only alphabets (A-Z, a-z).',
            'hex_code.regex' => 'Hex Code must be valid like #FF0000',
        ]);

        $brand = Brand::findOrFail($validated['brand_id']);

        $color = new Color();
        $color->brand_code = $brand->code;
        $color->model_code = 'DEFAULT'; // ya actual model_code
        $color->code       = strtoupper($validated['code']);
        $color->name       = $validated['name'];
        $color->hex_code   = strtoupper($validated['hex_code']);
        $color->is_active  = $validated['is_active'] ?? true;
        $color->save();

        \Alert::success('Color created successfully!')->flash();
        return redirect(backpack_url('color'));
    }

    // ====================== EDIT ======================
    public function edit($id)
    {
        $this->crud->setEditView('admin.color.edit');

        $color = Color::findOrFail($id);

        return view('admin.color.edit', [
            'title'  => 'Edit Color - ' . $color->name,
            'color'  => $color,
            'brands' => Brand::orderBy('name')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $color = Color::findOrFail($id);

        $validated = $request->validate([
            'brand_id' => 'required|exists:xlr8_vehicle_brand,id',
            'name'     => 'required|string|max:255',
            'code'     => 'required|string|max:50|regex:/^[A-Za-z]+$/|unique:xlr8_vehicle_color,code,' . $id,
            'hex_code' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ], [
            'code.regex' => 'Color Code must contain only alphabets (A-Z, a-z).',
            'hex_code.regex' => 'Hex Code must be valid like #FF0000',
        ]);

        $brand = Brand::findOrFail($validated['brand_id']);

        $color->update([
            'brand_code' => $brand->code,
            'code'       => strtoupper($validated['code']),
            'name'       => $validated['name'],
            'hex_code'   => strtoupper($validated['hex_code']),
            'is_active'  => $validated['is_active'] ?? true,
        ]);

        \Alert::success('Color updated successfully!')->flash();
        return redirect(backpack_url('color'));
    }
}

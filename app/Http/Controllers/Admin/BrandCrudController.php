<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Vehicle\Brand;

class BrandCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    public function setup()
    {
        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/brand');
        CRUD::setEntityNameStrings('brand', 'brands');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.brand.list');
    }

    // ====================== LIST ======================
    public function index()
    {
        $brands = Brand::select([
            'id',
            'code',
            'name',
            'description',
            'is_active'
        ])->orderBy('id', 'desc')->get();

        $gridData = $brands->map(function ($brand, $index) {
            $mapped = $brand->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['is_active'] = $brand->is_active ? 'Active' : 'Inactive';

            $editUrl = backpack_url("brand/{$brand->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2" title="Edit">
                         Edit
                    </a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.brand.list', [
            'title' => 'All Brands',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',    'headerName' => 'S.No'],
                    ['field' => 'code',         'headerName' => 'Code'],
                    ['field' => 'name',         'headerName' => 'Brand Name'],
                    ['field' => 'description',  'headerName' => 'Description'],
                    ['field' => 'is_active',    'headerName' => 'Active'],
                    ['field' => 'action',       'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    // ====================== CREATE ======================
    public function create()
    {
        $this->crud->setCreateView('admin.brand.create');
        return view('admin.brand.create', ['title' => 'Add New Brand']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:xlr8_vehicle_brand,code',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ], [
            'code.required' => 'Brand Code is required.',
            'code.size'     => 'Brand Code must be exactly 5 characters long.',
            'code.unique'   => 'This Brand Code is already taken. Please choose another one.',
            'name.required' => 'Brand Name is required.',
        ]);

        Brand::create($validated);

        \Alert::success('Brand created successfully!')->flash();
        return redirect(backpack_url('brand'));
    }

    // ====================== EDIT ======================
    public function edit($id)
    {
        $this->crud->setEditView('admin.brand.edit');
        $brand = Brand::findOrFail($id);

        return view('admin.brand.edit', [
            'title' => 'Edit Brand - ' . $brand->name,
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:xlr8_vehicle_brand,code,' . $id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ], [
            'code.required' => 'Brand Code is required.',
            'code.size'     => 'Brand Code must be exactly 5 characters long.',
            'code.unique'   => 'This Brand Code is already taken. Please choose another one.',
            'name.required' => 'Brand Name is required.',
        ]);

        $brand->update($validated);

        \Alert::success('Brand updated successfully!')->flash();
        return redirect(backpack_url('brand'));
    }
    public function import(Request $request)
{
    ini_set('max_execution_time', 300);

    if (!$request->hasFile('excel_file')) {
        \Alert::error('No file uploaded!')->flash();
        return redirect()->back();
    }

    $file = $request->file('excel_file');
    if (!in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
        \Alert::error('Only Excel files (.xlsx, .xls) allowed')->flash();
        return redirect()->back();
    }

    try {
        $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($file->getPathname());
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            \Alert::error('Excel file is empty.')->flash();
            return redirect()->back();
        }

        // KeyValue Maps
        $keyvalues = \DB::table('xlr8_utils_keyvalue')
            ->whereIn('keyword_master_id', [6, 9, 10, 11, 12])
            ->get()
            ->groupBy('keyword_master_id');

        $fuelMap     = $this->buildKeyMap($keyvalues->get(6, collect()));
        $bodyMakeMap = $this->buildKeyMap($keyvalues->get(9, collect()));
        $bodyTypeMap = $this->buildKeyMap($keyvalues->get(10, collect()));
        $permitMap   = $this->buildKeyMap($keyvalues->get(11, collect()));
        $statusMap   = $this->buildKeyMap($keyvalues->get(12, collect()));

        $segmentMapping = [
            'COMMERCIAL' => 'COMML',
            'PERSONAL'   => 'PERSL',
            'LMM'        => 'LMM',
            'BEV'        => 'BEV',
            'XUV'        => 'XUV',
            'COMML'      => 'COMML',
            'PERSL'      => 'PERSL',
        ];

        $brandCode = 'MHD';
        $now       = now();
        $stats     = ['segment' => 0, 'subsegment' => 0, 'model' => 0, 'variant' => 0, 'color' => 0, 'skipped' => 0];

        \Log::info("=== Vehicle Import Started ===", [
            'file' => $file->getClientOriginalName(), 
            'total_rows' => count($rows) - 1
        ]);

        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;

            try {
                $excelVehicleCode = trim($row[1] ?? '');
                $rawModel         = strtoupper(trim($row[2] ?? ''));
                $rawSegment       = strtoupper(trim($row[7] ?? ''));

                $oemVariant    = trim($row[3] ?? '');
                $customModel   = trim($row[4] ?? '');
                $customVariant = trim($row[5] ?? '');

                if (empty($rawModel) || empty($excelVehicleCode)) {
                    \Log::warning("Row {$excelRow} SKIPPED: Empty Model or Vehicle Code");
                    $stats['skipped']++;
                    continue;
                }

                // Segment Code
                $segmentCode = $segmentMapping[$rawSegment] 
                            ?? $segmentMapping[str_replace([' ', '_', '-'], '', $rawSegment)] 
                            ?? substr($rawSegment, 0, 5);
                $segmentCode = strtoupper(trim($segmentCode));

                // Safe Codes
                $modelCode      = substr($rawModel, 0, 20);
                $subSegmentCode = !empty($row[8]) ? substr(strtoupper(trim($row[8])), 0, 15) : null;  // increased safety

                // Lookup IDs from maps (with fallback)
                $fuelStr     = strtoupper(trim($row[11] ?? ''));
                $bodyMakeStr = strtoupper(trim($row[17] ?? ''));
                $bodyTypeStr = strtoupper(trim($row[18] ?? ''));
                $permitStr   = strtoupper(trim($row[21] ?? ''));
                $statusStr   = strtoupper(trim($row[23] ?? 'ACTIVE'));

                $fuelTypeId   = $fuelMap[$fuelStr]     ?? null;
                $bodyMakeId   = $bodyMakeMap[$bodyMakeStr] ?? null;
                $bodyTypeId   = $bodyTypeMap[$bodyTypeStr] ?? null;
                $permitId     = $permitMap[$permitStr]   ?? null;
                $statusId     = $statusMap[$statusStr]   ?? null;

                $variantCode = strlen($excelVehicleCode) > 2 
                    ? substr($excelVehicleCode, 0, -2) 
                    : $excelVehicleCode;

                // === Insert Logic (Segment, Subsegment, Model, Variant, Color) ===
                // Segment
                if ($segmentCode && !\DB::table('xlr8_vehicle_segment')
                        ->where('brand_code', $brandCode)->where('code', $segmentCode)->exists()) {
                    \DB::table('xlr8_vehicle_segment')->insert([
                        'brand_code' => $brandCode, 'code' => $segmentCode,
                        'name' => ucfirst(strtolower($segmentCode)), 'is_active' => 1,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $stats['segment']++;
                }

                // Subsegment
                if ($subSegmentCode && $segmentCode && !\DB::table('xlr8_vehicle_subsegment')
                        ->where('brand_code', $brandCode)
                        ->where('segment_code', $segmentCode)
                        ->where('code', $subSegmentCode)->exists()) {
                    \DB::table('xlr8_vehicle_subsegment')->insert([
                        'brand_code' => $brandCode, 'segment_code' => $segmentCode,
                        'code' => $subSegmentCode, 'name' => ucfirst(strtolower($subSegmentCode)),
                        'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $stats['subsegment']++;
                }

                // Model
                if (!\DB::table('xlr8_vehicle_model')->where('code', $modelCode)->exists()) {
                    \DB::table('xlr8_vehicle_model')->insert([
                        'brand_code'       => $brandCode,
                        'segment_code'     => $segmentCode,
                        'sub_segment_code' => $subSegmentCode,
                        'code'             => $modelCode,
                        'name'             => $customModel ?: $modelCode,
                        'oem_name'         => $rawModel,
                        'is_active'        => 1,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                    $stats['model']++;
                }

                // Variant
                if (!\DB::table('xlr8_vehicle_variant')->where('code', $variantCode)->exists()) {
                    \DB::table('xlr8_vehicle_variant')->insert([
                        'brand_code'        => $brandCode,
                        'segment_code'      => $segmentCode,
                        'sub_segment_code'  => $subSegmentCode,
                        'model_code'        => $modelCode,
                        'code'              => $variantCode,
                        'oem_name'          => $oemVariant,
                        'custom_name'       => $customVariant ?: null,
                        'fuel_type_id'      => $fuelTypeId,
                        'seating_capacity'  => is_numeric($row[13]??null) ? (int)$row[13] : null,
                        'wheels'            => is_numeric($row[14]??null) ? (int)$row[14] : 4,
                        'gvw'               => is_numeric($row[20]??null) ? (int)$row[20] : null,
                        'cc_capacity'       => !empty($row[19]) ? (string)$row[19] : null,
                        'transmission'      => strtoupper(trim($row[15]??'')) ?: null,
                        'drivetrain'        => strtoupper(trim($row[16]??'')) ?: null,
                        'body_type_id'      => $bodyTypeId,
                        'body_make_id'      => $bodyMakeId,
                        'permit_id'         => $permitId,
                        'status_id'         => $statusId,
                        'is_csd'            => 0,
                        'is_active'         => 1,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                    $stats['variant']++;
                }

                // Color
                $colorCode = strtoupper(trim($row[9] ?? ''));
                $colorName = trim($row[10] ?? '');
                if ($colorCode && $modelCode) {
                    if (!\DB::table('xlr8_vehicle_color')
                            ->where('model_code', $modelCode)
                            ->where('code', $colorCode)->exists()) {
                        \DB::table('xlr8_vehicle_color')->insert([
                            'brand_code'           => $brandCode,
                            'segment_code'         => $segmentCode,
                            'sub_segment_code'     => $subSegmentCode,
                            'model_code'           => $modelCode,
                            'vehicle_variant_code' => '',
                            'code'                 => $colorCode,
                            'name'                 => $colorName ?: $colorCode,
                            'is_active'            => 1,
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ]);
                        $stats['color']++;
                    }
                }


            } catch (\Exception $e) {
                $stats['skipped']++;
                \Log::error("Row {$excelRow} [{$rawModel}] SKIPPED — Error: " . $e->getMessage());
            }
        }

        \Log::info("=== Vehicle Import Completed ===", $stats);

        $summary = "Import Completed → Models: {$stats['model']}, Variants: {$stats['variant']}, Colors: {$stats['color']}, Skipped: {$stats['skipped']}";
        
        if ($stats['skipped'] > 0) {
            \Alert::warning($summary . '<br>Check laravel.log for details.')->flash();
        } else {
            \Alert::success($summary)->flash();
        }

    } catch (\Exception $e) {
        \Log::error('Vehicle Import failed', ['error' => $e->getMessage()]);
        \Alert::error('Import failed: ' . $e->getMessage())->flash();
    }

    return redirect()->back();
}
/**
 * Build a map of UPPERCASE_KEY => id from a collection of keyvalue rows.
 * Falls back to matching on 'value' (UPPERCASE) when 'key' is null.
 */
private function buildKeyMap(\Illuminate\Support\Collection $rows): array
{
    $map = [];
    foreach ($rows as $kv) {
        // primary index: the stored key (e.g. 'DIESEL', 'PRIVATE')
        if (!empty($kv->key)) {
            $map[strtoupper($kv->key)] = $kv->id;
        }
        // secondary index: the human value (e.g. 'Diesel', 'Private')
        if (!empty($kv->value)) {
            $map[strtoupper($kv->value)] = $kv->id;
        }
    }
    return $map;
}
}

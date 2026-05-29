<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use App\Models\Admin\{Branch, Location, Designation, Department, Division, Vertical};
use App\Models\Vehicle\{Segment, SubSegment, VehicleModel, Variant};

class MastersImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'M_Branch' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[0])) continue;
                        Branch::updateOrInsert(
                            ['code' => strtoupper(trim($row[0]))],
                            ['name' => $row[1] ?? '', 'is_active' => ($row[5] ?? '') === 'Yes']
                        );
                    }
                }
            },

            'M_Location' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[0])) continue;
                        Location::updateOrInsert(
                            ['code' => strtoupper(trim($row[0]))],
                            [
                                'name'        => $row[1] ?? '',
                                'branch_code' => strtoupper(trim($row[2] ?? '')),
                                'is_active'   => ($row[12] ?? '') === 'Yes'
                            ]
                        );
                    }
                }
            },

            'M_Designation' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[1])) continue;
                        Designation::updateOrInsert(
                            ['code' => strtoupper(trim($row[1]))],
                            ['name' => $row[0] ?? '', 'is_active' => ($row[3] ?? '') === 'Yes']
                        );
                    }
                }
            },

            'M_Department' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[0])) continue;
                        Department::updateOrInsert(
                            ['code' => strtoupper(trim($row[0]))],
                            ['name' => $row[1] ?? '', 'is_active' => ($row[2] ?? '') === 'Yes']
                        );
                    }
                }
            },

            'M_Division' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[1])) continue;
                        Division::updateOrInsert(
                            ['code' => strtoupper(trim($row[1]))],
                            [
                                'name'      => $row[3] ?? '',
                                'dept_code' => strtoupper(trim($row[0] ?? '')),
                                'is_active' => ($row[4] ?? '') === 'Yes'
                            ]
                        );
                    }
                }
            },

            'M_Vertical' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[0])) continue;
                        Vertical::updateOrInsert(
                            ['code' => strtoupper(trim($row[0]))],
                            ['name' => $row[1] ?? '', 'is_active' => ($row[2] ?? '') === 'Yes']
                        );
                    }
                }
            },

            // ==================== SEGMENT (with brand_code) ====================
            'M_Segment' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    $defaultBrand = \App\Models\Vehicle\Brand::where('is_active', true)->first();
                    $brandCode = $defaultBrand?->code ?? 'M&M';

                    if (!$defaultBrand) {
                        echo "\n⚠️  [M_Segment] No active brand found. Using fallback: M&M\n";
                    }

                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[0])) continue;
                        Segment::updateOrInsert(
                            ['code' => strtoupper(trim($row[0]))],
                            [
                                'name'       => $row[1] ?? '',
                                'brand_code' => $brandCode,
                                'is_active'  => ($row[2] ?? 'Yes') === 'Yes'
                            ]
                        );
                    }
                }
            },

            // ==================== SUB SEGMENT (with brand_code) ====================
            'M_SubSegment' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    $defaultBrand = \App\Models\Vehicle\Brand::where('is_active', true)->first();
                    $brandCode = $defaultBrand?->code ?? 'M&M';

                    if (!$defaultBrand) {
                        echo "\n⚠️  [M_SubSegment] No active brand found. Using fallback: M&M\n";
                    }

                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[0])) continue;
                        SubSegment::updateOrInsert(
                            ['code' => strtoupper(trim($row[0]))],
                            [
                                'name'         => $row[1] ?? '',
                                'segment_code' => strtoupper(trim($row[2] ?? '')),
                                'brand_code'   => $brandCode,
                                'is_active'    => ($row[3] ?? 'Yes') === 'Yes'
                            ]
                        );
                    }
                }
            },

            // ==================== MODEL (with brand_code) - Ready for future use ====================
            'M_Model' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {
                    $defaultBrand = \App\Models\Vehicle\Brand::where('is_active', true)->first();
                    $brandCode = $defaultBrand?->code ?? 'M&M';

                    foreach ($rows->skip(1) as $row) {
                        if (empty($row[3])) continue; // Model Code is in column 3 (0-based)
                        VehicleModel::updateOrInsert(
                            ['code' => strtoupper(trim($row[3]))],
                            [
                                'name'            => $row[5] ?? $row[4] ?? '',
                                'brand_code'      => strtoupper(trim($row[0] ?? $brandCode)),
                                'segment_code'    => strtoupper(trim($row[1] ?? '')),
                                'sub_segment_code'=> strtoupper(trim($row[2] ?? '')),
                                'is_active'       => ($row[6] ?? 'Yes') === 'Yes'
                            ]
                        );
                    }
                }
            },

            // ==================== VARIANT (with brand_code) - Ready for future use ====================
        //     'M_Variant' => new class implements \Maatwebsite\Excel\Concerns\ToCollection {
        //         public function collection(Collection $rows) {
        //             $defaultBrand = \App\Models\Vehicle\Brand::where('is_active', true)->first();
        //             $brandCode = $defaultBrand?->code ?? 'M&M';

        //             foreach ($rows->skip(1) as $row) {
        //                 if (empty($row[0])) continue;
        //                 Variant::updateOrInsert(
        //                     ['code' => strtoupper(trim($row[0]))],
        //                     [
        //                         'name'            => $row[1] ?? '',
        //                         'brand_code'      => strtoupper(trim($row[1] ?? $brandCode)), // adjust column if needed
        //                         'model_code'      => strtoupper(trim($row[2] ?? '')),
        //                         'segment_code'    => strtoupper(trim($row[3] ?? '')),
        //                         'sub_segment_code'=> strtoupper(trim($row[4] ?? '')),
        //                         'is_active'       => ($row[5] ?? 'Yes') === 'Yes'
        //                     ]
        //                 );
        //             }
        //         }
        //     },
        ];
    }
}
<?php

namespace App\Exports;

use App\Models\Core\Brand;
use App\Models\Core\Color;
use App\Models\Core\Segment;
use App\Models\Core\SubSegment;
use App\Models\Core\Variant;
use App\Models\Core\VehicleModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

/**
 * VehicleDataExport - Export vehicle definitions to Excel
 * 
 * Mirrors the structure of vehicle_definition.xlsx import file
 * Creates one row per variant-color combination
 * 
 * Column Structure (matching importer):
 * Brand | Segment | Sub Segment | OEM Model | Custom Model | 
 * OEM Variant | Custom Variant | Model Code | Colour | Colour Code | 
 * Fuel | Seating | Wheels | CC | GVW | Body Make | Body Type | Permit | Status
 */
class VehicleDataExport implements FromCollection, WithHeadings, WithColumnWidths
{
    private $exportedCount = 0;

    public function collection(): Collection
    {
        $rows = collect();

        // Get all variants with their relationships
        $variants = Variant::with([
            'vehicleModel',
            'vehicleModel.brand',
            'vehicleModel.segment',
            'vehicleModel.subSegment',
            'colors'
        ])->get();

        // echo "\n═══════════════════════════════════════════════════════════════\n";
        // echo "             VEHICLE DATA EXPORT - STARTED\n";
        // echo "═══════════════════════════════════════════════════════════════\n\n";

        // For each variant
        foreach ($variants as $variant) {
            $brand = $variant->vehicleModel->brand;
            $segment = $variant->vehicleModel->segment;
            $subSegment = $variant->vehicleModel->subSegment;
            $vehicleModel = $variant->vehicleModel;

            // Get colors for this variant
            $colors = $variant->colors()->get();

            // If no colors, create one row anyway
            if ($colors->isEmpty()) {
                $rows->push($this->buildRow($brand, $segment, $subSegment, $vehicleModel, $variant, null));
                $this->exportedCount++;
            } else {
                // Create one row per color
                foreach ($colors as $color) {
                    $rows->push($this->buildRow($brand, $segment, $subSegment, $vehicleModel, $variant, $color));
                    $this->exportedCount++;
                }
            }
        }

        // echo "✅ EXPORT COMPLETE\n";
        // echo "   Total Variants Exported: {$this->exportedCount}\n\n";
        // echo "═══════════════════════════════════════════════════════════════\n\n";

        return $rows;
    }

    /**
     * Build a single row of data
     */
    private function buildRow(
        Brand $brand,
        Segment $segment,
        ?SubSegment $subSegment,
        VehicleModel $vehicleModel,
        Variant $variant,
        ?Color $color
    ): array {
        return [
            // Brand
            $this->extractJsonValue($brand->name),

            // Segment
            $this->extractJsonValue($segment->name),

            // Sub Segment
            $subSegment ? $this->extractJsonValue($subSegment->name) : '',

            // OEM Model
            $vehicleModel->name ?? '',

            // Custom Model
            $vehicleModel->custom_name ?? '',

            // OEM Variant
            $variant->name ?? '',

            // Custom Variant
            $variant->custom_name ?? '',

            // Model Code
            $this->generateModelCode($variant->oem_code, $color),

            // Colour
            $color ? $this->extractJsonValue($color->name) : '',

            // Colour Code
            $color ? ($color->code ?? '') : '',

            // Fuel
            $variant->fuelType ? $variant->fuelType->value : '',

            // Seating
            $variant->seating_capacity ?? '',

            // Wheels
            $variant->wheels ?? '',

            // CC
            $variant->cc_capacity ?? '',

            // GVW
            $variant->gvwr ?? '',

            // Body Make
            $variant->bodyMake ? $variant->bodyMake->value : '',

            // Body Type
            $variant->bodyType ? $variant->bodyType->value : '',

            // Permit
            $variant->permit ? $variant->permit->value : '',

            // Status
            $variant->status ? $variant->status->value : 'ACTIVE',
        ];
    }

    /**
     * Extract value from JSON encoded field
     * 
     * Handles both:
     * {"en":"value"} format
     * Direct string format
     */
    private function extractJsonValue(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        // Try to decode JSON
        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded['en'])) {
            return trim($decoded['en']);
        }

        // If not JSON, return as-is
        return trim($value);
    }

    /**
     * Generate Model Code from oem_code and color code
     * 
     * Model Code format: {oem_code}{color_code_first_2_chars}
     * Example: M00101 where M001 is oem_code and 01 is from color code
     */
    private function generateModelCode(?string $oemCode, ?Color $color): string
    {
        if (empty($oemCode)) {
            return '';
        }

        $code = trim($oemCode);

        // If color exists, append first 2 chars of color code
        if ($color && !empty($color->code)) {
            $colorCode = substr($color->code, 0, 2);
            $code .= $colorCode;
        } else {
            $code .= '00'; // Default if no color
        }

        return $code;
    }

    /**
     * Column headings matching the importer structure
     */
    public function headings(): array
    {
        return [
            'Brand',
            'Segment',
            'Sub Segment',
            'OEM Model',
            'Custom Model',
            'OEM Variant',
            'Custom Variant',
            'Model Code',
            'Colour',
            'Colour Code',
            'Fuel',
            'Seating',
            'Wheels',
            'CC',
            'GVW',
            'Body Make',
            'Body Type',
            'Permit',
            'Status',
        ];
    }

    /**
     * Column widths for better readability
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Brand
            'B' => 15, // Segment
            'C' => 15, // Sub Segment
            'D' => 18, // OEM Model
            'E' => 18, // Custom Model
            'F' => 18, // OEM Variant
            'G' => 18, // Custom Variant
            'H' => 12, // Model Code
            'I' => 15, // Colour
            'J' => 12, // Colour Code
            'K' => 12, // Fuel
            'L' => 10, // Seating
            'M' => 10, // Wheels
            'N' => 10, // CC
            'O' => 10, // GVW
            'P' => 15, // Body Make
            'Q' => 15, // Body Type
            'R' => 12, // Permit
            'S' => 12, // Status
        ];
    }
}

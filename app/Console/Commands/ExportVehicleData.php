<?php

namespace App\Console\Commands;

use App\Exports\VehicleDataExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportVehicleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:vehicle-data {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all vehicle data to Excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Generate filename with timestamp
            $timestamp = now()->format('d-m-Y-H-i-s');
            $filename = "VehicleDataExport_{$timestamp}.xlsx";

            // Determine export directory
            if ($this->option('path')) {
                // Custom path provided
                $exportDir = $this->option('path');
            } else {
                // Default: public/exports
                $exportDir = public_path('exports');
            }

            // Normalize path
            $exportDir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $exportDir);
            $exportDir = rtrim($exportDir, DIRECTORY_SEPARATOR);

            // Create directory if needed
            if (!is_dir($exportDir)) {
                if (!@mkdir($exportDir, 0755, true)) {
                    throw new \Exception("Cannot create directory: {$exportDir}");
                }
            }

            if (!is_writable($exportDir)) {
                throw new \Exception("Directory not writable: {$exportDir}");
            }

            $fullPath = $exportDir . DIRECTORY_SEPARATOR . $filename;

            $this->info("\n📊 Starting Vehicle Data Export...\n");
            $this->info("📁 Export Directory: {$exportDir}\n");
            $this->info("📄 Filename: {$filename}\n");

            // Use temp file in public folder
            $tempPath = public_path('exports' . DIRECTORY_SEPARATOR . '.temp_' . $filename);

            // Export to temp file using Maatwebsite
            Excel::store(
                new VehicleDataExport(),
                '.temp_' . $filename,
                'public/exports'
            );

            // Check if temp file exists in the expected location
            if (!file_exists($tempPath)) {
                // Try alternative: write to temp directory and copy
                $sysTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

                Excel::store(
                    new VehicleDataExport(),
                    $sysTemp
                );

                if (!file_exists($sysTemp)) {
                    throw new \Exception("Export file creation failed");
                }

                // Copy from temp to final location
                if (!@copy($sysTemp, $fullPath)) {
                    throw new \Exception("Failed to copy file to {$fullPath}");
                }

                @unlink($sysTemp);
            } else {
                // Rename temp to final
                if (!@rename($tempPath, $fullPath)) {
                    if (!@copy($tempPath, $fullPath)) {
                        throw new \Exception("Failed to finalize export file");
                    }
                    @unlink($tempPath);
                }
            }

            // Verify file was created
            if (!file_exists($fullPath)) {
                throw new \Exception("File creation failed: {$fullPath}");
            }

            $fileSize = filesize($fullPath);
            $fileSizeKB = round($fileSize / 1024, 2);
            $webUrl = url('exports/' . $filename);

            $this->info("✅ Export completed successfully!\n");
            $this->info("📍 Full Path: {$fullPath}\n");
            $this->info("📦 Size: {$fileSizeKB} KB\n");
            $this->info("🌐 Web URL: {$webUrl}\n");
            $this->info("⏰ Timestamp: {$timestamp}\n");

            return 0;
        } catch (\Exception $e) {
            $this->error("\n❌ Export failed:\n");
            $this->error($e->getMessage() . "\n");
            return 1;
        }
    }
}

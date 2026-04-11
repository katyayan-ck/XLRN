<?php

namespace App\Console\Commands;

use App\Imports\VehicleDefineImporter;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportVehicleDefine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vehicle-define
                            {--file= : Path to the Excel file to import}
                            {--force : Force import without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import vehicle definitions from Excel file (Brand → Segment → SubSegment → VehicleModel → Variant + Colors)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->option('file');

        if (!$file) {
            $this->error('File path is required. Use: php artisan import:vehicle-define --file=/path/to/file.xlsx');
            return Command::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        if (!$this->option('force')) {
            $this->warn('This will import vehicle data. Make sure you have a backup of your database.');
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Import cancelled.');
                return Command::FAILURE;
            }
        }

        try {
            $this->info("Starting import from: {$file}");
            $this->newLine();

            Excel::import(new VehicleDefineImporter(), $file);

            $this->info('✓ Import completed successfully!');
            $this->newLine();
            $this->line('Check storage/logs/laravel.log for detailed import logs.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            \Log::error("Vehicle import error: {$e->getMessage()}", [
                'file' => $file,
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}

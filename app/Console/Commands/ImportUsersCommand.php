<?php

namespace App\Console\Commands;

use App\Services\Importers\UserImporter;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportUsersCommand extends Command
{
    protected $signature = 'import:users {file : Path to Excel file to import}
                          {--branch= : Filter by branch code}
                          {--department= : Filter by department code}
                          {--role= : Assign role to all imported users}
                          {--skip-validation : Skip field validation}
                          {--dry-run : Show what would be imported without saving}';

    protected $description = 'Import users from Excel file (Person → Employee → User hierarchy)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filePath = $this->argument('file');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        // Validate file is Excel
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['xlsx', 'xls', 'csv'])) {
            $this->error("Invalid file type. Expected .xlsx, .xls, or .csv");
            return 1;
        }

        try {
            $this->info('==========================================================');
            $this->info('      VDMS USER IMPORT SYSTEM');
            $this->info('==========================================================');
            $this->info('');
            $this->info('File: ' . basename($filePath));
            $this->info('Size: ' . number_format(filesize($filePath) / 1024, 2) . ' KB');
            $this->info('');

            // Start import
            $this->info('Starting import process...');
            $this->info('');

            $progressBar = $this->output->createProgressBar(100);
            $progressBar->setFormat('Progress: [%bar%] %percent%% - %message%');
            $progressBar->setMessage('Reading file...');
            $progressBar->start();

            $importer = new UserImporter($filePath);
            $progressBar->advance(30);
            $progressBar->setMessage('Validating records...');

            $progressBar->advance(20);
            $progressBar->setMessage('Processing imports...');

            $result = $importer->execute();

            $progressBar->advance(40);
            $progressBar->setMessage('Finalizing...');
            $progressBar->finish();

            $this->info('');
            $this->info('');

            // Results
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Imported', $result['imported']],
                    ['Skipped', $result['skipped']],
                    ['Total Processed', $result['imported'] + $result['skipped']],
                    ['Status', $result['success'] ? '✓ SUCCESS' : '✗ FAILED'],
                ]
            );

            // Errors
            if (!empty($result['errors'])) {
                $this->error('');
                $this->error('ERRORS ENCOUNTERED:');
                $this->error('─────────────────────────────────────────────────────');

                foreach ($result['errors'] as $error) {
                    $this->error("Row {$error['row']}: {$error['error']}");
                }
            }

            // Warnings
            if (!empty($result['warnings'])) {
                $this->warn('');
                $this->warn('WARNINGS:');
                $this->warn('─────────────────────────────────────────────────────');

                foreach ($result['warnings'] as $warning) {
                    $this->warn($warning);
                }
            }

            $this->info('');
            $this->info($result['message']);
            $this->info('');

            return $result['success'] ? 0 : 1;
        } catch (Exception $e) {
            $this->error('');
            $this->error('IMPORT FAILED');
            $this->error('─────────────────────────────────────────────────────');
            $this->error('Error: ' . $e->getMessage());
            $this->error('');

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}

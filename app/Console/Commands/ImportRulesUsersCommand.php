<?php

namespace App\Console\Commands;

use App\Services\Importers\RulesUserImporter;
use Exception;
use Illuminate\Console\Command;

class ImportRulesUsersCommand extends Command
{
    protected $signature = 'import:rules-users 
                          {file : Path to Excel file to import}
                          {--sheet=UserList : Sheet name to import from (default: "User List")}
                          {--dry-run : Show what would be imported without saving}';

    protected $description = 'Import users from Rules.xlsx format with smart data handling';

    public function handle()
    {
        $filePath = $this->argument('file');
        $sheetName = $this->option('sheet');
        $dryRun = $this->option('dry-run');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: $filePath");
            return 1;
        }

        // Validate file is Excel
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['xlsx', 'xls'])) {
            $this->error("❌ Invalid file type. Expected .xlsx or .xls");
            return 1;
        }

        try {
            $this->info('═════════════════════════════════════════════════════════════');
            $this->info('      RULES USER IMPORT SYSTEM');
            $this->info('═════════════════════════════════════════════════════════════');
            $this->info('');

            if ($dryRun) {
                $this->warn('⚠️  DRY RUN MODE - No data will be saved');
                $this->info('');
            }

            $this->info('File Details:');
            $this->info("  Path: " . basename($filePath));
            $this->info("  Size: " . number_format(filesize($filePath) / 1024, 2) . " KB");
            $this->info("  Sheet: '{$sheetName}'");
            $this->info('');

            // Start import
            $this->info('Starting import process...');

            $progressBar = $this->output->createProgressBar(100);
            $progressBar->setFormat('Progress: [%bar%] %percent%% (%current%/%max%) - %message%');
            $progressBar->setMessage('Reading file...');
            $progressBar->start();

            $importer = new RulesUserImporter($filePath, $sheetName);
            $progressBar->advance(40);
            $progressBar->setMessage('Processing users...');

            $result = $importer->execute();

            $progressBar->advance(50);
            $progressBar->setMessage('Finalizing...');
            $progressBar->finish();

            $this->info('');
            $this->info('');

            // Results Table
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Processed', $result['total_processed']],
                    ['✓ Imported (New)', $result['imported']],
                    ['↻ Updated (Existing)', $result['updated']],
                    ['⊘ Skipped', $result['skipped']],
                    ['⚠ Errors', count($result['errors'])],
                    ['Duration', $result['duration_seconds'] . 's'],
                    ['Status', $result['success'] ? '✓ SUCCESS' : '✗ FAILED'],
                ]
            );

            // Detailed Results
            $this->info('');
            $this->info('Result Summary:');
            $this->info('─────────────────────────────────────────────────────────────');

            if ($result['imported'] > 0) {
                $this->line("  <fg=green>✓ New Users Created: {$result['imported']}</>");
            }

            if ($result['updated'] > 0) {
                $this->line("  <fg=blue>↻ Existing Users Updated: {$result['updated']}</>");
            }

            if ($result['skipped'] > 0) {
                $this->line("  <fg=yellow>⊘ Records Skipped: {$result['skipped']}</>");
            }

            // Errors
            if (!empty($result['errors'])) {
                $this->error('');
                $this->error('ERRORS ENCOUNTERED:');
                $this->error('─────────────────────────────────────────────────────────────');

                foreach ($result['errors'] as $error) {
                    $this->error("Row {$error['row']}: {$error['error']}");
                }
            }

            // Warnings
            if (!empty($result['warnings'])) {
                $this->warn('');
                $this->warn('WARNINGS:');
                $this->warn('─────────────────────────────────────────────────────────────');

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
            $this->error('❌ IMPORT FAILED');
            $this->error('─────────────────────────────────────────────────────────────');
            $this->error('Error: ' . $e->getMessage());
            $this->error('');

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}

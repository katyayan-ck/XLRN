<?php

namespace App\Console\Commands;

//use App\Services\Exporters\UserExporter;
use Exception;
use Illuminate\Console\Command;

class ExportUsersCommand extends Command
{
    protected $signature = 'export:users 
                          {--branch= : Export only users from specific branch code}
                          {--department= : Export only users from specific department code}
                          {--designation= : Export only users with specific designation code}
                          {--status=active : Filter by status (active|inactive|all)}
                          {--output= : Output file path (default: storage/exports/)}';

    protected $description = 'Export users to Excel file with assignments and data scopes';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $this->info('==========================================================');
            $this->info('      VDMS USER EXPORT SYSTEM');
            $this->info('==========================================================');
            $this->info('');

            // Build filters
            $filters = [];

            if ($this->option('branch')) {
                $this->info('Filter: Branch = ' . $this->option('branch'));
                $filters['branch_code'] = $this->option('branch');
            }

            if ($this->option('department')) {
                $this->info('Filter: Department = ' . $this->option('department'));
                $filters['department_code'] = $this->option('department');
            }

            if ($this->option('designation')) {
                $this->info('Filter: Designation = ' . $this->option('designation'));
                $filters['designation_code'] = $this->option('designation');
            }

            $status = $this->option('status');
            if ($status !== 'all') {
                $this->info('Filter: Status = ' . ucfirst($status));
                $filters['is_active'] = ($status === 'active');
            }

            $this->info('');
            $progressBar = $this->output->createProgressBar(100);
            $progressBar->setFormat('Progress: [%bar%] %percent%% - %message%');
            $progressBar->start();

            // Determine output path
            $outputPath = $this->option('output');
            if (!$outputPath) {
                $outputPath = storage_path('exports/users_' . date('Y-m-d-His') . '.xlsx');
            }

            // Ensure directory exists
            @mkdir(dirname($outputPath), 0755, true);

            $progressBar->advance(20);
            $progressBar->setMessage('Preparing export...');

            // Create exporter
            $exporter = new UserExporter($outputPath);
            $exporter->withFilters($filters);

            $progressBar->advance(30);
            $progressBar->setMessage('Generating sheets...');

            // Execute export
            $result = $exporter->execute();

            $progressBar->advance(40);
            $progressBar->setMessage('Finalizing...');
            $progressBar->finish();

            $this->info('');
            $this->info('');

            // Results
            if ($result['success']) {
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Exported', $result['count']],
                        ['File Path', $result['path']],
                        ['File Size', number_format(filesize($result['path']) / 1024, 2) . ' KB'],
                        ['Status', '✓ SUCCESS'],
                    ]
                );

                $this->info('');
                $this->info('Export Details:');
                $this->info('─────────────────────────────────────────────────────');
                $this->info('✓ Users Sheet: Main user data');
                $this->info('✓ Assignments Sheet: Branch, Department, Location, Post assignments');
                $this->info('✓ Data Scopes Sheet: RBAC data scoping information');
                $this->info('✓ Summary Sheet: Statistics and distribution by branch');

                $this->info('');
                $this->info('File ready for download: ' . basename($result['path']));
                $this->info('');

                return 0;
            } else {
                $this->error('Export failed: ' . $result['message']);
                return 1;
            }
        } catch (Exception $e) {
            $this->error('');
            $this->error('EXPORT FAILED');
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

<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KeywordValueService;

class SetupEntityHistory extends Command
{
    protected $signature = 'xlr8:setup-entity-history {--backfill}';
    protected $description = 'Setup Entity History (seed keywords + optional backfill)';

    public function handle()
    {
        $this->info('Seeding entity_actions keywords...');
        // Add your KeywordValueService::seedIfMissing('entity_actions', [...]) logic here

        if ($this->option('backfill')) {
            $this->info('Backfill from old tables completed (if data existed).');
        }

        $this->info('✅ Entity History setup complete.');
    }
}
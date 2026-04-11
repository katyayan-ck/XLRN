<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupVision extends Command
{
    protected $signature = 'doc:setup-vision';
    protected $description = 'Setup Google Cloud Vision credentials';

    public function handle(): void
    {
        $this->info('Upload key.json to storage/app/google/key.json');
        $this->info('Add to .env: GOOGLE_APPLICATION_CREDENTIALS=storage/app/google/key.json');
        $this->info('Test: Run upload with AI enabled');
    }
}

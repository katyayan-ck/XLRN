<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MastersImport;

class ImportMasters extends Command
{
    protected $signature = 'import:masters {file}';

    protected $description = 'Import Master Data (Branch, Location, Designation, Department, Division, Vertical, Segment, SubSegment)';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Importing master data from: {$file}");

        Excel::import(new MastersImport, $file);

        $this->info("Master data import completed successfully.");
        return 0;
    }
}
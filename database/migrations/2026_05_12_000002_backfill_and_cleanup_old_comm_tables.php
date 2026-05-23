<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Optional: Add your backfill logic here from old tables if needed
        // Example: DB::statement("INSERT INTO xlr8_utils_comm_master ...");

        // After successful backfill, drop old tables
        DB::statement('DROP TABLE IF EXISTS xlr8_utils_communication_master');
        DB::statement('DROP TABLE IF EXISTS xlr8_utils_communication_thread');
    }

    public function down(): void
    {
        // Recreate old tables if rollback needed (rare)
    }
};
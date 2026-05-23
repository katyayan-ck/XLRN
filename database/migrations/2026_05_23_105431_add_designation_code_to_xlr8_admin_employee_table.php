<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            if (!Schema::hasColumn('xlr8_admin_employee', 'designation_code')) {
                $table->string('designation_code', 10)->nullable()->after('desig_code')
                      ->comment('Soft ref → xlr8_admin_designation.code (new clean column for Designation-as-Role architecture)');
            }
        });

        // One-time backfill from existing desig_code (safe, idempotent)
        DB::table('xlr8_admin_employee')
            ->whereNull('designation_code')
            ->whereNotNull('desig_code')
            ->update([
                'designation_code' => DB::raw('desig_code'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            $table->dropColumn('designation_code');
        });
    }
};
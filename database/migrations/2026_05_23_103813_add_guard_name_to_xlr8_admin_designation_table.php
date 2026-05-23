<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xlr8_admin_designation', function (Blueprint $table) {
            if (!Schema::hasColumn('xlr8_admin_designation', 'guard_name')) {
                $table->string('guard_name', 255)->default('web')->after('name');
            }
        });

        // Backfill existing designations
        DB::table('xlr8_admin_designation')
            ->whereNull('guard_name')
            ->update(['guard_name' => 'web']);
    }

    public function down(): void
    {
        Schema::table('xlr8_admin_designation', function (Blueprint $table) {
            $table->dropColumn('guard_name');
        });
    }
};
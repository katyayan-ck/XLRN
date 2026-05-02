<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Disable FK checks temporarily just in case legacy constraints exist
        Schema::disableForeignKeyConstraints();

        $legacyTables = [
            'xcelr8_us_branch', 'xcelr8_us_location', 'xcelr8_vehicle_master',
            'xcore_colors', 'xcore_models', 'xcore_subbranches', 'xcore_subsegments', 'xcore_variants',
            'xcore_user_departments', 'xcore_user_designations', 'xcore_user_locations', 
            'xcore_user_models', 'xcore_user_reportings', 'xcore_user_segments', 
            'xcore_user_service_branches', 'xcore_user_service_locations', 
            'xcore_user_spare_branches', 'xcore_user_spare_locations', 
            'xcore_user_subbranches', 'xcore_user_subsegments', 
            'xcore_user_sub_departments', 'xcore_user_variants', 'xcore_user_verticals',
            'bmpl_enum_master' // Replaced by KeywordValue/KeyValue system
        ];

        foreach ($legacyTables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};

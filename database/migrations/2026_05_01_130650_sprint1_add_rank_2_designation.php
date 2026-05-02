<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FIXED: singular 'xlr8_admin_designation' not plural 'xlr8_admin_designation'
        Schema::table('xlr8_admin_designation', function (Blueprint $table) {
            $table->string('desig_code', 10)
                  ->nullable()
                  ->after('id')
                  ->comment('Short code used across all org tables e.g. FSC, SM, BM');

            $table->unsignedTinyInteger('rank')
                  ->default(0)
                  ->after('hierarchy_level')
                  ->comment('Rank within hierarchy_level for tie-breaking');

            $table->string('category', 30)
                  ->nullable()
                  ->after('rank')
                  ->comment('e.g. Sales, Service, Admin, Management');
        });
    }

    public function down(): void
    {
        Schema::table('xlr8_admin_designation', function (Blueprint $table) {
            $table->dropColumn(['desig_code', 'rank', 'category']);
        });
    }
};

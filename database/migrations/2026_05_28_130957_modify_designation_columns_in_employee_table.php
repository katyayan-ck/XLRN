<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Increases the length of legacy + new designation columns
     * to support longer codes like AST_ACC_MGR, ACS_MGR, etc.
     */
    public function up(): void
    {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            // Legacy column (used during transition)
            $table->string('desig_code', 20)->nullable()->change();

            // New preferred column
            $table->string('designation_code', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            // Revert to smaller size (adjust if your original size was different)
            $table->string('desig_code', 10)->nullable()->change();
            $table->string('designation_code', 10)->nullable()->change();
        });
    }
};
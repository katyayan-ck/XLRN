<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix color unique constraint: vehicle_model_id + code should be unique instead of just code
        Schema::table('colors', function (Blueprint $table) {
            // Drop the old incorrect unique constraint
            $table->dropUnique('colors_code_unique');

            // Add the correct unique constraint: vehicle_model_id + code
            $table->unique(['vehicle_model_id', 'code'], 'colors_vehicle_model_id_code_unique');
        });

        // Add audit columns to variant_colors pivot table
        Schema::table('variant_colors', function (Blueprint $table) {
            // Add audit timestamp columns
            $table->softDeletes()->after('updated_at');

            // Add audit user tracking columns
            $table->unsignedBigInteger('created_by')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');

            // Add foreign key constraints for audit columns
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_colors', function (Blueprint $table) {
            $table->dropForeignKey(['created_by']);
            $table->dropForeignKey(['updated_by']);
            $table->dropForeignKey(['deleted_by']);
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by', 'deleted_at']);
        });

        Schema::table('colors', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('colors_vehicle_model_id_code_unique');

            // Restore the old one if needed
            $table->unique('code', 'colors_code_unique');
        });
    }
};

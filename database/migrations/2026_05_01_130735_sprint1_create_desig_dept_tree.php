<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_admin_desig_dept_tree', function (Blueprint $table) {
            $table->id();
            $table->string('tree_code', 20)->unique()
                  ->comment('e.g. SLS-SHW-FSC or SLS-BM');
            $table->string('desig_code',    10);
            $table->string('dept_code',     10);
            $table->string('div_code',      10)->nullable();
            $table->string('reports_to_code', 20)->nullable()
                  ->comment('Self-ref via tree_code — Eloquent only, no FK');
            $table->string('display_name',  100)->nullable();
            $table->tinyInteger('level')->default(1)
                  ->comment('1=entry, higher=senior');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index('tree_code',                    'idx_ddt_tree_code');
            $table->index('desig_code',                   'idx_ddt_desig_code');
            $table->index(['dept_code','div_code'],        'idx_ddt_dept_div');
            $table->index('reports_to_code',              'idx_ddt_reports_to');
            $table->index('level',                        'idx_ddt_level');
            $table->index('is_active',                    'idx_ddt_active');
            $table->index('created_by',                   'idx_ddt_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_admin_desig_dept_tree');
    }
};

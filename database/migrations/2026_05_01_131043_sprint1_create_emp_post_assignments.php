<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_admin_emp_post_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('emp_code',  20)
                  ->comment('Eloquent-only link to xlr8_admin_employees.emp_code');
            $table->string('post_code', 30)
                  ->comment('Eloquent-only link to xlr8_iam_roles.post_code');
            $table->enum('assignment_type', ['primary','additional'])->default('primary');
            $table->date('from_date');
            $table->date('to_date')->nullable()
                  ->comment('NULL = currently active');
            $table->enum('relieving_type', [
                'onboarding','transfer','promotion','demotion',
                'additional_charge','charge_relieved',
                'relieving','termination','reassignment',
            ])->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('relieved_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // All query patterns covered by indexes
            $table->index(['post_code','to_date'],              'idx_epa_post_current');
            $table->index(['emp_code','to_date'],               'idx_epa_emp_current');
            $table->index(['emp_code','from_date'],             'idx_epa_emp_journey');
            $table->index(['post_code','from_date','to_date'],  'idx_epa_post_date_range');
            $table->index('assignment_type',                    'idx_epa_type');
            $table->index('relieving_type',                     'idx_epa_relieving');
            $table->index('created_by',                         'idx_epa_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_admin_emp_post_assignments');
    }
};

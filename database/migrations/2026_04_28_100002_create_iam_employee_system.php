<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_admin_employee', function (Blueprint $table) {
            $table->id();

            // ── Natural key ──────────────────────────────────────────────────
            $table->string('code', 20)->unique()
                  ->comment('Employee code e.g. BMPL-0001. Used as FK everywhere.');

            // ── Person linkage ───────────────────────────────────────────────
            $table->string('person_code', 20)
                  ->comment('Soft ref → xlr8_admin_person.person_code');

            // ── Org assignment ───────────────────────────────────────────────
            $table->string('desig_code',          10)->nullable()
                  ->comment('Ref → xlr8_admin_designation.code');
            $table->string('primary_branch_code',  5)->nullable()
                  ->comment('Ref → xlr8_admin_branch.code');
            $table->string('primary_dept_code',   10)->nullable()
                  ->comment('Ref → xlr8_admin_department.code');
            $table->string('primary_div_code',    10)->nullable()
                  ->comment('Ref → xlr8_admin_division.code');
            $table->string('primary_loc_code',     5)->nullable()
                  ->comment('Physical work location ref → xlr8_admin_location.code');

            // ── Default vehicle scope ────────────────────────────────────────
            $table->string('vertical_code',      10)->nullable()
                  ->comment('e.g. NV, UV');
            $table->string('segment_code',        5)->nullable()
                  ->comment('e.g. BEV, PER, COM');
            $table->string('sub_segment_code',    5)->nullable()
                  ->comment('e.g. XUV, NON-XUV');

            // ── OEM ──────────────────────────────────────────────────────────
            $table->string('oem_id', 50)->nullable()
                  ->comment('OEM / manufacturer portal employee ID');

            // ── Employment ───────────────────────────────────────────────────
            $table->enum('employment_type', [
                'permanent', 'probation', 'apprentice', 'contract', 'temporary',
            ])->default('permanent');

            $table->enum('employment_status', [
                'active', 'inactive', 'separated', 'terminated', 'absconded',
            ])->default('active');

            $table->date('joining_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('separation_date')->nullable();
            $table->string('separation_reason', 150)->nullable();

            // ── Personal (employee-specific beyond person master) ─────────────
            $table->string('blood_group',    5)->nullable();
            $table->string('nationality',   50)->nullable()->default('Indian');
            $table->string('father_name',  100)->nullable();
            $table->string('mother_name',  100)->nullable();
            $table->string('passport_no',   20)->nullable();
            $table->tinyInteger('no_of_children')->unsigned()->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('biometric_id', 20)->nullable();

            // ── Official contact (work — separate from personal person contacts) ─
            $table->string('official_mobile',  15)->nullable();
            $table->string('official_email',  100)->nullable();

            // ── Payroll — PF ──────────────────────────────────────────────────
            $table->boolean('pf_eligible')->default(false);
            $table->enum('pf_reg_type', ['new', 'existing'])->nullable();
            $table->string('pf_number',    30)->nullable();
            $table->string('uan_number',   20)->nullable()->unique();
            $table->date('pf_joining_date')->nullable();
            $table->boolean('eps_membership')->default(false);
            $table->boolean('abry_eligible')->default(false);

            // ── Payroll — ESI ─────────────────────────────────────────────────
            $table->boolean('esi_eligible')->default(false);
            $table->string('esi_number', 20)->nullable()->unique();

            // ── Payroll — PT / LWF ───────────────────────────────────────────
            $table->string('pt_establishment_id', 30)->nullable();
            $table->boolean('lwf_eligible')->default(false);

            // ── Salary ────────────────────────────────────────────────────────
            $table->enum('salary_payment_mode', ['bank', 'cash', 'cheque'])
                  ->default('bank');
            $table->enum('salary_structure_type', [
                'statutory_limit', 'above_statutory_limit',
            ])->default('statutory_limit');

            // ── Shift ─────────────────────────────────────────────────────────
            $table->enum('shift_type', ['flexible', 'fixed'])->default('flexible');
            $table->string('shift_name', 50)->nullable()
                  ->comment('e.g. Open | 08:45 AM TO 07:00 PM');
            $table->smallInteger('late_arrival_window')->unsigned()->nullable()->default(30)
                  ->comment('Allowed late arrival in minutes');
            $table->smallInteger('early_going_window')->unsigned()->nullable()->default(15)
                  ->comment('Allowed early exit in minutes');
            $table->string('leave_rule', 50)->nullable();
            $table->string('week_off',   30)->nullable()->default('Sunday');
            $table->boolean('wo_work_compensation')->default(false)
                  ->comment('Whether week-off working earns compensatory off');
            $table->boolean('comp_off_applicable')->default(false);

            // ── Reporting manager ─────────────────────────────────────────────
            $table->string('reporting_emp_code', 20)->nullable()
                  ->comment('Default reporting manager employee code');

            // ── Audit ─────────────────────────────────────────────────────────
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ───────────────────────────────────────────────────────
            $table->index('person_code');
            $table->index('primary_branch_code');
            $table->index('primary_dept_code');
            $table->index('primary_div_code');
            $table->index('desig_code');
            $table->index('employment_status');
            $table->index('employment_type');
            $table->index('reporting_emp_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_admin_employee');
    }
};

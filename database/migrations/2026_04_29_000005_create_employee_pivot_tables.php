<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Employee ↔ Branch ─────────────────────────────────────
        Schema::create('xlr8_admin_emp_branch_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('branch_code', 5);
            $t->enum('assignment_type', ['primary','additional','inherited'])->default('additional');
            $t->date('from_date')->nullable();
            $t->date('to_date')->nullable();
            $t->boolean('is_current')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'branch_code'], 'uq_emp_branch');
            $t->foreign('employee_code', 'fk_emp_branch_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
            $t->foreign('branch_code', 'fk_emp_branch_br')
              ->references('code')->on('xlr8_admin_branch')->cascadeOnUpdate();
        });

        // ── Employee ↔ Department ─────────────────────────────────
        Schema::create('xlr8_admin_emp_department_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('dept_code', 10);
            $t->string('division_code', 15)->nullable();
            $t->enum('assignment_type', ['primary','secondary'])->default('primary');
            $t->boolean('is_current')->default(true);
            $t->date('from_date')->nullable();
            $t->date('to_date')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'dept_code'], 'uq_emp_dept');
            $t->foreign('employee_code', 'fk_emp_dept_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
            $t->foreign('dept_code', 'fk_emp_dept_dept')
              ->references('code')->on('xlr8_admin_department')->cascadeOnUpdate();
        });

        // ── Employee ↔ Location ───────────────────────────────────
        Schema::create('xlr8_admin_emp_location_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('location_code', 10);
            $t->string('branch_code', 5);
            $t->boolean('is_primary_work')->default(false);
            $t->enum('assignment_type', ['explicit','inherited','excluded'])->default('explicit');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'location_code'], 'uq_emp_loc');
            $t->foreign('employee_code', 'fk_emp_loc_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
        });

        // ── Employee ↔ Vertical ───────────────────────────────────
        Schema::create('xlr8_admin_emp_vertical_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('vertical_code', 10);
            $t->boolean('is_current')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'vertical_code'], 'uq_emp_vert');
            $t->foreign('employee_code', 'fk_emp_vert_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
        });

        // ── Employee ↔ Segment ────────────────────────────────────
        Schema::create('xlr8_admin_emp_segment_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('segment_code', 10);
            $t->boolean('is_current')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'segment_code'], 'uq_emp_seg');
            $t->foreign('employee_code', 'fk_emp_seg_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
        });

        // ── Employee ↔ Sub-Segment ────────────────────────────────
        Schema::create('xlr8_admin_emp_sub_segment_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('sub_segment_code', 10);
            $t->boolean('is_current')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'sub_segment_code'], 'uq_emp_subseg');  // ← explicit short name
            $t->foreign('employee_code', 'fk_emp_subseg_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
        });

        // ── Employee ↔ Post ───────────────────────────────────────
        Schema::create('xlr8_iam_emp_post_pivot', function (Blueprint $t) {
            $t->id();
            $t->string('employee_code', 20);
            $t->string('post_code', 30);
            $t->boolean('is_current')->default(true);
            $t->date('from_date')->nullable();
            $t->date('to_date')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->unique(['employee_code', 'post_code'], 'uq_emp_post');
            $t->foreign('employee_code', 'fk_emp_post_emp')
              ->references('code')->on('xlr8_admin_employee')->cascadeOnDelete();
            $t->foreign('post_code', 'fk_emp_post_post')
              ->references('code')->on('xlr8_iam_post')->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_iam_emp_post_pivot');
        Schema::dropIfExists('xlr8_admin_emp_sub_segment_pivot');
        Schema::dropIfExists('xlr8_admin_emp_segment_pivot');
        Schema::dropIfExists('xlr8_admin_emp_vertical_pivot');
        Schema::dropIfExists('xlr8_admin_emp_location_pivot');
        Schema::dropIfExists('xlr8_admin_emp_department_pivot');
        Schema::dropIfExists('xlr8_admin_emp_branch_pivot');
    }
};
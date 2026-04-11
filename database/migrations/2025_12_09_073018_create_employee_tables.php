<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employees Table
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // BMPL-EMP-001
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('primary_branch_id');
            $table->unsignedBigInteger('primary_department_id');

            // Employment details
            $table->date('joining_date');
            $table->date('resignation_date')->nullable();
            $table->enum('employment_type', ['permanent', 'contract', 'temporary', 'probation'])->default('permanent');
            $table->string('employment_status')->default('active'); // active, on_leave, resigned, retired

            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('person_id');
            $table->index('is_active');
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->restrictOnDelete();
            $table->foreign('primary_branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('primary_department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Posts Table (Job positions as roles)
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // SALES-MGR-PERS-001
            $table->string('title');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('reports_to_post_id')->nullable();

            $table->integer('max_assignees')->default(1);
            $table->integer('current_assignees')->default(0);
            $table->boolean('is_vacant')->default(false);
            $table->timestamp('vacancy_published_at')->nullable();

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('department_id');
            $table->index('is_active');
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('reports_to_post_id')->references('id')->on('posts')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Post Permissions (RBAC)
        Schema::create('post_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('permission_id'); // From Spatie permissions
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('granted_at')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'permission_id']);
            $table->index('post_id');
            $table->index('permission_id');
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete(); // Spatie
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Employee-Post Assignments
        Schema::create('employee_post_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('post_id');

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->integer('assignment_order')->default(1);
            $table->boolean('is_current')->default(true);

            $table->text('remarks')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_current']);
            $table->index('post_id');
            $table->index('from_date');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Employee-Department Assignments
        Schema::create('employee_department_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('department_id');

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_current')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_current']);
            $table->index('department_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Employee-Branch Assignments
        Schema::create('employee_branch_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_current')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_current']);
            $table->index('branch_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Employee-Location Assignments
        Schema::create('employee_location_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('branch_id');

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_current')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_current']);
            $table->index('location_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Employee-Vertical Assignments
        Schema::create('employee_vertical_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('vertical_id');

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_current')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_current']);
            $table->index('vertical_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('vertical_id')->references('id')->on('verticals')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_vertical_assignments');
        Schema::dropIfExists('employee_location_assignments');
        Schema::dropIfExists('employee_branch_assignments');
        Schema::dropIfExists('employee_department_assignments');
        Schema::dropIfExists('employee_post_assignments');
        Schema::dropIfExists('post_permissions');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('employees');
    }
};

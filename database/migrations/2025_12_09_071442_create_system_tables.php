<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System Settings Table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // app_name, timezone, etc.
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json, array
            $table->text('description')->nullable();
            $table->boolean('is_editable')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Branches Table
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // BKN, JPR
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('country')->default('India');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Locations Table (Tehsil/Showroom under branch)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('code')->unique(); // BKN-LOC-001
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('branch_id');
            $table->index('is_active');
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Departments Table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // SALES, HR
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_department_id')->nullable(); // Hierarchical
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('head_id')->nullable(); // Person ID as head
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('parent_department_id');
            $table->index('branch_id');
            $table->index('is_active');
            $table->foreign('parent_department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            //$table->foreign('head_id')->references('id')->on('persons')->nullOnDelete(); // Assuming persons table exists later, but order it before if needed
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Divisions Table (Sub-departments)
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('code')->unique(); // SALES-PERS
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('head_id')->nullable(); // Person ID
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
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            //$table->foreign('head_id')->references('id')->on('persons')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Designations Table (CEO, Manager, etc.)
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // CEO, MGR
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('hierarchy_level')->default(0); // For ordering
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Verticals Table (Personal, Commercial)
        Schema::create('verticals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PERS, COMM
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verticals');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('system_settings');
    }
};

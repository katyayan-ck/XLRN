<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User Types Table (Enum of user types)
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // admin, employee, customer, vendor
            $table->string('display_name');
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

        // Update Users Table (Laravel default with additions)
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_type_id')->nullable()->after('id');
            $table->unsignedBigInteger('person_id')->nullable()->after('user_type_id');
            $table->unsignedBigInteger('employee_id')->nullable()->after('person_id');
            $table->unsignedBigInteger('mile_id')->nullable()->after('employee_id');
            $table->string('code')->unique()->after('mile_id');
            $table->string('avatar')->nullable()->after('code');
            $table->string('mobile')->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('last_login_at');
            $table->softDeletes()->after('deleted_by');

            $table->index('person_id');
            $table->index('employee_id');
            $table->index('user_type_id');
            $table->index('code');
            $table->index('mile_id');
            $table->index('is_active');

            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('user_type_id')->references('id')->on('user_types')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // User-Role Multi Assignments Table (Users can have multiple roles)
        Schema::create('user_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id'); // From Spatie roles

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('remarks')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'role_id', 'from_date']);
            $table->index('user_id');
            $table->index('role_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete(); // Spatie
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // User-Division Assignments (For division-based access control)
        Schema::create('user_division_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('division_id');

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_current')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'division_id']);
            $table->index('user_id');
            $table->index('division_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('division_id')->references('id')->on('divisions')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_division_assignments');
        Schema::dropIfExists('user_role_assignments');
        Schema::dropIfExists('user_types');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['user_type_id']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['person_id', 'employee_id', 'user_type_id', 'code', 'avatar', 'phone', 'is_active', 'last_login_at', 'deleted_by']);
            $table->dropSoftDeletes();
        });
    }
};

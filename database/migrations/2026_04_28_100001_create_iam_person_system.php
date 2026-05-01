<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // 1. xlr8_admin_person
        // =====================================================================
        Schema::create('xlr8_admin_person', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 20)->unique()
                  ->comment('Immutable natural key. PAN/Aadhaar for individual; PAN/TAN for legal entity.');

            $table->enum('entity_type', ['individual', 'legal_entity'])
                  ->default('individual');

            $table->string('salutation',   10)->nullable();
            $table->string('first_name',   60)->nullable();
            $table->string('middle_name',  60)->nullable();
            $table->string('last_name',    60)->nullable();
            $table->string('display_name',120)->nullable();

            $table->enum('gender', ['Male', 'Female', 'Other', 'Prefer not to say'])->nullable();
            $table->date('dob')->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
            $table->string('spouse_name', 100)->nullable();
            $table->string('occupation',   80)->nullable();

            $table->string('aadhaar_no', 20)->nullable()->unique();
            $table->string('pan_no',     15)->nullable()->unique();
            $table->string('tan_no',     15)->nullable()->unique()
                  ->comment('For legal entities / deductors');
            $table->string('gst_no',     20)->nullable()->unique();

            $table->json('extra_data')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('entity_type');
            $table->index('first_name');
            $table->index('last_name');
        });

        // =====================================================================
        // 2. xlr8_admin_person_contacts
        // =====================================================================
        Schema::create('xlr8_admin_person_contacts', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 20)
                  ->comment('Soft ref → xlr8_admin_person.person_code');

            $table->enum('data_type', ['Mobile', 'Email', 'Landline', 'Fax']);

            $table->enum('contact_type', ['Primary', 'Alternate', 'Office', 'Home', 'Emergency'])
                  ->comment('Only ONE Primary allowed per (person_code, data_type).');

            $table->string('contact_detail', 100)
                  ->comment('Actual phone number or email address');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Core business rule: one Primary per person per data_type
            $table->unique(
                ['person_code', 'data_type', 'contact_type'],
                'uq_person_contact_data_type'
            );

            $table->index('person_code');
            $table->index(['person_code', 'data_type']);
            $table->index('contact_detail');
        });

        // =====================================================================
        // 3. xlr8_admin_person_addresses
        // =====================================================================
        Schema::create('xlr8_admin_person_addresses', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 20)
                  ->comment('Soft ref → xlr8_admin_person.person_code');

            $table->enum('address_type', ['Primary', 'Office', 'Home', 'Alternate', 'Permanent'])
                  ->default('Primary')
                  ->comment('One Primary per person_code.');

            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();
            $table->string('landmark',        80)->nullable();
            $table->string('city',            60)->nullable();
            $table->string('taluka',          60)->nullable();
            $table->string('district',        60)->nullable();
            $table->string('state',           60)->nullable();
            $table->string('country',         60)->nullable()->default('India');
            $table->string('pincode',         10)->nullable();
            $table->decimal('latitude',  10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['person_code', 'address_type'], 'uq_person_address_type');
            $table->index('person_code');
            $table->index('pincode');
        });

        // =====================================================================
        // 4. xlr8_admin_person_banking_details
        // =====================================================================
        Schema::create('xlr8_admin_person_banking_details', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 20)
                  ->comment('Soft ref → xlr8_admin_person.person_code');

            $table->enum('account_type', ['Primary', 'Secondary', 'Joint', 'Trust'])
                  ->default('Primary')
                  ->comment('One Primary per person_code.');

            $table->string('bank_name',            80)->nullable();
            $table->string('branch_name',          80)->nullable();
            $table->string('account_number',       30)->nullable();
            $table->string('account_holder_name', 100)->nullable();
            $table->string('ifsc_code',            15)->nullable();
            $table->string('micr_code',            10)->nullable();
            $table->enum('account_nature', ['Savings', 'Current', 'Salary', 'NRO', 'NRE'])
                  ->nullable()->default('Savings');

            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['person_code', 'account_type'], 'uq_person_bank_account_type');
            $table->index('person_code');
            $table->index('ifsc_code');
        });

        // =====================================================================
        // 5. users
        // =====================================================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('username', 60)->unique()
                  ->comment('Login handle. Immutable after creation.');

            $table->string('password')
                  ->comment('bcrypt hash');

            $table->enum('user_type', ['Emp', 'Cust', 'DSA', 'Insurer', 'Associate'])
                  ->default('Emp');

            $table->string('person_code', 20)->nullable()
                  ->comment('Soft ref → xlr8_admin_person.person_code');

            $table->string('employee_code', 20)->nullable()
                  ->comment('Soft ref → xlr8_admin_employee.code. Null for non-Emp users.');

            $table->unsignedBigInteger('user_type_id')->nullable()
                  ->comment('Legacy ref to xlr8_iam_user_type. Keep until RBAC fully migrated.');

            $table->string('avatar', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();   // adds remember_token VARCHAR(100) NULL
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('person_code');
            $table->index('employee_code');
            $table->index('user_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('xlr8_admin_person_banking_details');
        Schema::dropIfExists('xlr8_admin_person_addresses');
        Schema::dropIfExists('xlr8_admin_person_contacts');
        Schema::dropIfExists('xlr8_admin_person');
    }
};

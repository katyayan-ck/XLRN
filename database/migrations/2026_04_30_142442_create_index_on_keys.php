<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // IAM & Employee Pivot Indexes
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            $table->index('code', 'idx_emp_code');
            $table->index('person_code', 'idx_emp_person_code');
            $table->index('desig_code', 'idx_emp_desig_code');
            $table->index('primary_branch_code', 'idx_emp_branch');
            $table->index('primary_dept_code', 'idx_emp_dept');
        });

        Schema::table('xlr8_admin_person', function (Blueprint $table) {
            $table->index('person_code', 'idx_person_code');
            $table->index('pan_no', 'idx_person_pan');
            $table->index('aadhaar_no', 'idx_person_aadhaar');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('person_code', 'idx_user_person');
            $table->index('employee_code', 'idx_user_emp');
        });

        // Vehicle System Indexes
        Schema::table('xlr8_vehicle_model', function (Blueprint $table) {
            $table->index('code', 'idx_veh_mdl_code');
            $table->index('brand_code', 'idx_veh_mdl_brand');
        });

        Schema::table('xlr8_vehicle_variant', function (Blueprint $table) {
            $table->index('code', 'idx_veh_var_code');
            $table->index('model_code', 'idx_veh_var_model');
        });
    }

    public function down(): void {
        // Drop logic here...
    }
};

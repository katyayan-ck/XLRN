<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy employee pivot tables (replaced by xlr8_admin_user_scopes)
        Schema::dropIfExists('xlr8_admin_emp_branch_pivot');
        Schema::dropIfExists('xlr8_admin_emp_department_pivot');
        Schema::dropIfExists('xlr8_admin_emp_location_pivot');
        Schema::dropIfExists('xlr8_admin_emp_segment_pivot');
        Schema::dropIfExists('xlr8_admin_emp_sub_segment_pivot');
        Schema::dropIfExists('xlr8_admin_emp_vertical_pivot');
        Schema::dropIfExists('xlr8_admin_emp_division_pivot');

        // Legacy IAM pivots (no longer used)
        Schema::dropIfExists('xlr8_iam_user_division_pivot');
        Schema::dropIfExists('xlr8_iam_user_role_pivot');
        Schema::dropIfExists('xlr8_iam_user_data_scopes');
        Schema::dropIfExists('xlr8_utils_enum_columns');
    }

    public function down(): void
    {
        // We do not recreate legacy tables.
        // Restore from backup if rollback is needed.
    }
};
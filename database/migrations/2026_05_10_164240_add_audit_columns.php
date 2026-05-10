<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'xlr8_admin_emp_branch_pivot',
            'xlr8_admin_emp_location_pivot',
            'xlr8_admin_emp_department_pivot',
            'xlr8_admin_emp_division_pivot',
            'xlr8_admin_emp_post_assignments',
            'xlr8_admin_emp_segment_pivot',
            'xlr8_admin_emp_sub_segment_pivot',
            'xlr8_admin_emp_vertical_pivot',
            'xlr8_iam_user_division_pivot',
            'xlr8_iam_user_role_pivot',
            'variant_colors',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tbl) use ($table) {
                    if (!Schema::hasColumn($table, 'created_at')) $tbl->timestamp('created_at')->nullable();
                    if (!Schema::hasColumn($table, 'updated_at')) $tbl->timestamp('updated_at')->nullable();
                    if (!Schema::hasColumn($table, 'deleted_at')) $tbl->softDeletes();
                    if (!Schema::hasColumn($table, 'created_by')) $tbl->unsignedBigInteger('created_by')->nullable();
                    if (!Schema::hasColumn($table, 'updated_by')) $tbl->unsignedBigInteger('updated_by')->nullable();
                    if (!Schema::hasColumn($table, 'deleted_by')) $tbl->unsignedBigInteger('deleted_by')->nullable();
                });
            }
        }
    }
};
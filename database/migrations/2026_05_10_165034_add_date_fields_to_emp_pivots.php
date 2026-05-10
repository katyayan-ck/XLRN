<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pivots = [
            'xlr8_admin_emp_branch_pivot',
            'xlr8_admin_emp_location_pivot',
            'xlr8_admin_emp_department_pivot',
            'xlr8_admin_emp_division_pivot',
            'xlr8_admin_emp_segment_pivot',
            'xlr8_admin_emp_sub_segment_pivot',
            'xlr8_admin_emp_vertical_pivot',
        ];

        foreach ($pivots as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    if (!Schema::hasColumn($table, 'from_date')) $blueprint->date('from_date')->nullable();
                    if (!Schema::hasColumn($table, 'to_date')) $blueprint->date('to_date')->nullable();
                });
            }
        }
    }
};
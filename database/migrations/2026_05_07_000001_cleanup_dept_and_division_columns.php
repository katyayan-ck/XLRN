<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master cleanup migration:
 *
 * 1. xlr8_admin_department
 *    - Drop duplicate column `dept_code` (same value as `code`)
 *    - Drop its index `idxxlr8admindepartmentdeptcode`
 *
 * 2. xlr8_admin_division
 *    - Drop duplicate column `div_code` (same value as `code`)
 *    - Drop FK-style `department_id` (id-based relation replaced by code-based)
 *    - Drop their indexes
 *    - Add `dept_code` varchar(10) (code-based soft reference to xlr8_admin_department.code)
 *    - Add index on new `dept_code` column
 *
 * Safe to run multiple times — all drops check existence first.
 */
return new class extends Migration
{
    public function up(): void
    {

        // ── Step 2: xlr8_admin_department — drop dept_code ───────────────────────
        Schema::table('xlr8_admin_department', function (Blueprint $table) {
            // Drop the index first (MySQL requires this before dropping the column)
            if ($this->indexExists('xlr8_admin_department', 'idxxlr8admindepartmentdeptcode')) {
                $table->dropIndex('idxxlr8admindepartmentdeptcode');
            }
            if ($this->columnExists('xlr8_admin_department', 'dept_code')) {
                $table->dropColumn('dept_code');
            }
        });

        // ── Step 3: xlr8_admin_division — add dept_code before dropping old cols ─
        // Add first so the back-fill UPDATE above already populated it.
        if (!$this->columnExists('xlr8_admin_division', 'dept_code')) {
            Schema::table('xlr8_admin_division', function (Blueprint $table) {
                $table->string('dept_code', 10)
                      ->nullable()
                      ->after('id')
                      ->comment('Code-based soft ref → xlr8_admin_department.code');
            });
        }

        // ── Step 4: xlr8_admin_division — drop div_code and department_id ────────
        Schema::table('xlr8_admin_division', function (Blueprint $table) {
            // Drop div_code index then column
            if ($this->indexExists('xlr8_admin_division', 'idxxlr8admindivisiondivcode')) {
                $table->dropIndex('idxxlr8admindivisiondivcode');
            }
            if ($this->columnExists('xlr8_admin_division', 'div_code')) {
                $table->dropColumn('div_code');
            }

            // Drop department_id index then column
            if ($this->indexExists('xlr8_admin_division', 'divisionsdepartmentidindex')) {
                $table->dropIndex('divisionsdepartmentidindex');
            }
            if ($this->indexExists('xlr8_admin_division', 'idxdivdept')) {
                $table->dropIndex('idxdivdept');
            }
            if ($this->columnExists('xlr8_admin_division', 'department_id')) {
                $table->dropColumn('department_id');
            }
        });
		
		 Schema::table('xlr8_admin_designation', function (Blueprint $table) {
            // Drop div_code index then column
            if ($this->indexExists('xlr8_admin_designation', 'idxxlr8admindesignationdesigcode')) {
                $table->dropIndex('idxxlr8admindesignationdesigcode');
            }
            if ($this->columnExists('xlr8_admin_designation', 'desig_code')) {
                $table->dropColumn('desig_code');
            }
        });

        // ── Step 5: Add index on new dept_code column ────────────────────────────
        Schema::table('xlr8_admin_division', function (Blueprint $table) {
            if (!$this->indexExists('xlr8_admin_division', 'idx_division_dept_code')) {
                $table->index('dept_code', 'idx_division_dept_code');
            }
        });
    }

    public function down(): void
    {
        // ── Restore xlr8_admin_department.dept_code ───────────────────────────────
        if (!$this->columnExists('xlr8_admin_department', 'dept_code')) {
            Schema::table('xlr8_admin_department', function (Blueprint $table) {
                $table->string('dept_code', 10)
                      ->nullable()
                      ->after('code')
                      ->comment('Short code used across org tables');
            });
            // Re-populate from code
            DB::statement("UPDATE xlr8_admin_department SET dept_code = code WHERE dept_code IS NULL");
        }

        // ── Restore xlr8_admin_division.div_code and department_id ────────────────
        Schema::table('xlr8_admin_division', function (Blueprint $table) {
            if (!$this->columnExists('xlr8_admin_division', 'div_code')) {
                $table->string('div_code', 10)
                      ->nullable()
                      ->after('code')
                      ->comment('Short code used across org tables');
            }
            if (!$this->columnExists('xlr8_admin_division', 'department_id')) {
                $table->unsignedBigInteger('department_id')
                      ->nullable()
                      ->after('id')
                      ->comment('Legacy FK to xlr8_admin_department.id');
            }
        });

        // Re-populate div_code from code, department_id from dept_code join
        DB::statement("UPDATE xlr8_admin_division SET div_code = code WHERE div_code IS NULL");
        DB::statement("
            UPDATE xlr8_admin_division d
            JOIN   xlr8_admin_department dep ON dep.code = d.dept_code
            SET    d.department_id = dep.id
            WHERE  d.department_id IS NULL
        ");

        // Drop new dept_code column
        Schema::table('xlr8_admin_division', function (Blueprint $table) {
            if ($this->indexExists('xlr8_admin_division', 'idx_division_dept_code')) {
                $table->dropIndex('idx_division_dept_code');
            }
            if ($this->columnExists('xlr8_admin_division', 'dept_code')) {
                $table->dropColumn('dept_code');
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};

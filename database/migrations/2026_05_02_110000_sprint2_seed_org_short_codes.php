<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SPRINT 2 — Migration 1/1
 * Back-fill short code columns added by master fix migration.
 * branch_code = code (truncated to 10 chars)
 * dept_code   = code (truncated to 10 chars)
 * div_code    = code (truncated to 10 chars)
 * vert_code   = code (truncated to 10 chars)
 *
 * Safe: runs in transaction, non-destructive UPDATE only.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Back-fill branch_code from code
            DB::statement('UPDATE xlr8_admin_branch SET branch_code = LEFT(code, 10) WHERE branch_code IS NULL');

            // Back-fill dept_code from code
            DB::statement('UPDATE xlr8_admin_department SET dept_code = LEFT(code, 10) WHERE dept_code IS NULL');

            // Back-fill div_code from code
            DB::statement('UPDATE xlr8_admin_division SET div_code = LEFT(code, 10) WHERE div_code IS NULL');

            // Back-fill vert_code from code
            DB::statement('UPDATE xlr8_admin_vertical SET vert_code = LEFT(code, 10) WHERE vert_code IS NULL');
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::statement('UPDATE xlr8_admin_branch SET branch_code = NULL');
            DB::statement('UPDATE xlr8_admin_department SET dept_code = NULL');
            DB::statement('UPDATE xlr8_admin_division SET div_code = NULL');
            DB::statement('UPDATE xlr8_admin_vertical SET vert_code = NULL');
        });
    }
};
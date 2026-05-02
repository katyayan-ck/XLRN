<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure user_data_scopes indexes are correct (partial run left duplicate)
        if (Schema::hasTable('xlr8_iam_user_data_scopes')) {
            $this->dropIndexIfExists('xlr8_iam_user_data_scopes', 'user_data_scopes_user_id_scope_type_scope_value_unique');

            Schema::table('xlr8_iam_user_data_scopes', function (Blueprint $table) {
                if (!$this->indexExists('xlr8_iam_user_data_scopes', 'uq_user_scope_type_value')) {
                    $table->unique(['user_id', 'scope_type', 'scope_value'], 'uq_user_scope_type_value');
                }
            });
        }

        // 2. Add missing short-code columns (still absent in current schema)
        $this->addShortCodeColumn('xlr8_admin_branch',     'branch_code', 10, 'code');
        $this->addShortCodeColumn('xlr8_admin_department', 'dept_code',   10, 'code');
        $this->addShortCodeColumn('xlr8_admin_division',   'div_code',    10, 'code');
        $this->addShortCodeColumn('xlr8_admin_vertical',   'vert_code',   10, 'code');

        // 3. Ensure required indexes exist
        $this->ensureIndex('xlr8_iam_post_org_scopes',    ['post_code', 'scope_type'], 'idx_pos_post_type');
        $this->ensureIndex('xlr8_iam_post_vehicle_scopes', ['post_code', 'scope_type'], 'idx_pvs_post_type');

        // 4. Drop legacy tables (still present)
        Schema::dropIfExists('xlr8_iam_emp_post_pivot');
        Schema::dropIfExists('xlr8_iam_process');
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed. Restore from backup.');
    }

    // ====================== SAFE HELPERS ======================
    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $index)) return;

        Schema::table($table, function (Blueprint $table) use ($index) {
            $table->dropIndex($index);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"));
        return $indexes->where('Key_name', $indexName)->isNotEmpty();
    }

    private function ensureIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $name)) return;

        Schema::table($table, function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
    }

    private function addShortCodeColumn(string $tableName, string $column, int $length, string $afterColumn): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, $column)) return;

        Schema::table($tableName, function (Blueprint $table) use ($column, $length, $afterColumn, $tableName) {
            $table->string($column, $length)->nullable()
                  ->after($afterColumn)
                  ->comment('Short code used across org tables');
            $table->index($column, "idx_{$tableName}_{$column}");
        });
    }
};
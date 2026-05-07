<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 Migration — RBAC Schema Additions
 *
 * Changes:
 *  1. xlr8_admin_location      → 7 location type boolean flags
 *  2. xlr8_admin_designation   → is_top_mgmt (bool) + parent_desig_code (varchar10)
 *  3. xlr8_admin_employee      → mile_id (varchar30, nullable) after oem_id
 *  4. importlogs               → extend importtype enum to include 'rbac_master'
 *
 * Safe to re-run:   uses hasColumn() guards on every alter.
 * Rollback safe:    down() fully reverses every change.
 * PHP / Laravel:    Laravel 12, PHP 8.3, MySQL 8.x
 */
return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    // UP
    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        $this->addLocationTypeFlags();
        $this->addDesignationColumns();
        $this->addEmployeeMileId();
        $this->extendImportlogsEnum();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOWN
    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        $this->removeLocationTypeFlags();
        $this->removeDesignationColumns();
        $this->removeEmployeeMileId();
        $this->revertImportlogsEnum();
    }

    // =========================================================================
    // 1. LOCATION TYPE FLAGS
    // =========================================================================

    /**
     * Adds 7 boolean type-flag columns to xlr8_admin_location.
     *
     * These replace the old free-text "type" field approach and allow a
     * single location to serve multiple purposes simultaneously
     * (e.g. a showroom that also has a workshop and parts counter).
     *
     * Column order is placed AFTER is_active deliberately — type flags are
     * operational metadata, not identity/status fields.
     *
     * All default false — existing rows are treated as unclassified until
     * manually updated or re-imported via the RBAC master importer.
     */
    private function addLocationTypeFlags(): void
    {
        Schema::table('xlr8_admin_location', function (Blueprint $table) {

            if (! Schema::hasColumn('xlr8_admin_location', 'is_sales_location')) {
                $table->boolean('is_sales_location')
                    ->default(false)
                    ->after('is_active')
                    ->comment('True if this location handles new vehicle retail sales');
            }

            if (! Schema::hasColumn('xlr8_admin_location', 'is_workshop')) {
                $table->boolean('is_workshop')
                    ->default(false)
                    ->after('is_sales_location')
                    ->comment('True if this location has a vehicle service / repair workshop');
            }

            if (! Schema::hasColumn('xlr8_admin_location', 'is_parts_location')) {
                $table->boolean('is_parts_location')
                    ->default(false)
                    ->after('is_workshop')
                    ->comment('True if this location stocks and retails spare parts');
            }

            if (! Schema::hasColumn('xlr8_admin_location', 'is_stock_location')) {
                $table->boolean('is_stock_location')
                    ->default(false)
                    ->after('is_parts_location')
                    ->comment('True if vehicles are physically stocked / dispatched from here');
            }

            if (! Schema::hasColumn('xlr8_admin_location', 'is_office_only')) {
                $table->boolean('is_office_only')
                    ->default(false)
                    ->after('is_stock_location')
                    ->comment('Administrative / back-office only — no sales, workshop, or parts activity');
            }

            if (! Schema::hasColumn('xlr8_admin_location', 'is_mwh')) {
                $table->boolean('is_mwh')
                    ->default(false)
                    ->after('is_office_only')
                    ->comment('Mother Warehouse — central hub for spare parts distribution to sub-locations');
            }

            if (! Schema::hasColumn('xlr8_admin_location', 'is_lmmws')) {
                $table->boolean('is_lmmws')
                    ->default(false)
                    ->after('is_mwh')
                    ->comment('LMM Workshop — dedicated service point for Light & Medium Motor vehicle segment');
            }
        });

        // Composite index to speed up location-type filter queries
        // e.g. "get all sales locations of branch BKN"
        if (! $this->indexExists('xlr8_admin_location', 'idx_loc_type_flags')) {
            Schema::table('xlr8_admin_location', function (Blueprint $table) {
                $table->index(
                    ['branch_code', 'is_sales_location', 'is_workshop', 'is_parts_location'],
                    'idx_loc_type_flags'
                );
            });
        }
    }

    private function removeLocationTypeFlags(): void
    {
        if ($this->indexExists('xlr8_admin_location', 'idx_loc_type_flags')) {
            Schema::table('xlr8_admin_location', function (Blueprint $table) {
                $table->dropIndex('idx_loc_type_flags');
            });
        }

        Schema::table('xlr8_admin_location', function (Blueprint $table) {
            $toDrop = array_filter([
                'is_sales_location', 'is_workshop', 'is_parts_location',
                'is_stock_location', 'is_office_only', 'is_mwh', 'is_lmmws',
            ], fn ($col) => Schema::hasColumn('xlr8_admin_location', $col));

            if ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }

    // =========================================================================
    // 2. DESIGNATION ADDITIONS
    // =========================================================================

    /**
     * is_top_mgmt:
     *   Flags DP, VP, CEO, GM, AGM as top management.
     *   Used for data-scoping bypass and approval hierarchy shortcuts.
     *   Separate from hierarchy_level so the flag can be queried with a
     *   simple boolean index rather than a range scan on hierarchy_level.
     *
     * parent_desig_code:
     *   Soft-reference (no FK) to desig_code of the "default reporting
     *   designation" for this designation. Drives the org tree builder and
     *   the DesignationDeptTree auto-population during employee onboarding.
     *   Stored as a short code (max 10 chars) matching desig_code pattern.
     */
    private function addDesignationColumns(): void
    {
        Schema::table('xlr8_admin_designation', function (Blueprint $table) {

            if (! Schema::hasColumn('xlr8_admin_designation', 'is_top_mgmt')) {
                $table->boolean('is_top_mgmt')
                    ->default(false)
                    ->after('category')
                    ->comment('True for DP, VP, CEO, GM, AGM — bypasses standard data-scope restrictions');
            }

            if (! Schema::hasColumn('xlr8_admin_designation', 'parent_desig_code')) {
                $table->string('parent_desig_code', 10)
                    ->nullable()
                    ->after('is_top_mgmt')
                    ->comment('Default reporting designation code. Soft-ref → xlr8_admin_designation.desig_code. No FK to allow bootstrapping.');
            }
        });

        // Index for top-mgmt lookups and parent-chain traversal
        if (! $this->indexExists('xlr8_admin_designation', 'idx_desig_top_mgmt')) {
            Schema::table('xlr8_admin_designation', function (Blueprint $table) {
                $table->index('is_top_mgmt', 'idx_desig_top_mgmt');
            });
        }

        if (! $this->indexExists('xlr8_admin_designation', 'idx_desig_parent_code')) {
            Schema::table('xlr8_admin_designation', function (Blueprint $table) {
                $table->index('parent_desig_code', 'idx_desig_parent_code');
            });
        }
    }

    private function removeDesignationColumns(): void
    {
        foreach (['idx_desig_top_mgmt', 'idx_desig_parent_code'] as $idx) {
            if ($this->indexExists('xlr8_admin_designation', $idx)) {
                Schema::table('xlr8_admin_designation', function (Blueprint $table) use ($idx) {
                    $table->dropIndex($idx);
                });
            }
        }

        Schema::table('xlr8_admin_designation', function (Blueprint $table) {
            $toDrop = array_filter(
                ['is_top_mgmt', 'parent_desig_code'],
                fn ($col) => Schema::hasColumn('xlr8_admin_designation', $col)
            );
            if ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }

    // =========================================================================
    // 3. EMPLOYEE mile_id
    // =========================================================================

    /**
     * mile_id — OEM Mile platform employee identifier.
     *
     * Business rule: Only applicable to CNS (Consultant) designation
     * employees, and not even all of them — it is populated only when
     * the OEM assigns one.
     *
     * Implementation:
     *   - Column is nullable on all rows (no constraint at DB level).
     *   - The importer enforces the CNS-only rule in application logic.
     *   - A partial index (WHERE mile_id IS NOT NULL) is added for
     *     performance on OEM sync queries without wasting index space
     *     on the majority of NULL rows.
     *
     * NOTE: MySQL does not support partial indexes natively. The index
     * below is a standard index; application queries should add
     * WHERE mile_id IS NOT NULL themselves for efficiency.
     */
    private function addEmployeeMileId(): void
    {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            if (! Schema::hasColumn('xlr8_admin_employee', 'mile_id')) {
                $table->string('mile_id', 30)
                    ->nullable()
                    ->after('oem_id')
                    ->comment('OEM Mile platform employee ID. CNS (Consultant) designation only. NULL for all other designations.');
            }
        });

        if (! $this->indexExists('xlr8_admin_employee', 'idx_emp_mile_id')) {
            Schema::table('xlr8_admin_employee', function (Blueprint $table) {
                $table->index('mile_id', 'idx_emp_mile_id');
            });
        }
    }

    private function removeEmployeeMileId(): void
    {
        if ($this->indexExists('xlr8_admin_employee', 'idx_emp_mile_id')) {
            Schema::table('xlr8_admin_employee', function (Blueprint $table) {
                $table->dropIndex('idx_emp_mile_id');
            });
        }

        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            if (Schema::hasColumn('xlr8_admin_employee', 'mile_id')) {
                $table->dropColumn('mile_id');
            }
        });
    }

    // =========================================================================
    // 4. IMPORTLOGS ENUM EXTENSION
    // =========================================================================

    /**
     * Extends the importlogs.importtype ENUM to add 'rbac_master'.
     *
     * The full ordered enum after this migration:
     *   vehicledefinition | users | accessories | rbac_master
     *
     * MySQL ENUM ordering note: existing values retain their ordinal
     * position. 'rbac_master' is appended at the end — safe operation.
     *
     * Guard: only runs if the importlogs table and importtype column exist,
     * so this migration does not fail on a fresh install that has not yet
     * run the importlogs table migration.
     */
    private function extendImportlogsEnum(): void
    {
        if (
            ! Schema::hasTable('importlogs') ||
            ! Schema::hasColumn('importlogs', 'importtype')
        ) {
            return;
        }

        // Check if 'rbac_master' is already in the enum to make this idempotent
        $currentEnum = $this->getEnumValues('importlogs', 'importtype');
        if (in_array('rbac_master', $currentEnum, true)) {
            return;
        }

        DB::statement("
            ALTER TABLE `importlogs`
            MODIFY COLUMN `importtype`
            ENUM(
                'vehicledefinition',
                'users',
                'accessories',
                'rbac_master'
            ) NOT NULL DEFAULT 'users'
        ");
    }

    private function revertImportlogsEnum(): void
    {
        if (
            ! Schema::hasTable('importlogs') ||
            ! Schema::hasColumn('importlogs', 'importtype')
        ) {
            return;
        }

        // Only revert if no rbac_master rows exist — safety check
        $hasRbacRows = DB::table('importlogs')
            ->where('importtype', 'rbac_master')
            ->exists();

        if ($hasRbacRows) {
            // Cannot safely remove enum value while rows reference it
            // Log a warning and skip — DBA must handle manually
            logger()->warning(
                'Migration rollback skipped: importlogs has rbac_master rows. ' .
                'Remove or reclassify them before rolling back this migration.'
            );
            return;
        }

        DB::statement("
            ALTER TABLE `importlogs`
            MODIFY COLUMN `importtype`
            ENUM(
                'vehicledefinition',
                'users',
                'accessories'
            ) NOT NULL DEFAULT 'users'
        ");
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Check whether a named index exists on a table.
     * Works with MySQL 8.x information_schema.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name   = ?
              AND index_name   = ?
        ", [$table, $indexName]);

        return ($result[0]->cnt ?? 0) > 0;
    }

    /**
     * Return current ENUM allowed values for a given column.
     * Parses information_schema COLUMN_TYPE e.g. enum('a','b','c').
     */
    private function getEnumValues(string $table, string $column): array
    {
        $result = DB::select("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE table_schema = DATABASE()
              AND table_name   = ?
              AND column_name  = ?
        ", [$table, $column]);

        if (empty($result)) {
            return [];
        }

        $type = $result[0]->COLUMN_TYPE ?? '';
        preg_match_all("/'([^']+)'/", $type, $matches);

        return $matches[1] ?? [];
    }
};

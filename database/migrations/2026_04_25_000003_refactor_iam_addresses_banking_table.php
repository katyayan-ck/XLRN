<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * MIGRATION 3 — xlr8_admin_person_addresses + xlr8_admin_person_banking_details
 *
 * ADDRESSES:
 *   ADD  address_type ENUM('Primary','Office','Home','Alternate','Permanent')
 *        Replaces is_primary + old type column.
 *   DROP is_primary, notes
 *   UNIQUE KEY (person_id, address_type)
 *
 * BANKING DETAILS:
 *   account_type (Saving/Salary/Current/OD) → already exists, DO NOT TOUCH.
 *   is_primary (boolean)                    → already exists, KEEP AS-IS.
 *   Actions: Demote duplicate primaries, promote first record if none set.
 *   CHECK constraint added as DB-level safety net on MySQL 8.0.16+.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ══ ADDRESSES ══════════════════════════════════════════════════════

        Schema::table('xlr8_admin_person_addresses', function (Blueprint $table) {
            $table->enum('address_type', ['Primary', 'Office', 'Home', 'Alternate', 'Permanent'])
                  ->default('Primary')->after('person_id')
                  ->comment('Replaces is_primary + type. One entry per address_type per person.');
        });

        $aCols  = Schema::getColumnListing('xlr8_admin_person_addresses');
        $hasIsP = in_array('is_primary', $aCols);
        $hasTyp = in_array('type', $aCols);

        if ($hasIsP && $hasTyp) {
            DB::statement("
                UPDATE xlr8_admin_person_addresses SET address_type = CASE
                    WHEN is_primary = 1 THEN 'Primary'
                    WHEN type IN ('Office','Home','Alternate','Permanent') THEN type
                    ELSE 'Alternate'
                END
            ");
        } elseif ($hasIsP) {
            DB::statement("
                UPDATE xlr8_admin_person_addresses
                SET address_type = IF(is_primary = 1, 'Primary', 'Alternate')
            ");
        }

        $aDrop = array_values(array_intersect(['is_primary', 'notes'], $aCols));
        if ($aDrop) {
            Schema::table('xlr8_admin_person_addresses', function (Blueprint $table) use ($aDrop) {
                $table->dropColumn($aDrop);
            });
        }

        DB::statement("
            CREATE UNIQUE INDEX uq_address_person_type
            ON xlr8_admin_person_addresses (person_id, address_type)
        ");

        // ══ BANKING DETAILS ════════════════════════════════════════════════
        // account_type (Saving/Salary/Current/OD) → already exists, untouched.
        // is_primary (boolean)                    → already exists, untouched.

        // Demote duplicate is_primary=1 rows — keep only the latest per person
        DB::statement("
            UPDATE xlr8_admin_person_banking_details b1
            INNER JOIN (
                SELECT person_id, MAX(id) AS keep_id
                FROM xlr8_admin_person_banking_details
                WHERE is_primary = 1 AND deleted_at IS NULL
                GROUP BY person_id
            ) b2 ON b1.person_id = b2.person_id
            SET b1.is_primary = 0
            WHERE b1.is_primary = 1
              AND b1.id != b2.keep_id
              AND b1.deleted_at IS NULL
        ");

        // For persons with NO primary at all, promote the first (lowest id) record
        DB::statement("
            UPDATE xlr8_admin_person_banking_details b1
            INNER JOIN (
                SELECT person_id, MIN(id) AS first_id
                FROM xlr8_admin_person_banking_details
                WHERE deleted_at IS NULL
                GROUP BY person_id
                HAVING SUM(is_primary) = 0
            ) b2 ON b1.person_id = b2.person_id AND b1.id = b2.first_id
            SET b1.is_primary = 1
        ");

        // MySQL 8.0.16+ CHECK constraint as DB-level safety net
        try {
            DB::statement("
                ALTER TABLE xlr8_admin_person_banking_details
                ADD CONSTRAINT chk_bank_is_primary_boolean CHECK (is_primary IN (0, 1))
            ");
        } catch (\Exception $e) { /* older MySQL — skip silently */ }
    }

    public function down(): void
    {
        // Addresses rollback
        try { DB::statement("DROP INDEX uq_address_person_type ON xlr8_admin_person_addresses"); } catch (\Exception $e) {}
        Schema::table('xlr8_admin_person_addresses', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->dropColumn('address_type');
        });

        // Banking rollback — nothing structural was added, just data normalised
        try { DB::statement("ALTER TABLE xlr8_admin_person_banking_details DROP CHECK chk_bank_is_primary_boolean"); } catch (\Exception $e) {}
    }
};
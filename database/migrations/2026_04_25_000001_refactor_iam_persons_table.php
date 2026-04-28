<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Run AFTER Migration 1 (contacts already seeded).
 *
 * ADD:  person_code VARCHAR(20) UNIQUE IMMUTABLE — PAN/Aadhaar/TAN natural key
 *       entity_type ENUM('individual','legal_entity') DEFAULT 'individual'
 *       tan_no      VARCHAR(20) — for legal entities
 * DROP: mobile_primary, mobile_secondary, email_primary, email_secondary
 *
 * Person Code Derivation:
 *   Individual  → PAN (preferred if both exist) → Aadhaar → legacy code
 *   Legal Entity → PAN → TAN → GSTIN → legacy code
 *   Rule: Immutable. If only Aadhaar existed at creation, adding PAN later does NOT change code.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new columns nullable first
        Schema::table('xlr8_admin_person', function (Blueprint $table) {
            $table->string('person_code', 20)->nullable()->unique()->after('id')
                  ->comment('Immutable. PAN→Aadhaar for individual; PAN→TAN for legal entity.');
            $table->enum('entity_type', ['individual', 'legal_entity'])
                  ->default('individual')->after('person_code');
            $table->string('tan_no', 20)->nullable()->after('gst_no');
        });

        // Step 2: Populate person_code — PAN preferred, then Aadhaar, then legacy code
        DB::statement("
            UPDATE xlr8_admin_person
            SET person_code = CASE
                WHEN pan_no    IS NOT NULL AND TRIM(pan_no)    != '' THEN UPPER(TRIM(pan_no))
                WHEN aadhaar_no IS NOT NULL AND TRIM(aadhaar_no) != '' THEN TRIM(aadhaar_no)
                ELSE code
            END
        ");

        // Step 3: Now enforce NOT NULL
        Schema::table('xlr8_admin_person', function (Blueprint $table) {
            $table->string('person_code', 20)->nullable(false)->change();
        });

        // Step 4: Drop deprecated contact columns (safely seeded in Migration 1)
        Schema::table('xlr8_admin_person', function (Blueprint $table) {
            $table->dropColumn(['mobile_primary', 'mobile_secondary', 'email_primary', 'email_secondary']);
        });
    }

    public function down(): void
    {
        Schema::table('xlr8_admin_person', function (Blueprint $table) {
            $table->string('mobile_primary')->nullable();
            $table->string('mobile_secondary')->nullable();
            $table->string('email_primary')->nullable();
            $table->string('email_secondary')->nullable();
        });
        Schema::table('xlr8_admin_person', function (Blueprint $table) {
            try { $table->dropUnique(['person_code']); } catch (\Exception $e) {}
            $table->dropColumn(['person_code', 'entity_type', 'tan_no']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * MUST RUN FIRST — seeds contact data BEFORE Migration 2 drops person columns.
 *
 * ADD:  data_type    ENUM('Mobile','Email','Landline','Fax')
 *       contact_type ENUM('Primary','Alternate','Office','Home','Emergency')
 *       contact_detail VARCHAR(100)
 * DROP: name, relationship, notes, is_primary, phone, email, type
 *
 * Primary Rule: First entry of each data_type per person is always 'Primary'.
 * UNIQUE KEY (person_id, data_type, contact_type) — prevents two 'Primary' mobiles.
 */
return new class extends Migration
{
    public function up(): void
{
    // Step 0: Make legacy NOT NULL columns safe for our INSERTs
    // We are about to drop these columns anyway — just give them defaults first.
    $existing = Schema::getColumnListing('xlr8_admin_person_contacts');
    $legacyNotNull = array_intersect(['name', 'relationship', 'notes', 'phone', 'email', 'type'], $existing);

    if ($legacyNotNull) {
        Schema::table('xlr8_admin_person_contacts', function (Blueprint $table) use ($legacyNotNull) {
            foreach ($legacyNotNull as $col) {
                $table->string($col)->nullable()->default(null)->change();
            }
        });
    }

    // Step 1: Add new columns nullable for safe seeding
    Schema::table('xlr8_admin_person_contacts', function (Blueprint $table) {
        $table->enum('data_type', ['Mobile', 'Email', 'Landline', 'Fax'])
              ->nullable()->after('person_id');
        $table->enum('contact_type', ['Primary', 'Alternate', 'Office', 'Home', 'Emergency'])
              ->nullable()->after('data_type');
        $table->string('contact_detail', 100)->nullable()->after('contact_type');
    });

    // Step 2: Seed from xlr8_admin_person columns
    DB::statement("
        INSERT INTO xlr8_admin_person_contacts
            (person_id, data_type, contact_type, contact_detail, created_by, updated_by, created_at, updated_at)
        SELECT p.id, 'Mobile', 'Primary', p.mobile_primary, p.created_by, p.created_by, NOW(), NOW()
        FROM xlr8_admin_person p
        WHERE p.mobile_primary IS NOT NULL AND TRIM(p.mobile_primary) != ''
    ");

    DB::statement("
        INSERT INTO xlr8_admin_person_contacts
            (person_id, data_type, contact_type, contact_detail, created_by, updated_by, created_at, updated_at)
        SELECT p.id, 'Mobile', 'Alternate', p.mobile_secondary, p.created_by, p.created_by, NOW(), NOW()
        FROM xlr8_admin_person p
        WHERE p.mobile_secondary IS NOT NULL AND TRIM(p.mobile_secondary) != ''
    ");

    DB::statement("
        INSERT INTO xlr8_admin_person_contacts
            (person_id, data_type, contact_type, contact_detail, created_by, updated_by, created_at, updated_at)
        SELECT p.id, 'Email', 'Primary', p.email_primary, p.created_by, p.created_by, NOW(), NOW()
        FROM xlr8_admin_person p
        WHERE p.email_primary IS NOT NULL AND TRIM(p.email_primary) != ''
    ");

    DB::statement("
        INSERT INTO xlr8_admin_person_contacts
            (person_id, data_type, contact_type, contact_detail, created_by, updated_by, created_at, updated_at)
        SELECT p.id, 'Email', 'Alternate', p.email_secondary, p.created_by, p.created_by, NOW(), NOW()
        FROM xlr8_admin_person p
        WHERE p.email_secondary IS NOT NULL AND TRIM(p.email_secondary) != ''
    ");

    // Step 3: Seed from users table
    DB::statement("
        INSERT INTO xlr8_admin_person_contacts
            (person_id, data_type, contact_type, contact_detail, created_by, updated_by, created_at, updated_at)
        SELECT u.person_id, 'Email', 'Primary', u.email, u.id, u.id, NOW(), NOW()
        FROM users u
        WHERE u.person_id IS NOT NULL AND u.email IS NOT NULL AND TRIM(u.email) != ''
          AND NOT EXISTS (
              SELECT 1 FROM xlr8_admin_person_contacts c
              WHERE c.person_id = u.person_id AND c.data_type = 'Email'
                AND c.contact_type = 'Primary' AND c.deleted_at IS NULL
          )
    ");

    DB::statement("
        INSERT INTO xlr8_admin_person_contacts
            (person_id, data_type, contact_type, contact_detail, created_by, updated_by, created_at, updated_at)
        SELECT u.person_id, 'Mobile', 'Primary', u.mobile, u.id, u.id, NOW(), NOW()
        FROM users u
        WHERE u.person_id IS NOT NULL AND u.mobile IS NOT NULL AND TRIM(u.mobile) != ''
          AND NOT EXISTS (
              SELECT 1 FROM xlr8_admin_person_contacts c
              WHERE c.person_id = u.person_id AND c.data_type = 'Mobile'
                AND c.contact_type = 'Primary' AND c.deleted_at IS NULL
          )
    ");

    // Step 4: Make new columns NOT NULL after seeding
    Schema::table('xlr8_admin_person_contacts', function (Blueprint $table) {
        $table->enum('data_type', ['Mobile', 'Email', 'Landline', 'Fax'])->nullable(false)->change();
        $table->enum('contact_type', ['Primary', 'Alternate', 'Office', 'Home', 'Emergency'])->nullable(false)->change();
        $table->string('contact_detail', 100)->nullable(false)->change();
    });

    // Step 5: UNIQUE — one entry per (person_id, data_type, contact_type)
    DB::statement("
        ALTER TABLE xlr8_admin_person_contacts
        ADD UNIQUE KEY uq_contact_person_dtype_ctype (person_id, data_type, contact_type)
    ");

    // Step 6: Drop old columns (now nullable — safe to drop)
    $existing = Schema::getColumnListing('xlr8_admin_person_contacts');
    $drop = array_values(array_intersect(
        ['name', 'relationship', 'notes', 'is_primary', 'phone', 'email', 'type'],
        $existing
    ));
    if ($drop) {
        Schema::table('xlr8_admin_person_contacts', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }
}

    public function down(): void
    {
        Schema::table('xlr8_admin_person_contacts', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('relationship')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('type')->nullable();
        });
        try { DB::statement("ALTER TABLE xlr8_admin_person_contacts DROP INDEX uq_contact_person_dtype_ctype"); } catch (\Exception $e) {}
        Schema::table('xlr8_admin_person_contacts', function (Blueprint $table) {
            $table->dropColumn(['data_type', 'contact_type', 'contact_detail']);
        });
    }
};

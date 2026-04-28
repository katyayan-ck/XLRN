<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = Schema::getColumnListing('users');

        // ── Step 1: Add new columns ───────────────────────────────────────
        Schema::table('users', function (Blueprint $table) use ($existing) {
            if (!in_array('user_type', $existing)) {
                $table->enum('user_type', ['Emp', 'Cust', 'DSA', 'Insurer', 'Associate'])
                      ->nullable()->after('id')
                      ->comment('Hardcoded DB-enforced enum. No lookup table.');
            }
            if (!in_array('username', $existing)) {
                $table->string('username', 60)->nullable()->unique()->after('user_type')
                      ->comment('Unique login handle. Seeded from old code column.');
            }
            if (!in_array('employee_code', $existing)) {
                $table->string('employee_code', 20)->nullable()->after('username')
                      ->comment('FK → xlr8_admin_employee.code. NULL for non-employee users.');
            }
            if (!in_array('created_by', $existing)) {
                $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            }
            if (!in_array('updated_by', $existing)) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        // person_code added via raw SQL to guarantee charset+collation matches
        // xlr8_admin_person.person_code exactly (utf8mb4 / utf8mb4_0900_ai_ci)
        if (!in_array('person_code', $existing)) {
            DB::statement("
                ALTER TABLE users
                ADD COLUMN person_code VARCHAR(20)
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_0900_ai_ci
                NULL
                COMMENT 'Natural FK → xlr8_admin_person.person_code'
                AFTER username
            ");
        }

        // ── Step 2: Populate person_code ──────────────────────────────────
        if (in_array('person_id', Schema::getColumnListing('users'))) {
            DB::statement("
                UPDATE users u
                INNER JOIN xlr8_admin_person p ON p.id = u.person_id
                SET u.person_code = p.person_code
                WHERE u.person_id IS NOT NULL
                  AND u.person_code IS NULL
            ");
        }

        // ── Step 3: Populate employee_code ────────────────────────────────
        if (in_array('employee_id', Schema::getColumnListing('users'))) {
            DB::statement("
                UPDATE users u
                INNER JOIN xlr8_admin_employee e ON e.id = u.employee_id
                SET u.employee_code = e.code
                WHERE u.employee_id IS NOT NULL
                  AND u.employee_code IS NULL
            ");
        }

        // ── Step 4: Seed username from old code column ────────────────────
        if (in_array('code', Schema::getColumnListing('users'))) {
            DB::statement("
                UPDATE users
                SET username = code
                WHERE username IS NULL
                  AND code IS NOT NULL
                  AND TRIM(code) != ''
            ");
        }

        // ── Step 5: Set user_type for all existing records ────────────────
        DB::statement("UPDATE users SET user_type = 'Emp' WHERE user_type IS NULL");

        // ── Step 6: Backfill audit fields ─────────────────────────────────
        DB::statement("UPDATE users SET created_by = id WHERE created_by IS NULL");
        DB::statement("UPDATE users SET updated_by = id WHERE updated_by IS NULL");

        // ── Step 7: FK constraints ────────────────────────────────────────
        // person_code FK skipped — collation mismatch between tables makes
        // MySQL reject it. Relationship enforced via Eloquent model instead.
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->foreign('created_by')
                      ->references('id')->on('users')
                      ->onDelete('set null');
            } catch (\Exception $e) {}
            try {
                $table->foreign('updated_by')
                      ->references('id')->on('users')
                      ->onDelete('set null');
            } catch (\Exception $e) {}
        });

        // ── Step 8: Drop old FKs then old columns ─────────────────────────
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'users_user_type_id_foreign',
                'users_person_id_foreign',
				'users_employee_id_foreign'
            ] as $fk) {
                try { $table->dropForeign($fk); } catch (\Exception $e) {}
            }

            $drop = array_values(array_intersect(
                ['user_type_id', 'person_id', 'employee_id', 'mile_id',
                 'code', 'name', 'email', 'mobile',
                 'email_verified_at', 'remember_token'],
                Schema::getColumnListing('users')
            ));

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try { $table->dropForeign(['created_by']); } catch (\Exception $e) {}
            try { $table->dropForeign(['updated_by']); } catch (\Exception $e) {}

            $toDrop = array_values(array_intersect(
                ['user_type', 'username', 'person_code', 'employee_code', 'created_by', 'updated_by'],
                Schema::getColumnListing('users')
            ));
            if ($toDrop) $table->dropColumn($toDrop);

            $table->unsignedBigInteger('user_type_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('mile_id')->nullable();
            $table->string('code')->default('');
            $table->string('name')->default('');
            $table->string('email')->default('');
            $table->string('mobile')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
        });
    }
};
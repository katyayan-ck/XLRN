<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ====== ENHANCE system_settings TABLE WITH 6 AUDIT FIELDS ======
        if (Schema::hasTable('system_settings')) {
            Schema::table('system_settings', function (Blueprint $table) {
                // 1. Rename is_editable to iseditable for consistency
                if (
                    Schema::hasColumn('system_settings', 'is_editable') &&
                    !Schema::hasColumn('system_settings', 'iseditable')
                ) {
                    $table->renameColumn('is_editable', 'iseditable');
                }

                // 2. Add label for admin UI (after key)
                if (!Schema::hasColumn('system_settings', 'label')) {
                    $table->string('label')->nullable()->after('key')
                        ->comment('Human-readable label for admin interface');
                }

                // 3. Add default_value (after value)
                if (!Schema::hasColumn('system_settings', 'default_value')) {
                    $table->text('default_value')->nullable()->after('value')
                        ->comment('Default value if not set');
                }

                // 4. Add topic - Category/Topic for setting
                if (!Schema::hasColumn('system_settings', 'topic')) {
                    $table->string('topic')->nullable()->after('type')
                        ->comment('Setting topic/category: site, dealership, pricing');
                }

                // 5. Add group - Group within topic
                if (!Schema::hasColumn('system_settings', 'group')) {
                    $table->string('group')->nullable()->after('topic')
                        ->comment('Group name within topic for UI organization');
                }

                // 6. Add sort_order
                if (!Schema::hasColumn('system_settings', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('group')
                        ->comment('Order within group');
                }

                // 7. Add input_type - for admin UI
                if (!Schema::hasColumn('system_settings', 'input_type')) {
                    $table->string('input_type')->default('text')->after('type')
                        ->comment('text, textarea, json, file, image, select, toggle, color, number, email');
                }

                // 8. Add validation_rules
                if (!Schema::hasColumn('system_settings', 'validation_rules')) {
                    $table->text('validation_rules')->nullable()->after('input_type')
                        ->comment('Laravel validation rules');
                }

                // 9. Add options for select/radio
                if (!Schema::hasColumn('system_settings', 'options')) {
                    $table->json('options')->nullable()->after('validation_rules')
                        ->comment('JSON array of options for select/radio inputs');
                }

                // 10. Add help_text
                if (!Schema::hasColumn('system_settings', 'help_text')) {
                    $table->text('help_text')->nullable()->after('options')
                        ->comment('Helper text shown in admin UI');
                }

                // 11. Add is_visible
                if (!Schema::hasColumn('system_settings', 'is_visible')) {
                    $table->boolean('is_visible')->default(true)->after('iseditable')
                        ->comment('Show in admin UI');
                }

                // 12. Add deleted_at for soft deletes (6 AUDIT FIELDS: created_by, updated_by, created_at, updated_at, is_deleted, deleted_at)
                if (!Schema::hasColumn('system_settings', 'deleted_at')) {
                    $table->softDeletes();
                }

                // 13. Add is_deleted flag
                if (!Schema::hasColumn('system_settings', 'is_deleted')) {
                    $table->boolean('is_deleted')->default(false)->after('updated_at')
                        ->comment('Soft delete flag');
                }
            });

            // Add indexes (safely, without checking if they exist)
            try {
                Schema::table('system_settings', function (Blueprint $table) {
                    $table->index('topic');
                    $table->index('group');
                    $table->index(['topic', 'group']);
                    $table->index('iseditable');
                    $table->index('is_visible');
                });
            } catch (\Exception $e) {
                // Indexes might already exist, ignore
            }
        }

        // ====== CREATE system_setting_topics TABLE ======
        if (!Schema::hasTable('system_setting_topics')) {
            Schema::create('system_setting_topics', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->comment('Topic code: site, dealership, pricing, etc');
                $table->string('label')->comment('Display name');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);

                // 6 AUDIT FIELDS
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->boolean('is_deleted')->default(false);
                $table->softDeletes();

                // Indexes
                $table->index('code');
                $table->index('is_active');

                // Foreign keys
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // ====== CREATE system_setting_audits TABLE ======
        if (!Schema::hasTable('system_setting_audits')) {
            Schema::create('system_setting_audits', function (Blueprint $table) {
                $table->id();

                // Foreign key to system_settings
                $table->unsignedBigInteger('setting_id');
                $table->unsignedBigInteger('user_id')->nullable();

                $table->string('action')->comment('created, updated, deleted');
                $table->longText('old_value')->nullable();
                $table->longText('new_value')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();

                // 6 AUDIT FIELDS
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->boolean('is_deleted')->default(false);
                $table->softDeletes();

                // Indexes
                $table->index(['setting_id', 'created_at']);
                $table->index(['user_id', 'created_at']);
                $table->index('action');

                // Foreign keys (AFTER columns defined)
                $table->foreign('setting_id')
                    ->references('id')
                    ->on('system_settings')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('updated_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        // Drop tables in correct order (foreign key dependencies)
        Schema::dropIfExists('system_setting_audits');
        Schema::dropIfExists('system_setting_topics');

        // Remove columns from system_settings
        if (Schema::hasTable('system_settings')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $columns = [
                    'label',
                    'default_value',
                    'topic',
                    'group',
                    'sort_order',
                    'input_type',
                    'validation_rules',
                    'options',
                    'help_text',
                    'is_visible',
                    'is_deleted'
                ];

                foreach ($columns as $col) {
                    if (Schema::hasColumn('system_settings', $col)) {
                        try {
                            $table->dropColumn($col);
                        } catch (\Exception $e) {
                            // Ignore errors
                        }
                    }
                }

                // Drop indexes safely
                try {
                    $table->dropIndex(['topic']);
                } catch (\Exception $e) {
                }

                try {
                    $table->dropIndex(['group']);
                } catch (\Exception $e) {
                }

                try {
                    $table->dropIndex(['topic', 'group']);
                } catch (\Exception $e) {
                }

                try {
                    $table->dropIndex(['iseditable']);
                } catch (\Exception $e) {
                }

                try {
                    $table->dropIndex(['is_visible']);
                } catch (\Exception $e) {
                }

                // Drop soft deletes
                try {
                    $table->dropSoftDeletes();
                } catch (\Exception $e) {
                }
            });
        }
    }
};

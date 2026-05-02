<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPRINT 1 — MIGRATION 1/7
 * Extend xlr8_iam_roles to absorb Post.
 * Rule: NO SQL FOREIGN KEYS — indexes only.
 * Rule: All tables get 6 audit columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xlr8_iam_roles', function (Blueprint $table) {

            // ── Post Identity ─────────────────────────────
            $table->string('post_code', 30)->nullable()->unique()->after('guard_name')
                  ->comment('Unique post code e.g. NKH-SHW-SL-FSC-001');
            $table->string('display_name', 150)->nullable()->after('post_code');
            $table->boolean('is_post')->default(false)->after('display_name')
                  ->comment('Discriminator: true=Post, false=system Role');

            // ── Org Anchors (code-based, NO FK) ───────────
            $table->string('branch_code', 10)->nullable()->after('is_post');
            $table->string('loc_code',    10)->nullable()->after('branch_code');
            $table->string('dept_code',   10)->nullable()->after('loc_code');
            $table->string('div_code',    10)->nullable()->after('dept_code');
            $table->string('desig_code',  10)->nullable()->after('div_code');
            $table->string('tree_code',   20)->nullable()->after('desig_code')
                  ->comment('Eloquent-only link to xlr8_admin_desig_dept_tree.tree_code');

            // ── Post Config ────────────────────────────────
            $table->tinyInteger('seq_no')->default(1)->after('tree_code');
            $table->tinyInteger('max_occupants')->default(1)->after('seq_no');
            $table->boolean('is_active')->default(true)->after('max_occupants');
            $table->json('metadata')->nullable()->after('is_active');

            // ── 6 Audit Columns ────────────────────────────
            // (created_at / updated_at already exist from Spatie)
            $table->softDeletes()->after('metadata');
            $table->unsignedBigInteger('created_by')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');

            // ── Indexes (NO FKs) ───────────────────────────
            $table->index('is_post',                       'idx_iam_roles_is_post');
            $table->index('post_code',                     'idx_iam_roles_post_code');
            $table->index('branch_code',                   'idx_iam_roles_branch_code');
            $table->index('loc_code',                      'idx_iam_roles_loc_code');
            $table->index('desig_code',                    'idx_iam_roles_desig_code');
            $table->index('tree_code',                     'idx_iam_roles_tree_code');
            $table->index('is_active',                     'idx_iam_roles_is_active');
            $table->index(['dept_code', 'div_code'],       'idx_iam_roles_dept_div');
            $table->index(['is_post', 'is_active'],        'idx_iam_roles_post_active');
            $table->index('created_by',                    'idx_iam_roles_created_by');
        });
    }

    public function down(): void
    {
        Schema::table('xlr8_iam_roles', function (Blueprint $table) {
            $table->dropIndex('idx_iam_roles_is_post');
            $table->dropIndex('idx_iam_roles_post_code');
            $table->dropIndex('idx_iam_roles_branch_code');
            $table->dropIndex('idx_iam_roles_loc_code');
            $table->dropIndex('idx_iam_roles_desig_code');
            $table->dropIndex('idx_iam_roles_tree_code');
            $table->dropIndex('idx_iam_roles_is_active');
            $table->dropIndex('idx_iam_roles_dept_div');
            $table->dropIndex('idx_iam_roles_post_active');
            $table->dropIndex('idx_iam_roles_created_by');
            $table->dropColumn([
                'post_code','display_name','is_post',
                'branch_code','loc_code','dept_code','div_code','desig_code','tree_code',
                'seq_no','max_occupants','is_active','metadata',
                'deleted_at','created_by','updated_by','deleted_by',
            ]);
        });
    }
};

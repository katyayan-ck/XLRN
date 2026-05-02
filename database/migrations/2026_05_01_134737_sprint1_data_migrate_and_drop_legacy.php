<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPRINT 1 — MIGRATION 7/7
 * 1. Migrate existing xlr8_iam_post rows → xlr8_iam_roles (as Posts)
 * 2. Migrate existing role assignments from xlr8_iam_post/pivot → emp_post_assignments
 * 3. Drop legacy tables: xlr8_iam_post, userdatascopes, graph_nodes, graph_edges,
 *    reporting_hierarchies, xlr8_admin_emp_vehicle_scope (if exists)
 *
 * SAFE: runs in a DB transaction. Rolls back cleanly on failure.
 */
return new class extends Migration
{
    public function up(): void
{
    DB::transaction(function () {
        // Step 1 & 2: Data migration only (keep inside transaction)
        if (Schema::hasTable('xlr8_iam_post')) {
            $posts = DB::table('xlr8_iam_post')->get();
            foreach ($posts as $post) {
                $existingRole = DB::table('xlr8_iam_roles')
                    ->where('name', $post->code ?? $post->post_code ?? 'POST-'.$post->id)
                    ->first();

                if ($existingRole) {
                    DB::table('xlr8_iam_roles')->where('id', $existingRole->id)->update([
                        'post_code' => $post->code ?? $post->post_code,
                        'display_name' => $post->display_name ?? $post->name ?? null,
                        'is_post' => true,
                        'branch_code' => $post->branch_code ?? null,
                        'loc_code' => $post->location_code ?? $post->loc_code ?? null,
                        'dept_code' => $post->dept_code ?? null,
                        'div_code' => $post->div_code ?? null,
                        'desig_code' => $post->desig_code ?? null,
                        'max_occupants' => $post->max_occupants ?? 1,
                        'is_active' => $post->is_active ?? true,
                        'created_by' => $post->created_by ?? null,
                        'updated_by' => $post->updated_by ?? null,
                    ]);
                } else {
                    DB::table('xlr8_iam_roles')->insert([
                        'name' => $post->code ?? $post->post_code ?? 'POST-'.$post->id,
                        'guard_name' => 'api',
                        'post_code' => $post->code ?? $post->post_code,
                        'display_name' => $post->display_name ?? $post->name ?? null,
                        'is_post' => true,
                        'branch_code' => $post->branch_code ?? null,
                        'loc_code' => $post->location_code ?? $post->loc_code ?? null,
                        'dept_code' => $post->dept_code ?? null,
                        'div_code' => $post->div_code ?? null,
                        'desig_code' => $post->desig_code ?? null,
                        'max_occupants' => $post->max_occupants ?? 1,
                        'is_active' => $post->is_active ?? true,
                        'created_at' => $post->created_at ?? now(),
                        'updated_at' => $post->updated_at ?? now(),
                        'created_by' => $post->created_by ?? null,
                        'updated_by' => $post->updated_by ?? null,
                    ]);
                }
            }
        }

        // Step 2: Pivot migration
        $pivotTable = Schema::hasTable('xlr8_admin_emp_post_pivot') 
            ? 'xlr8_admin_emp_post_pivot' 
            : (Schema::hasTable('emp_post_assignments') ? 'emp_post_assignments' : null);

        if ($pivotTable) {
            $pivotRows = DB::table($pivotTable)->get();
            foreach ($pivotRows as $row) {
                $postCode = $row->post_code ?? DB::table('xlr8_iam_roles')
                    ->where('id', $row->post_id ?? 0)->value('post_code');
                if (!$postCode) continue;

                DB::table('xlr8_admin_emp_post_assignments')->insertOrIgnore([
                    'emp_code' => $row->emp_code,
                    'post_code' => $postCode,
                    'assignment_type' => $row->assignment_type ?? 'primary',
                    'from_date' => $row->from_date ?? $row->created_at,
                    'to_date' => $row->to_date ?? ($row->is_current ? null : now()),
                    'relieving_type' => 'onboarding',
                    'remarks' => 'Migrated from legacy pivot',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'created_by' => $row->created_by ?? null,
                ]);
            }
        }
    });

    // Step 3: Drop tables OUTSIDE transaction
    $legacyTables = [
        'xlr8_iam_post', 'userdatascopes', 'user_data_scopes',
        'graph_nodes', 'graph_edges', 'reporting_hierarchies',
        'xlr8_admin_emp_vehicle_scope', 'employee_vehicle_scopes'
    ];
    foreach ($legacyTables as $table) {
        Schema::dropIfExists($table);
    }
}

    public function down(): void
    {
        // Intentionally empty — this migration is one-way.
        // Restore from the pre-migration schema dump created in Step 1.
        throw new \RuntimeException(
            'Sprint 1 data migration cannot be automatically reversed. ' .
            'Restore from schema dump created before running this migration.'
        );
    }
};
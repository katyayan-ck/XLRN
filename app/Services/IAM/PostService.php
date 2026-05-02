<?php

namespace App\Services\IAM;

use App\Enums\ErrorCodeEnum;
use App\Exceptions\ApplicationException;
use App\Models\IAM\Post;
use App\Models\IAM\PostOrgScope;
use App\Models\IAM\PostVehicleScope;
use App\Models\Admin\DesigDeptTree;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * PostService — all business logic for Post CRUD, scope management, vacancy checks.
 *
 * Workspace Rules:
 *  - Pure Eloquent — no raw SQL in app code
 *  - All queries via named scopes
 *  - DB::transaction() wrapping all multi-step writes
 */
class PostService
{
    // ── Read Operations ───────────────────────────────────────────────

    public function list(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Post::with(['designation', 'department', 'division', 'location', 'desigDeptTree'])
                     ->active();

        if (!empty($filters['branch_code'])) {
            $query->byBranch($filters['branch_code']);
        }
        if (!empty($filters['loc_code'])) {
            $query->byLocation($filters['loc_code']);
        }
        if (!empty($filters['dept_code'])) {
            $query->byDept($filters['dept_code']);
        }
        if (!empty($filters['desig_code'])) {
            $query->byDesig($filters['desig_code']);
        }
        if (isset($filters['vacant']) && $filters['vacant']) {
            $query->vacant();
        }

        return $query->orderBy('branch_code')->orderBy('dept_code')->orderBy('post_code')
                     ->paginate($perPage);
    }

    public function findByCode(string $postCode): Post
    {
        $post = Post::with([
            'designation', 'department', 'division', 'location',
            'desigDeptTree', 'orgScopes', 'vehicleScopes',
            'currentAssignments.employee',
        ])->where('post_code', $postCode)->first();

        if (!$post) {
            throw new ApplicationException(
                ErrorCodeEnum::POST_NOT_FOUND,
                "Post [{$postCode}] not found."
            );
        }
        return $post;
    }

    public function getVacantPosts(string $branchCode): Collection
    {
        return Post::with(['designation'])
                   ->active()
                   ->byBranch($branchCode)
                   ->vacant()
                   ->orderBy('desig_code')
                   ->get();
    }

    // ── Write Operations ──────────────────────────────────────────────

    public function create(array $data): Post
    {
        return DB::transaction(function () use ($data) {
            $post = Post::create([
                'display_name'  => $data['display_name'],
                'branch_code'   => $data['branch_code'],
                'loc_code'      => $data['loc_code']    ?? null,
                'dept_code'     => $data['dept_code']   ?? null,
                'div_code'      => $data['div_code']    ?? null,
                'desig_code'    => $data['desig_code']  ?? null,
                'tree_code'     => $data['tree_code']   ?? null,
                'max_occupants' => $data['max_occupants'] ?? 1,
                'seq_no'        => $data['seq_no']      ?? 1,
                'is_active'     => $data['is_active']   ?? true,
                'metadata'      => $data['metadata']    ?? null,
            ]);

            // Attach org scopes if provided
            if (!empty($data['org_scopes'])) {
                $this->syncOrgScopes($post, $data['org_scopes']);
            }

            // Attach vehicle scopes if provided
            if (!empty($data['vehicle_scopes'])) {
                $this->syncVehicleScopes($post, $data['vehicle_scopes']);
            }

            return $post->load(['orgScopes', 'vehicleScopes']);
        });
    }

    public function update(string $postCode, array $data): Post
    {
        return DB::transaction(function () use ($postCode, $data) {
            $post = $this->findByCode($postCode);

            $post->update(array_filter([
                'display_name'  => $data['display_name']  ?? null,
                'loc_code'      => $data['loc_code']      ?? null,
                'dept_code'     => $data['dept_code']     ?? null,
                'div_code'      => $data['div_code']      ?? null,
                'desig_code'    => $data['desig_code']    ?? null,
                'tree_code'     => $data['tree_code']     ?? null,
                'max_occupants' => $data['max_occupants'] ?? null,
                'is_active'     => $data['is_active']     ?? null,
                'metadata'      => $data['metadata']      ?? null,
            ], fn($v) => $v !== null));

            if (isset($data['org_scopes'])) {
                $this->syncOrgScopes($post, $data['org_scopes']);
            }
            if (isset($data['vehicle_scopes'])) {
                $this->syncVehicleScopes($post, $data['vehicle_scopes']);
            }

            return $post->load(['orgScopes', 'vehicleScopes']);
        });
    }

    public function deactivate(string $postCode, string $reason = ''): Post
    {
        return DB::transaction(function () use ($postCode, $reason) {
            $post = $this->findByCode($postCode);

            // Block deactivation if post has active occupants
            if (!$post->isVacant()) {
                throw new ApplicationException(
                    ErrorCodeEnum::POST_HAS_ACTIVE_OCCUPANTS,
                    "Cannot deactivate post [{$postCode}] — it has active occupants. Relieve them first."
                );
            }

            $post->update(['is_active' => false]);
            return $post;
        });
    }

    // ── Scope Management ──────────────────────────────────────────────

    /**
     * Full sync of org scopes for a post.
     * Input: [['scope_type' => 'branch', 'scope_value' => 'NKH'], ...]
     * Pass ['scope_type' => 'branch', 'scope_value' => null] for wildcard.
     */
    public function syncOrgScopes(Post $post, array $scopes): void
    {
        // Delete existing scopes for the provided types only
        $types = collect($scopes)->pluck('scope_type')->unique()->values()->all();
        PostOrgScope::forPost($post->post_code)->whereIn('scope_type', $types)->delete();

        foreach ($scopes as $scope) {
            PostOrgScope::create([
                'post_code'   => $post->post_code,
                'scope_type'  => $scope['scope_type'],
                'scope_value' => $scope['scope_value'] ?? null,
                'created_by'  => auth()->id(),
            ]);
        }
    }

    public function syncVehicleScopes(Post $post, array $scopes): void
    {
        $types = collect($scopes)->pluck('scope_type')->unique()->values()->all();
        PostVehicleScope::forPost($post->post_code)->whereIn('scope_type', $types)->delete();

        foreach ($scopes as $scope) {
            PostVehicleScope::create([
                'post_code'   => $post->post_code,
                'scope_type'  => $scope['scope_type'],
                'scope_value' => $scope['scope_value'] ?? null,
                'created_by'  => auth()->id(),
            ]);
        }
    }

    // ── Tree / Hierarchy ──────────────────────────────────────────────

    public function resolveTree(string $desigCode, string $deptCode, ?string $divCode = null): ?DesigDeptTree
    {
        return DesigDeptTree::where('desig_code', $desigCode)
                            ->where('dept_code', $deptCode)
                            ->when($divCode, fn($q) => $q->where('div_code', $divCode))
                            ->active()
                            ->first();
    }
}
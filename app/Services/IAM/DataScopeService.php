<?php

namespace App\Services\IAM;

use App\Models\IAM\Post;
use App\Models\Admin\EmpPostAssignment;
use Carbon\Carbon;

/**
 * DataScopeService — resolves what data an employee/post can see.
 *
 * Resolution Order (highest priority wins):
 *  1. Check active Post → PostOrgScope / PostVehicleScope
 *  2. Fall back to DesigDeptTree defaults
 *  3. Return null = all access (wildcard) if no scopes defined
 *
 * All queries are Pure Eloquent — no raw SQL.
 */
class DataScopeService
{
    // ── Org Scope Resolution ──────────────────────────────────────────

    /**
     * Get org scope codes for an employee's current primary post.
     * Returns: null = all access | [] = no access | ['NKH','BKN'] = specific
     */
    public function getOrgScope(string $empCode, string $scopeType, ?Carbon $onDate = null): ?array
    {
        $assignment = $onDate
            ? EmpPostAssignment::forEmployee($empCode)->primary()->onDate($onDate)->first()
            : EmpPostAssignment::forEmployee($empCode)->primary()->current()->first();

        if (!$assignment) return [];

        $post = Post::with('orgScopes')
                    ->where('post_code', $assignment->post_code)
                    ->first();

        if (!$post) return [];

        return $post->getOrgScopeFor($scopeType);
    }

    /**
     * Unified scope check: can this employee access entity with given code + type?
     */
    public function canAccessOrg(string $empCode, string $scopeType, string $scopeValue): bool
    {
        $scopes = $this->getOrgScope($empCode, $scopeType);
        if ($scopes === null) return true;  // wildcard — all access
        if (empty($scopes)) return false;   // no scopes defined — no access
        return in_array($scopeValue, $scopes, true);
    }

    // ── Vehicle Scope Resolution ──────────────────────────────────────

    public function getVehicleScope(string $empCode, string $scopeType, ?Carbon $onDate = null): ?array
    {
        $assignment = $onDate
            ? EmpPostAssignment::forEmployee($empCode)->primary()->onDate($onDate)->first()
            : EmpPostAssignment::forEmployee($empCode)->primary()->current()->first();

        if (!$assignment) return [];

        $post = Post::with('vehicleScopes')
                    ->where('post_code', $assignment->post_code)
                    ->first();

        if (!$post) return [];

        return $post->getVehicleScopeFor($scopeType);
    }

    public function canAccessVehicle(string $empCode, string $scopeType, string $scopeValue): bool
    {
        $scopes = $this->getVehicleScope($empCode, $scopeType);
        if ($scopes === null) return true;
        if (empty($scopes)) return false;
        return in_array($scopeValue, $scopes, true);
    }

    // ── Combined Scope Payload ────────────────────────────────────────

    /**
     * Returns full scope payload for a post — used by API to hydrate
     * the authenticated user's data access context.
     */
    public function getFullScopePayload(string $postCode): array
    {
        $post = Post::with(['orgScopes', 'vehicleScopes'])
                    ->where('post_code', $postCode)
                    ->first();

        if (!$post) return ['org' => [], 'vehicle' => []];

        $orgScopes = [];
        foreach (\App\Models\IAM\PostOrgScope::TYPES as $type) {
            $orgScopes[$type] = $post->getOrgScopeFor($type);
        }

        $vehicleScopes = [];
        foreach (\App\Models\IAM\PostVehicleScope::TYPES as $type) {
            $vehicleScopes[$type] = $post->getVehicleScopeFor($type);
        }

        return [
            'post_code' => $postCode,
            'org'       => $orgScopes,
            'vehicle'   => $vehicleScopes,
        ];
    }

    // ── Eloquent Query Scope Injection ────────────────────────────────

    /**
     * Returns an array of branch codes for use in Eloquent whereIn() calls.
     * Returns null if wildcard (caller skips the whereIn).
     */
    public function getBranchCodesForQuery(string $empCode): ?array
    {
        return $this->getOrgScope($empCode, 'branch');
    }

    public function getLocationCodesForQuery(string $empCode): ?array
    {
        return $this->getOrgScope($empCode, 'location');
    }

    public function getSegmentCodesForQuery(string $empCode): ?array
    {
        return $this->getVehicleScope($empCode, 'segment');
    }
}
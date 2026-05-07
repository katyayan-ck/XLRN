<?php

namespace App\Services\IAM;

use App\Models\Admin\EmpPostAssignment;
use App\Models\IAM\Post;
use App\Models\IAM\PostOrgScope;
use App\Models\IAM\PostVehicleScope;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * DataScopeService — Post-based data access scope resolver.
 *
 * All scope resolution is derived from the employee's current active Post(s).
 * SuperAdmin always bypasses — returns null (wildcard).
 * Employee with no current post — returns [] (no access).
 *
 * Cache key: user.{userId}.scope.{type}  TTL: 60 minutes
 */
class DataScopeService
{
    private const TTL = 3600; // 1 hour

    // ── Primary Resolution ────────────────────────────────────────────

    /**
     * Get accessible org-scope codes for authenticated user.
     * null = all access (wildcard) | [] = no access | [...] = specific codes
     */
    public function getOrgScope(User $user, string $scopeType, bool $useCache = true): ?array
    {
        $this->validateOrgScopeType($scopeType);

        if ($user->isSuperAdmin()) return null;

        $cacheKey = "user.{$user->id}.scope.org.{$scopeType}";

        if ($useCache) {
            return Cache::remember($cacheKey, self::TTL, fn () =>
                $this->resolveOrgScope($user, $scopeType)
            );
        }

        return $this->resolveOrgScope($user, $scopeType);
    }

    /**
     * Get accessible vehicle-scope codes for authenticated user.
     */
    public function getVehicleScope(User $user, string $scopeType, bool $useCache = true): ?array
    {
        $this->validateVehicleScopeType($scopeType);

        if ($user->isSuperAdmin()) return null;

        $cacheKey = "user.{$user->id}.scope.vehicle.{$scopeType}";

        if ($useCache) {
            return Cache::remember($cacheKey, self::TTL, fn () =>
                $this->resolveVehicleScope($user, $scopeType)
            );
        }

        return $this->resolveVehicleScope($user, $scopeType);
    }

    // ── Access Checks ─────────────────────────────────────────────────

    public function canAccessOrg(User $user, string $scopeType, string $value): bool
    {
        $scope = $this->getOrgScope($user, $scopeType);
        if ($scope === null) return true;
        if (empty($scope)) return false;
        return in_array($value, $scope, true);
    }

    public function canAccessVehicle(User $user, string $scopeType, string $value): bool
    {
        $scope = $this->getVehicleScope($user, $scopeType);
        if ($scope === null) return true;
        if (empty($scope)) return false;
        return in_array($value, $scope, true);
    }

    // ── Query Helpers ─────────────────────────────────────────────────

    public function getBranchCodesForQuery(User $user): ?array
    {
        return $this->getOrgScope($user, 'branch');
    }

    public function getLocationCodesForQuery(User $user): ?array
    {
        return $this->getOrgScope($user, 'location');
    }

    public function getDeptCodesForQuery(User $user): ?array
    {
        return $this->getOrgScope($user, 'department');
    }

    public function getDivCodesForQuery(User $user): ?array
    {
        return $this->getOrgScope($user, 'division');
    }

    public function getSegmentCodesForQuery(User $user): ?array
    {
        return $this->getVehicleScope($user, 'segment');
    }

    public function getSubsegmentCodesForQuery(User $user): ?array
    {
        return $this->getVehicleScope($user, 'subsegment');
    }

    // ── Full Scope Payload (for API /me response) ─────────────────────

    public function getFullScopePayload(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return ['superadmin' => true, 'org' => null, 'vehicle' => null];
        }

        $orgScopes = [];
        foreach (PostOrgScope::TYPES as $type) {
            $orgScopes[$type] = $this->getOrgScope($user, $type);
        }

        $vehicleScopes = [];
        foreach (PostVehicleScope::TYPES as $type) {
            $vehicleScopes[$type] = $this->getVehicleScope($user, $type);
        }

        return [
            'superadmin' => false,
            'org'        => $orgScopes,
            'vehicle'    => $vehicleScopes,
        ];
    }

    // ── Cache Invalidation ────────────────────────────────────────────

    /**
     * Called by HRJourneyService when assignments change.
     */
    public function invalidateScopeCache(User $user): void
    {
        foreach (PostOrgScope::TYPES as $type) {
            Cache::forget("user.{$user->id}.scope.org.{$type}");
        }
        foreach (PostVehicleScope::TYPES as $type) {
            Cache::forget("user.{$user->id}.scope.vehicle.{$type}");
        }
    }

    /**
     * Called by PostOrgScope/PostVehicleScope observers — invalidates ALL
     * employees currently on the affected post.
     */
    public function invalidateScopeCacheForPost(string $postCode): void
    {
        $empCodes = EmpPostAssignment::where('post_code', $postCode)
            ->current()
            ->pluck('emp_code');

        $userIds = \App\Models\User::whereHas('employee', fn ($q) =>
            $q->whereIn('code', $empCodes)
        )->pluck('id');

        foreach ($userIds as $userId) {
            foreach (PostOrgScope::TYPES as $type) {
                Cache::forget("user.{$userId}.scope.org.{$type}");
            }
            foreach (PostVehicleScope::TYPES as $type) {
                Cache::forget("user.{$userId}.scope.vehicle.{$type}");
            }
        }
    }

    // ── Private Resolvers ─────────────────────────────────────────────

    private function resolveOrgScope(User $user, string $scopeType): ?array
    {
        $employee = $user->employee ?? null;
        if (!$employee) return [];

        return $employee->getUnionScopeFor($scopeType);
    }

    private function resolveVehicleScope(User $user, string $scopeType): ?array
    {
        $employee = $user->employee ?? null;
        if (!$employee) return [];

        return $employee->getVehicleScopeFor($scopeType);
    }

    // ── Validation ────────────────────────────────────────────────────

    private function validateOrgScopeType(string $type): void
    {
        if (!in_array($type, PostOrgScope::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid org scope type: {$type}");
        }
    }

    private function validateVehicleScopeType(string $type): void
    {
        if (!in_array($type, PostVehicleScope::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid vehicle scope type: {$type}");
        }
    }
}
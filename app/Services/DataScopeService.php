<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * DataScopeService - User Data Access Scoping Service
 * 
 * Manages hierarchical data scoping for users, controlling which
 * records they can view/modify based on their organizational assignments.
 * 
 * Scope Types:
 * - branch: Company branch/office
 * - location: Physical location within branch
 * - department: Organizational department
 * - division: Sub-department/team
 * - vertical: Market segment (Personal/Commercial)
 * - brand: Vehicle brand
 * - segment: Vehicle segment
 * - subsegment: Vehicle sub-segment
 * - vehiclemodel: Vehicle model
 * - variant: Vehicle variant
 * - color: Vehicle color
 * 
 * @package App\Services
 */
class DataScopeService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const WILDCARD_ALL = null; // NULL = all records

    /**
     * Assign data scope to user
     * 
     * @param User $user
     * @param string $scopeType One of the scope type constants
     * @param int|null $scopeValue Record ID to grant access to (NULL = all)
     * @param int $hierarchyLevel Depth level in hierarchy
     * @return UserDataScope
     */
    public function assignScope(
        User $user,
        string $scopeType,
        ?int $scopeValue = null,
        int $hierarchyLevel = 0
    ): UserDataScope {
        // Ensure it's a valid scope type
        $this->validateScopeType($scopeType);

        // Delete existing scope of this type for user
        UserDataScope::where('user_id', $user->id)
            ->where('scope_type', $scopeType)
            ->delete();

        // Create new scope
        $scope = UserDataScope::create([
            'user_id' => $user->id,
            'scope_type' => $scopeType,
            'scope_value' => $scopeValue,
            'hierarchy_level' => $hierarchyLevel,
            'status' => 'active',
        ]);

        // Clear cache
        $this->clearScopeCache($user, $scopeType);

        return $scope;
    }

    /**
     * Get IDs accessible to user for a given scope type
     * 
     * Returns:
     * - null: Wildcard (user can access ALL records of this type)
     * - []: No access (user cannot access any records)
     * - [1, 5, 10]: Specific IDs user can access
     * 
     * @param User $user
     * @param string $scopeType
     * @return int[]|null
     */
    public function getAccessibleIds(
        User $user,
        string $scopeType
    ): ?array {
        // Validate scope type
        $this->validateScopeType($scopeType);

        return Cache::remember(
            "user.{$user->id}.scope.{$scopeType}",
            self::CACHE_TTL,
            function () use ($user, $scopeType) {
                $scopes = UserDataScope::where('user_id', $user->id)
                    ->where('scope_type', $scopeType)
                    ->where('status', 'active')
                    ->pluck('scope_value')
                    ->toArray();

                if (empty($scopes)) {
                    return []; // No access for this scope type
                }

                // Check for wildcard (NULL value)
                if (in_array(null, $scopes, true)) {
                    return null; // Wildcard - all records
                }

                return $scopes; // Specific IDs
            }
        );
    }

    /**
     * Check if user has access to specific record
     * 
     * @param User $user
     * @param Model $record
     * @param string $scopeType
     * @return bool
     */
    public function canAccess(
        User $user,
        Model $record,
        string $scopeType
    ): bool {
        // SuperAdmin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accessibleIds = $this->getAccessibleIds($user, $scopeType);

        // Wildcard - user can access all
        if ($accessibleIds === null) {
            return true;
        }

        // No access defined
        if (empty($accessibleIds)) {
            return false;
        }

        // Check if record ID is in accessible list
        return in_array($record->id, $accessibleIds, true);
    }

    /**
     * Build a scoped query for given model and user
     * 
     * Usage:
     * ```php
     * $query = Branch::query();
     * $query = $scopeService->buildScopedQuery($user, $query, 'branch');
     * $branches = $query->get();
     * ```
     * 
     * @param User $user
     * @param Builder $query
     * @param string $scopeType
     * @return Builder
     */
    public function buildScopedQuery(
        User $user,
        Builder $query,
        string $scopeType
    ): Builder {
        // SuperAdmin - no scoping
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $accessibleIds = $this->getAccessibleIds($user, $scopeType);

        // Wildcard - no WHERE clause needed
        if ($accessibleIds === null) {
            return $query;
        }

        // No access - return empty set
        if (empty($accessibleIds)) {
            return $query->whereRaw('1=0'); // Returns no results
        }

        // Specific IDs
        return $query->whereIn('id', $accessibleIds);
    }

    /**
     * Get all accessible records for user of a type
     * 
     * @param User $user
     * @param string $scopeType
     * @param string $modelClass Model class name
     * @return Collection
     */
    public function getAccessibleRecords(
        User $user,
        string $scopeType,
        string $modelClass
    ): Collection {
        $query = $modelClass::query();
        $query = $this->buildScopedQuery($user, $query, $scopeType);

        return $query->active()->get();
    }

    /**
     * Get user's complete scope configuration
     * 
     * Returns array of all scope types with their accessible IDs
     * 
     * @param User $user
     * @return array
     */
    public function getUserScopeConfiguration(User $user): array
    {
        $scopeTypes = [
            'branch', 'location', 'department', 'division',
            'vertical', 'brand', 'segment', 'subsegment',
            'vehiclemodel', 'variant', 'color'
        ];

        $config = [];

        foreach ($scopeTypes as $type) {
            $config[$type] = $this->getAccessibleIds($user, $type);
        }

        return $config;
    }

    /**
     * Validate that record belongs to user's accessible parent scopes
     * 
     * For example, if user is scoped to branch_id=1, they should not
     * see a department that belongs to branch_id=2
     * 
     * @param User $user
     * @param Model $record
     * @param string $scopeType
     * @param string $parentScopeType
     * @param string $parentForeignKey
     * @return bool
     */
    public function validateHierarchy(
        User $user,
        Model $record,
        string $scopeType,
        string $parentScopeType,
        string $parentForeignKey
    ): bool {
        // SuperAdmin bypass
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Get parent ID from record
        $parentId = $record->{$parentForeignKey};

        // Check if user has access to parent
        return $this->canAccessId($user, $parentId, $parentScopeType);
    }

    /**
     * Check if user can access specific ID of a scope type
     * 
     * @param User $user
     * @param int $recordId
     * @param string $scopeType
     * @return bool
     */
    public function canAccessId(
        User $user,
        int $recordId,
        string $scopeType
    ): bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accessibleIds = $this->getAccessibleIds($user, $scopeType);

        // Wildcard
        if ($accessibleIds === null) {
            return true;
        }

        // Check if ID is in accessible list
        return in_array($recordId, $accessibleIds, true);
    }

    /**
     * Revoke scope from user
     * 
     * @param User $user
     * @param string $scopeType
     * @return bool
     */
    public function revokeScope(User $user, string $scopeType): bool
    {
        UserDataScope::where('user_id', $user->id)
            ->where('scope_type', $scopeType)
            ->delete();

        // Clear cache
        $this->clearScopeCache($user, $scopeType);

        return true;
    }

    /**
     * Clear scope cache for user/scope type
     * 
     * @param User $user
     * @param string|null $scopeType If null, clears all scope caches
     * @return void
     */
    public function clearScopeCache(User $user, ?string $scopeType = null): void
    {
        if ($scopeType === null) {
            // Clear all scopes for user
            $scopeTypes = [
                'branch', 'location', 'department', 'division',
                'vertical', 'brand', 'segment', 'subsegment',
                'vehiclemodel', 'variant', 'color'
            ];

            foreach ($scopeTypes as $type) {
                Cache::forget("user.{$user->id}.scope.{$type}");
            }
        } else {
            // Clear specific scope
            Cache::forget("user.{$user->id}.scope.{$scopeType}");
        }
    }

    /**
     * Validate scope type is valid
     * 
     * @param string $scopeType
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateScopeType(string $scopeType): void
    {
        $validTypes = [
            'branch', 'location', 'department', 'division', 'vertical',
            'brand', 'segment', 'subsegment', 'vehiclemodel', 'variant', 'color'
        ];

        if (!in_array($scopeType, $validTypes, true)) {
            throw new \InvalidArgumentException(
                "Invalid scope type: {$scopeType}. Valid types: " .
                implode(', ', $validTypes)
            );
        }
    }

    /**
     * Bulk assign scopes to user
     * 
     * Useful for employee assignment flows
     * 
     * @param User $user
     * @param array $scopes Format: ['branch' => 1, 'location' => null, ...]
     * @return void
     */
    public function assignMultipleScopes(User $user, array $scopes): void
    {
        foreach ($scopes as $scopeType => $scopeValue) {
            $this->assignScope($user, $scopeType, $scopeValue);
        }
    }
}

<?php

namespace App\Services;

use App\Models\User;

class DataScopeService
{
    /**
     * Get allowed codes for a specific scope type.
     * Automatically includes both Primary + Addon scopes from user_scopes table.
     *
     * @return array|null  Returns null = no restriction (show everything)
     */
    public function getCodes(User $user, string $scopeType): ?array
    {
        if (!$user || $user->bypass_data_scoping || $user->isSuperAdmin()) {
            return null;
        }

        return $user->activeScopes()
            ->where('scope_type', strtolower(trim($scopeType)))
            ->pluck('scope_code')
            ->unique()
            ->map(fn($c) => strtoupper($c))
            ->values()
            ->toArray();
    }

    /**
     * Get codes for multiple scope types at once.
     */
    public function getMultipleCodes(User $user, array $scopeTypes): array
    {
        if (!$user || $user->bypass_data_scoping || $user->isSuperAdmin()) {
            return [];
        }

        $types = array_map('strtolower', $scopeTypes);

        return $user->activeScopes()
            ->whereIn('scope_type', $types)
            ->pluck('scope_code')
            ->unique()
            ->map(fn($c) => strtoupper($c))
            ->values()
            ->toArray();
    }
}
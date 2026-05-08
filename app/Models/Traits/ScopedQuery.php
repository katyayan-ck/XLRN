<?php

namespace App\Models\Traits;

use App\Http\Scopes\DataScopeFilter;

/**
 * ScopedQuery — apply to any Eloquent model to enforce Post-based data scoping.
 *
 * Usage in model:
 *   use ScopedQuery;
 *   public string $scopeType   = 'branch';    // PostOrgScope::TYPES value
 *   public string $scopeColumn = 'branch_code'; // column on this model's table
 *   public string $scopeGroup  = 'org';        // 'org' or 'vehicle'
 *
 * To bypass in code: ModelClass::withoutGlobalScope(DataScopeFilter::class)->...
 */
trait ScopedQuery
{
    public static function bootScopedQuery(): void
    {
        static::addGlobalScope(new DataScopeFilter());
    }

    /**
     * Convenience method — query without data scope applied.
     */
    public static function withoutDataScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScope(DataScopeFilter::class);
    }
}

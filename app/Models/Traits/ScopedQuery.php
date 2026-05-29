<?php

namespace App\Models\Traits;

use App\Http\Scopes\DataScopeFilter;

trait ScopedQuery
{
    public static function bootScopedQuery(): void
    {
        static::addGlobalScope(new DataScopeFilter());
    }

    /**
     * Disable all data scoping for this query.
     */
    public static function withoutDataScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScope(DataScopeFilter::class);
    }

    /**
     * Apply only specific scopes (selective / partial scoping).
     */
    public function scopeApplyDataScopes(\Illuminate\Database\Eloquent\Builder $query, array $scopeTypes): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if (!$user || $user->bypass_data_scoping || $user->isSuperAdmin()) {
            return $query;
        }

        $service = app(\App\Services\DataScopeService::class);

        foreach ($scopeTypes as $scopeType) {
            $column = $this->getDataScopeColumn($scopeType);
            if (!$column) continue;

            $codes = $service->getCodes($user, $scopeType);

            if ($codes === null) continue;

            if (empty($codes)) {
                $query->whereRaw('1 = 0');
                return $query;
            }

            $query->whereIn($column, $codes);
        }

        return $query;
    }

    /**
     * Map scope type to database column.
     * Customize this method according to your schema.
     */
    protected function getDataScopeColumn(string $type): ?string
    {
        return match (strtolower($type)) {
            'branch'      => 'primary_branch_code',
            'location'    => 'primary_loc_code',
            'department'  => 'primary_dept_code',
            'division'    => 'primary_div_code',
            'vertical'    => 'vertical_code',
            'segment'     => 'segment_code',
            'sub_segment' => 'sub_segment_code',
            'model'       => 'model_code',
            default       => null,
        };
    }
}
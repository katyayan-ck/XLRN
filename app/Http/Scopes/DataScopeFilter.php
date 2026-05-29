<?php

namespace App\Http\Scopes;

use App\Services\DataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class DataScopeFilter implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!Auth::check()) return;

        $user = Auth::user();

        // Respect bypass flag and Super Admin
        if ($user->bypass_data_scoping || $user->isSuperAdmin()) {
            return;
        }

        $service = app(DataScopeService::class);

        // Support new array format + old single scope format (backward compatibility)
        $scopes = $model->dataScopes ?? [];

        if (empty($scopes) && isset($model->scopeType) && isset($model->scopeColumn)) {
            $scopes = [$model->scopeType => $model->scopeColumn];
        }

        if (empty($scopes)) return;

        foreach ($scopes as $scopeType => $column) {
            $codes = $service->getCodes($user, $scopeType);

            if ($codes === null) {
                continue; // No restriction for this scope type
            }

            if (empty($codes)) {
                $builder->whereRaw('1 = 0'); // No access
                return;
            }

            $builder->whereIn($column, $codes);
        }
    }
}
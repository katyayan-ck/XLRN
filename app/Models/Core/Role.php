<?php

namespace App\Models\Core;

use Spatie\Permission\Models\Role as SpatieRole;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends SpatieRole
{
    use CrudTrait;

    protected $table = 'roles';
    protected $guarded = [];

    // Optional: Add any custom methods here

    /**
     * Get users with this role
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            'model_has_roles',
            'role_id',
            'model_id'
        );
    }

    /**
     * Custom scope: Get active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get permission count
     */
    public function getPermissionsCountAttribute()
    {
        return $this->permissions()->count();
    }
}

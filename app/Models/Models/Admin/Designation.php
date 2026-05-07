<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use SoftDeletes;

    protected $table = 'xlr8_admin_designation';

    protected $fillable = [
        'code',
        'name',
        'description',
        'hierarchy_level',
        'rank',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'hierarchy_level' => 'integer',
        'rank'            => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'desig_code',
            'code'
        );
    }

    public function designationTree(): HasMany
    {
        return $this->hasMany(
            DesigDeptTree::class,
            'desig_code',
            'code'
        );
    }

    public function posts(): HasMany
    {
        return $this->hasMany(
            \App\Models\Iam\Role::class,
            'desig_code',
            'code'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeAtLevel($query, int $level)
    {
        return $query->where('hierarchy_level', $level);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', ucwords(strtolower($category)));
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function getLabelAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
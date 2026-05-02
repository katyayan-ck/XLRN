<?php


namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends BaseModel
{
    protected $table = 'xlr8_admin_designations';

    protected $fillable = [
        'desig_code','name','rank','rank_label',
        'is_top_mgmt','is_active',
        'created_by','updated_by','deleted_by',
    ];

    protected $casts = [
        'rank'        => 'integer',
        'is_top_mgmt' => 'boolean',
        'is_active'   => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\IAM\Post::class, 'desig_code', 'desig_code');
    }

    public function desigDeptTrees(): HasMany
    {
        return $this->hasMany(DesigDeptTree::class, 'desig_code', 'desig_code');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'desig_code', 'desig_code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeTopManagement(Builder $q): Builder
    {
        return $q->where('is_top_mgmt', true);
    }

    public function scopeByRank(Builder $q, int $rank): Builder
    {
        return $q->where('rank', $rank);
    }

    public function scopeAtLeastRank(Builder $q, int $minRank): Builder
    {
        return $q->where('rank', '>=', $minRank);
    }

    public function scopeAtMostRank(Builder $q, int $maxRank): Builder
    {
        return $q->where('rank', '<=', $maxRank);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public static function generateCode(string $prefix = 'DSG'): string
    {
        $last = static::withTrashed()->max('id') ?? 0;
        return strtoupper($prefix) . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
    }

    public function isHigherRankThan(Designation $other): bool
    {
        return $this->rank > $other->rank;
    }
}

// namespace App\Models\Admin;

// use App\Models\BaseModel;
// use Backpack\CRUD\app\Models\Traits\CrudTrait;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Relations\HasMany;

// class Designation extends BaseModel
// {
//     use CrudTrait, HasFactory;

//     protected $table = 'xlr8_admin_designation';

//     protected $fillable = [
//         'code', 'name', 'description',
//         'hierarchy_level', 'rank', 'is_top_level_mgmt', 'is_active',
//     ];

//     protected $casts = [
//         'hierarchy_level'   => 'integer',
//         'rank'              => 'integer',
//         'is_top_level_mgmt' => 'boolean',
//         'is_active'         => 'boolean',
//         'created_at'        => 'datetime',
//         'updated_at'        => 'datetime',
//         'deleted_at'        => 'datetime',
//     ];

//     public function employees(): HasMany
//     {
//         return $this->hasMany(Employee::class, 'desig_code', 'code');
//     }

//     public function posts(): HasMany
//     {
//         return $this->hasMany(\App\Models\IAM\Post::class, 'desig_code', 'code');
//     }

//     /**
//      * Get dept-tree entries showing who this desig reports to in each dept
//      */
//     public function deptTreeEntries(): HasMany
//     {
//         return $this->hasMany(DesignationDeptTree::class, 'desig_code', 'code');
//     }

//     /**
//      * Get the reporting-to designation within a specific department
//      */
//     public function reportsToInDept(string $deptCode): ?Designation
//     {
//         $tree = DesignationDeptTree::where('desig_code', $this->code)
//             ->where(fn($q) => $q->where('dept_code', $deptCode)->orWhere('dept_code', 'ALL'))
//             ->first();

//         return $tree?->reportsToDesignation;
//     }

//     public function scopeByRank($q) { return $q->orderBy('rank'); }
//     public function scopeTlm($q)    { return $q->where('is_top_level_mgmt', true); }

//     public static function generateCode(string $prefix = 'DES'): string
//     {
//         $lastId = static::withTrashed()->max('id') ?? 0;
//         return strtoupper($prefix) . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
//     }
// }
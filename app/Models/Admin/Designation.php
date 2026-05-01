<?php
namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends BaseModel
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_admin_designation';

    protected $fillable = [
        'code', 'name', 'description',
        'hierarchy_level', 'rank', 'is_top_level_mgmt', 'is_active',
    ];

    protected $casts = [
        'hierarchy_level'   => 'integer',
        'rank'              => 'integer',
        'is_top_level_mgmt' => 'boolean',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'desig_code', 'code');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\IAM\Post::class, 'desig_code', 'code');
    }

    /**
     * Get dept-tree entries showing who this desig reports to in each dept
     */
    public function deptTreeEntries(): HasMany
    {
        return $this->hasMany(DesignationDeptTree::class, 'desig_code', 'code');
    }

    /**
     * Get the reporting-to designation within a specific department
     */
    public function reportsToInDept(string $deptCode): ?Designation
    {
        $tree = DesignationDeptTree::where('desig_code', $this->code)
            ->where(fn($q) => $q->where('dept_code', $deptCode)->orWhere('dept_code', 'ALL'))
            ->first();

        return $tree?->reportsToDesignation;
    }

    public function scopeByRank($q) { return $q->orderBy('rank'); }
    public function scopeTlm($q)    { return $q->where('is_top_level_mgmt', true); }

    public static function generateCode(string $prefix = 'DES'): string
    {
        $lastId = static::withTrashed()->max('id') ?? 0;
        return strtoupper($prefix) . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}
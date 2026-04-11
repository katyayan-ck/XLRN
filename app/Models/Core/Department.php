<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Attributes as OA;

#[OA\Schema(title: 'Department')]
/**
 * Department Model
 * 
 * Represents organizational departments (Sales, HR, Finance, etc.)
 */
class Department extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'departments';
    protected string $scopeType = 'department';
    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_department_id',
        'branch_id',
        'head_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Parent department (hierarchical)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    /**
     * Relationship: Child departments
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    /**
     * Relationship: Department head (Person)
     */
    public function head()
    {
        return $this->belongsTo(Person::class, 'head_id');
    }

    /**
     * Relationship: Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship: Posts under department
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relationship: Divisions
     */
    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    /**
     * Relationship: Employees
     */
    public function employees()
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_department_assignments',
            'department_id',
            'employee_id'
        )->withPivot(['from_date', 'to_date', 'is_current']);
    }

    /**
     * Scope: Get top-level departments
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_department_id');
    }

    /**
     * Scope: Get departments in specific branch
     */
    public function scopeInBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get all descendants (flat list)
     */
    public function getAllDescendants()
    {
        $descendants = collect();
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        return $descendants;
    }

    /**
     * Generate auto code
     */
    public static function generateCode($prefix = 'DEPT')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}

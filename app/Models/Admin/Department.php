<?php
namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};

class Department extends BaseModel
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_admin_department';

    protected $fillable = [
        'code', 'name', 'description', 'head_emp_code', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_emp_code', 'code');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class, 'dept_code', 'code');
    }

    public function designationTree(): HasMany
    {
        return $this->hasMany(DesignationDeptTree::class, 'dept_code', 'code');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\IAM\Post::class, 'dept_code', 'code');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'xlr8_admin_emp_department_pivot', 'dept_code', 'emp_code', 'code', 'code')
            ->withPivot(['from_date','to_date','is_current'])->withTimestamps();
    }

    public function scopeTopLevel($q) { return $q->whereNull('parent_dept_code'); }

    public static function generateCode(string $prefix = 'DEPT'): string
    {
        $lastId = static::withTrashed()->max('id') ?? 0;
        return strtoupper($prefix) . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}
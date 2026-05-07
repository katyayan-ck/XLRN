<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};

class Division extends BaseModel
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_admin_division';

    protected $fillable = [
        'department_code',
        'code',
        'name',
        'description',
        'head_emp_code',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_code', 'code');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_emp_code', 'code');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\IAM\Post::class, 'div_code', 'code');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'xlr8_admin_emp_division_pivot', 'div_code', 'emp_code', 'code', 'code')
            ->withPivot(['dept_code', 'from_date', 'to_date', 'is_current'])->withTimestamps();
    }

    // Note: key is (dept_code, code) not just code alone
    public static function generateCode(string $deptCode): string
    {
        $lastId = static::withTrashed()->where('dept_code', $deptCode)->max('id') ?? 0;
        return strtoupper(substr($deptCode, 0, 3)) . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}

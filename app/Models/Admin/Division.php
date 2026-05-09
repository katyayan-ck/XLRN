<?php

namespace App\Models\Admin;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

/**
 * Table: xlr8_admin_division
 * POST-MIGRATION: div_code and department_id DROPPED.
 * Uses dept_code (varchar 10) as code-based soft ref to xlr8_admin_department.code.
 */
class Division extends Model
{
    use SoftDeletes, CrudTrait;

    protected $table = 'xlr8_admin_division';

    protected $fillable = [
        'dept_code',   // replaces department_id (dropped by migration)
        'code',
        'name',
        'description',
        'head_',     // integer — person.id (still in schema)
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    /** dept_code → xlr8_admin_department.code (Eloquent-only, no DB FK) */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_code', 'code');
    }

    /** Employees with this as primary division: employee.primary_div_code → division.code */
    public function primaryEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'primary_div_code', 'code');
    }

    /** DesigDeptTree nodes: desig_dept_tree.div_code → division.code */
    public function designationTree(): HasMany
    {
        return $this->hasMany(DesigDeptTree::class, 'div_code', 'code');
    }

    /** Posts: xlr8_iam_roles.div_code → division.code */
    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\Iam\Post::class, 'div_code', 'code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function scopeForDept($q, string $d)
    {
        return $q->where('dept_code', strtoupper(trim($d)));
    }

    // ── Mutators ──────────────────────────────────────────────────────────────
    public function setCodeAttribute(string $v): void
    {
        $this->attributes['code'] = strtoupper(trim($v));
    }
    public function setDeptCodeAttribute(?string $v): void
    {
        $this->attributes['dept_code'] = $v ? strtoupper(trim($v)) : null;
    }
}

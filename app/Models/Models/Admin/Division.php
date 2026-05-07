<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Division Eloquent Model
 *
 * Table: xlr8_admin_division
 *
 * Post-migration: department relation is code-based (dept_code → department.code).
 * The legacy department_id column and div_code column have been dropped.
 *
 * @property int         $id
 * @property string      $dept_code     Code-based soft ref → Department.code
 * @property string      $code          Division short code (e.g. SLS, SVC, PRT)
 * @property string      $name
 * @property string|null $description
 * @property int|null    $head_id
 * @property bool        $is_active
 */
class Division extends Model
{
    use SoftDeletes;

    protected $table = 'xlr8_admin_division';

    /**
     * The primary natural key for soft references across org tables.
     * All relations use $divCode → division.code, NOT division.id.
     */
    protected $fillable = [
        'dept_code',    // ← replaces department_id
        'code',
        'name',
        'description',
        'head_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Route model binding ───────────────────────────────────────────────────

    /**
     * Resolve route model binding by `code` instead of `id`.
     * Allows: /divisions/{code} → auto-resolves to Division model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    /**
     * Belongs to Department — code-based (dept_code → department.code).
     * No database FK constraint. Uses Eloquent-only soft reference.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'dept_code',   // local key on xlr8_admin_division
            'code'         // owner key on xlr8_admin_department
        );
    }

    /**
     * Employees with this as their primary division.
     * Code-based: employee.primary_div_code → division.code
     */
    public function primaryEmployees(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'primary_div_code', // FK on xlr8_admin_employee
            'code'              // local key on xlr8_admin_division
        );
    }

    /**
     * Desig-Dept tree entries that include this division.
     * Code-based: desig_dept_tree.div_code → division.code
     */
    public function designationTree(): HasMany
    {
        return $this->hasMany(
            \App\Models\Admin\DesigDeptTree::class,
            'div_code', // FK on xlr8_admin_desig_dept_tree
            'code'      // local key on xlr8_admin_division
        );
    }

    /**
     * IAM Roles (Posts) assigned to this division.
     * Code-based: xlr8_iam_roles.div_code → division.code
     */
    public function posts(): HasMany
    {
        return $this->hasMany(
            \App\Models\Iam\Role::class,
            'div_code', // FK on xlr8_iam_roles
            'code'      // local key on xlr8_admin_division
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeForDepartment($query, string $deptCode)
    {
        return $query->where('dept_code', strtoupper(trim($deptCode)));
    }

    // ── Accessors / Mutators ─────────────────────────────────────────────────

    /**
     * Always store code as uppercase.
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function setDeptCodeAttribute(?string $value): void
    {
        $this->attributes['dept_code'] = $value ? strtoupper(trim($value)) : null;
    }
}

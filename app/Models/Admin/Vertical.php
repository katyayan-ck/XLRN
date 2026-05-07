<?php
namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table: xlr8_admin_vertical
 * Schema has BOTH `code` (varchar 255 unique) AND `vert_code` (varchar 10).
 * Employee pivot uses `vertical_code` → vertical's `code` column.
 * `vert_code` is a legacy duplicate — import writes both same value.
 */
class Vertical extends Model
{
    use SoftDeletes;

    protected $table = 'xlr8_admin_vertical';

    protected $fillable = ['code', 'vert_code', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string { return 'code'; }

    // ── Relations ─────────────────────────────────────────────────────────────
    /** Employee pivot: emp_vertical_pivot.vertical_code → vertical.code */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeVerticalAssignment::class, 'vertical_code', 'code');
    }

    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class, EmployeeVerticalAssignment::class,
            'vertical_code', 'code',
            'code', 'employee_code'
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeActive($q) { return $q->where('is_active', true); }

    // ── Mutators ──────────────────────────────────────────────────────────────
    public function setCodeAttribute(string $v): void
    {
        $this->attributes['code']      = strtoupper(trim($v));
        $this->attributes['vert_code'] = substr(strtoupper(trim($v)), 0, 10);
    }
}
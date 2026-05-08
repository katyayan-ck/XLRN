<?php
namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table: xlr8_admin_designation
 * POST-MIGRATION: desig_code column DROPPED. Only `code` exists.
 * is_top_mgmt and parent_desig_code were ADDED by migration.
 * All cross-table soft-refs (employee.desig_code, post.desig_code,
 * desig_dept_tree.desig_code) point to THIS table's `code` column.
 */
class Designation extends Model
{
    use SoftDeletes;

    protected $table = 'xlr8_admin_designation';

    protected $fillable = [
        'code',            // ← ONLY authoritative key. desig_code is GONE.
        'name', 'description',
        'hierarchy_level', 'rank', 'category',
        'is_top_mgmt',        // added by migration
        'parent_desig_code',  // added by migration — soft ref to code in same table
        'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_top_mgmt'     => 'boolean',
        'hierarchy_level' => 'integer',
        'rank'            => 'integer',
    ];

    public function getRouteKeyName(): string { return 'code'; }

    // ── Relations ─────────────────────────────────────────────────────────────
    /** employee.desig_code → designation.code */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'desig_code', 'code');
    }

    /** desig_dept_tree.desig_code → designation.code */
    public function designationTree(): HasMany
    {
        return $this->hasMany(DesigDeptTree::class, 'desig_code', 'code');
    }

    /** xlr8_iam_roles.desig_code → designation.code */
    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\Iam\Post::class, 'desig_code', 'code');
    }

    /** Self-ref: parent_desig_code → code */
    public function parentDesignation()
    {
        return $this->belongsTo(static::class, 'parent_desig_code', 'code');
    }

    public function childDesignations(): HasMany
    {
        return $this->hasMany(static::class, 'parent_desig_code', 'code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeActive($q)                  { return $q->where('is_active', 1); }
    public function scopeTopMgmt($q)                 { return $q->where('is_top_mgmt', true); }
    public function scopeAtLevel($q, int $level)     { return $q->where('hierarchy_level', $level); }
    public function scopeInCategory($q, string $cat) { return $q->where('category', ucwords(strtolower($cat))); }

    // ── Mutators ──────────────────────────────────────────────────────────────
    public function setCodeAttribute(string $v): void
    {
        $this->attributes['code'] = strtoupper(trim($v));
    }

    // ── Accessors ─────────────────────────────────────────────────────────────
    public function getLabelAttribute(): string { return "{$this->code} — {$this->name}"; }
}
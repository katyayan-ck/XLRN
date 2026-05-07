<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use App\Models\IAM\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesigDeptTree extends BaseModel
{
    protected $table = 'xlr8_admin_desig_dept_tree';

    protected $fillable = [
        'tree_code','desig_code','dept_code','div_code',
        'reports_to_code','display_name','level','is_active',
        'created_by','updated_by','deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level'     => 'integer',
    ];

    // ── Relations (NO SQL FKs) ────────────────────────────────────────────

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'desig_code', 'desig_code');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_code', 'dept_code');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'div_code', 'div_code');
    }

    /** Self-referencing — default reports-to in the canonical tree */
    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(static::class, 'reports_to_code', 'tree_code');
    }

    /** All nodes that report to this one by default */
    public function directReports(): HasMany
    {
        return $this->hasMany(static::class, 'reports_to_code', 'tree_code');
    }

    /** Posts anchored to this tree node */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'tree_code', 'tree_code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeByDept(Builder $q, string $deptCode): Builder
    {
        return $q->where('dept_code', $deptCode);
    }

    public function scopeByDesig(Builder $q, string $desigCode): Builder
    {
        return $q->where('desig_code', $desigCode);
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('reports_to_code');
    }
}
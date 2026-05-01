<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePostAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'xlr8_iam_emp_post_pivot';

    protected $fillable = [
        'employee_code',    // FK → xlr8_admin_employee.code  (FIX: was employee_id int)
        'post_code',        // FK → xlr8_iam_post.code        (FIX: was post_id int)
        'is_current',
        'from_date',
        'to_date',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'from_date'  => 'date',
        'to_date'    => 'date',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────
    // FIX: employee via employee_code (string), post via post_code (string)
    // The Post model lives in App\Models\Admin\Post (xlr8_iam_post table)

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_code', 'code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeCurrent($q) { return $q->where('is_current', true)->whereNull('deleted_at'); }
}

<?php
namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignationDeptTree extends BaseModel
{
    protected $table = 'xlr8_admin_designation_dept_tree';

    protected $fillable = [
        'desig_code', 'dept_code', 'reports_to_desig_code', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'desig_code', 'code');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_code', 'code');
    }

    public function reportsToDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'reports_to_desig_code', 'code');
    }
}
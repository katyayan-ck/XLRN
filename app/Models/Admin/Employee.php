<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends BaseModel
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_admin_employee';

    protected $fillable = [
        'code',
        'person_code',
        'desig_code',
        'designation_code',
        'primary_branch_code',
        'primary_loc_code',
        'primary_dept_code',
        'primary_div_code',
        'vertical_code',
        'segment_code',
        'sub_segment_code',
        'mile_id',
        'father_name',
        'employment_type',
        'joining_date',
        'confirmation_date',
        'separation_date',
        'reporting_manager_code',
        'week_off',
        'shift_type',
        'shift_name',
        'biometric_id',
        'pf_eligible',
        'pf_number',
        'uan_number',
        'esi_eligible',
        'esi_number',
        'lwf_eligible',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'pf_eligible'       => 'boolean',
        'esi_eligible'      => 'boolean',
        'lwf_eligible'      => 'boolean',
        'joining_date'      => 'date',
        'confirmation_date' => 'date',
        'separation_date'   => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_code', 'person_code');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_code', 'code');
    }

    public function designationLegacy(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'desig_code', 'code');
    }

    // ─────────────────────────────────────────────────────────────
    // LEGACY RELATIONS CLEANED (No longer using dead pivots)
    // These old belongsToMany relations pointed to orphan pivot tables.
    // They have been removed. Use User scopes or direct queries instead.
    // ─────────────────────────────────────────────────────────────

    // Old relations removed:
    // branches(), locations(), departments(), divisions()
    // They pointed to xlr8_admin_emp_*_pivot tables which are being dropped.

    // ─────────────────────────────────────────────────────────────
    // BOOT
    // ─────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $employee) {
            if (empty($employee->designation_code) && !empty($employee->desig_code)) {
                $employee->designation_code = strtoupper(trim($employee->desig_code));
            }

            if (empty($employee->desig_code) && !empty($employee->designation_code)) {
                $employee->desig_code = strtoupper(trim($employee->designation_code));
            }

            if ($employee->designation_code) {
                $employee->designation_code = strtoupper(trim($employee->designation_code));
            }
            if ($employee->desig_code) {
                $employee->desig_code = strtoupper(trim($employee->desig_code));
            }
        });
    }
}
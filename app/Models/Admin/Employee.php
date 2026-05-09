<?php

namespace App\Models\Admin;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use SoftDeletes, CrudTrait;

    protected $table = 'xlr8_admin_employee';

    // ── Fillable ──────────────────────────────────────────────────────────────
    // FIX: removed designation_id, primary_branch_id, primary_department_id (int FKs)
    //      replaced with desig_code, primary_branch_code, primary_dept_code (string codes)
    // FIX: added primary_div_code, primary_loc_code (were missing)
    // FIX: added payroll split flag — payroll fields remain here; EmployeePayroll
    //      model references this record. If split preferred, move them there.
    // FIX: added person_code (natural key FK) — person_id kept for legacy reads only

    protected $fillable = [
        // Identity
        'code',                 // BMPL-XXXX — employee code, natural PK
        'person_code',          // FK → xlr8_admin_person.person_code

        // Org assignment (all string-code FKs)
        'desig_code',           // FK → xlr8_admin_designation.code
        'primary_branch_code',  // FK → xlr8_admin_branch.code
        'primary_dept_code',    // FK → xlr8_admin_department.code
        'primary_div_code',     // FK → xlr8_admin_division.code (nullable)
        'primary_loc_code',     // FK → xlr8_admin_location.code (physical work location)

        // Vertical / segment scope (primary scope; full scope via xlr8_admin_emp_vehicle_scope)
        'vertical_code',
        'segment_code',
        'sub_segment_code',
        'mile_id',
        // Reporting
        'reporting_emp_code',   // FK → xlr8_admin_employee.code (self-ref)

        // Employment details
        'oem_id',
        'employment_type',      // permanent|apprentice|contract|temporary|probation
        'employment_status',    // active|inactive|separated|terminated|absconded
        'joining_date',
        'probation_end_date',
        'confirmation_date',
        'separation_date',
        'separation_reason',

        // Personal (employee-specific, NOT in person master)
        'blood_group',
        'nationality',
        'father_name',
        'mother_name',
        'passport_no',
        'no_of_children',
        'marriage_date',

        // Shift
        'shift_type',           // flexible|fixed
        'shift_name',
        'late_arrival_window',
        'early_going_window',
        'leave_rule',
        'week_off',
        'wo_work_compensation',
        'comp_off_applicable',

        // Payroll flags
        'pf_eligible',
        'pf_reg_type',
        'pf_number',
        'uan_number',
        'pf_joining_date',
        'eps_membership',
        'abry_eligible',
        'esi_eligible',
        'esi_number',
        'pt_establishment_id',
        'lwf_eligible',
        'biometric_id',

        // Salary
        'salary_payment_mode',      // bank|cash|cheque
        'salary_structure_type',    // statutory_limit|above_statutory_limit

        // Audit
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'joining_date'        => 'date',
        'probation_end_date'  => 'date',
        'confirmation_date'   => 'date',
        'separation_date'     => 'date',
        'pf_joining_date'     => 'date',
        'marriage_date'       => 'date',
        'pf_eligible'         => 'boolean',
        'eps_membership'      => 'boolean',
        'abry_eligible'       => 'boolean',
        'esi_eligible'        => 'boolean',
        'lwf_eligible'        => 'boolean',
        'wo_work_compensation' => 'boolean',
        'comp_off_applicable' => 'boolean',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────
    public function getOfficialMobileAttribute(): ?string
    {
        return $this->person?->mobileContacts()
            ->where('contact_type', 'Office')
            ->whereNull('deleted_at')
            ->value('contact_detail');
    }

    public function getOfficialEmailAttribute(): ?string
    {
        return $this->person?->emailContacts()
            ->where('contact_type', 'Office')
            ->whereNull('deleted_at')
            ->value('contact_detail');
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Employee $e) {
            if (empty($e->code)) {
                $e->code = static::generateCode();
            }
            if (auth()->check() && empty($e->created_by)) {
                $e->created_by = auth()->id();
            }
        });

        static::updating(function (Employee $e) {
            if (auth()->check()) {
                $e->updated_by = auth()->id();
            }
        });

        static::deleting(function (Employee $e) {
            if (!$e->isForceDeleting() && auth()->check()) {
                $e->deleted_by = auth()->id();
                $e->saveQuietly();
            }
        });
    }

    // ── Code generation ───────────────────────────────────────────────────────

    public static function generateCode(string $prefix = 'BMPL'): string
    {
        $last = static::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    // ── Post-Scope Aggregation Methods (Sprint 2) ─────────────────────────────

    /**
     * Get UNION of org scope codes across ALL current active posts.
     * Returns: null = wildcard (all access) | [] = no access | ['NKH','BKN'] = specific
     */
    public function getUnionScopeFor(string $scopeType): ?array
    {
        $assignments = \App\Models\Admin\EmpPostAssignment::forEmployee($this->code)
            ->primary()
            ->current()
            ->with('post.orgScopes')
            ->get();

        if ($assignments->isEmpty()) return [];

        $allCodes = collect();

        foreach ($assignments as $assignment) {
            $codes = $assignment->post?->getOrgScopeFor($scopeType);
            if ($codes === null) return null; // any wildcard post = full wildcard
            $allCodes = $allCodes->merge($codes);
        }

        return $allCodes->unique()->values()->all();
    }

    /**
     * Get UNION of vehicle scope codes across ALL current active posts.
     */
    public function getVehicleScopeFor(string $scopeType): ?array
    {
        $assignments = \App\Models\Admin\EmpPostAssignment::forEmployee($this->code)
            ->primary()
            ->current()
            ->with('post.vehicleScopes')
            ->get();

        if ($assignments->isEmpty()) return [];

        $allCodes = collect();

        foreach ($assignments as $assignment) {
            $codes = $assignment->post?->getVehicleScopeFor($scopeType);
            if ($codes === null) return null;
            $allCodes = $allCodes->merge($codes);
        }

        return $allCodes->unique()->values()->all();
    }

    /**
     * Get the employee's primary post assignment on a given date.
     */
    public function getPostOnDate(string|\Carbon\Carbon $date): ?\App\Models\Admin\EmpPostAssignment
    {
        return \App\Models\Admin\EmpPostAssignment::forEmployee($this->code)
            ->primary()
            ->onDate(\Carbon\Carbon::parse($date))
            ->first();
    }

    // FIX: person via person_code (natural key) — NOT person_id
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_code', 'person_code');
    }

    // FIX: user via employee_code on users table pointing to this record's code
    public function user(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'employee_code', 'code');
    }


    // Self-referencing reporting chain
    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_emp_code', 'code');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reporting_emp_code', 'code');
    }

    // Org — all via code-based FKs (NOT integer IDs)
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'desig_code', 'code');
    }

    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_code', 'code');
    }

    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_dept_code', 'code');
    }

    public function primaryDivision(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'primary_div_code', 'code');
    }

    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'primary_loc_code', 'code');
    }

    // Pivot assignments — all use employee_code on child tables
    public function branchAssignments(): HasMany
    {
        return $this->hasMany(EmployeeBranchAssignment::class, 'employee_code', 'code');
    }

    public function departmentAssignments(): HasMany
    {
        return $this->hasMany(EmployeeDepartmentAssignment::class, 'employee_code', 'code');
    }

    public function locationAssignments(): HasMany
    {
        return $this->hasMany(EmployeeLocationAssignment::class, 'employee_code', 'code');
    }

    public function verticalAssignments(): HasMany
    {
        return $this->hasMany(EmployeeVerticalAssignment::class, 'employee_code', 'code');
    }

    public function postAssignments(): HasMany
    {
        return $this->hasMany(EmployeePostAssignment::class, 'employee_code', 'code');
    }

    public function vehicleScopes(): HasMany
    {
        return $this->hasMany(EmployeeVehicleScope::class, 'employee_code', 'code');
    }

    // Payroll (split table)
    public function payroll(): HasOne
    {
        return $this->hasOne(EmployeePayroll::class, 'emp_code', 'code');
    }

    // Current post (active assignment)
    public function currentPost(): HasOne
    {
        return $this->hasOne(EmployeePostAssignment::class, 'employee_code', 'code')
            ->where('is_current', true)
            ->whereNull('deleted_at');
    }

    // Current active vertical assignment
    public function currentVertical(): HasOne
    {
        return $this->hasOne(EmployeeVerticalAssignment::class, 'employee_code', 'code')
            ->where('is_current', true)
            ->whereNull('deleted_at');
    }

    // All branches this employee can access (via pivot)
    public function accessibleBranches()
    {
        return $this->hasManyThrough(
            Branch::class,
            EmployeeBranchAssignment::class,
            'employee_code', // FK on EmployeeBranchAssignment
            'code',          // FK on Branch
            'code',          // local key on Employee
            'branch_code'    // local key on EmployeeBranchAssignment
        );
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        return $this->person?->display_name
            ?? $this->person?->full_name
            ?? $this->code;
    }

    public function getPrimaryMobileAttribute(): ?string
    {
        return $this->person?->primary_mobile;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->employment_status === 'active';
    }

    public function getTenureAttribute(): ?string
    {
        if (!$this->joining_date) return null;
        $end  = $this->separation_date ?? now();
        $diff = $this->joining_date->diff($end);
        return "{$diff->y}y {$diff->m}m";
    }

    // ── Business logic ────────────────────────────────────────────────────────

    public function getCurrentScope(): array
    {
        return [
            'employee_code'    => $this->code,
            'branch_code'      => $this->primary_branch_code,
            'dept_code'        => $this->primary_dept_code,
            'div_code'         => $this->primary_div_code,
            'location_code'    => $this->primary_loc_code,
            'desig_code'       => $this->desig_code,
            'vertical_code'    => $this->vertical_code,
            'segment_code'     => $this->segment_code,
            'sub_segment_code' => $this->sub_segment_code,
        ];
    }

    public function activate(): void
    {
        $this->update([
            'employment_status' => 'active',
            'separation_date'   => null,
            'separation_reason' => null,
        ]);
    }

    public function separate(string $reason = ''): void
    {
        $this->update([
            'employment_status' => 'separated',
            'separation_date'   => now(),
            'separation_reason' => $reason,
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('employment_status', 'active')->whereNull('deleted_at');
    }

    public function scopeByBranch($q, string $branchCode)
    {
        return $q->where('primary_branch_code', $branchCode);
    }

    public function scopeByDept($q, string $deptCode)
    {
        return $q->where('primary_dept_code', $deptCode);
    }

    public function scopeByDesig($q, string $desigCode)
    {
        return $q->where('desig_code', $desigCode);
    }

    public function scopeSearch($q, string $term)
    {
        return $q->where('code', 'like', "%{$term}%")
            ->orWhere('official_email',  'like', "%{$term}%")
            ->orWhere('official_mobile', 'like', "%{$term}%")
            ->orWhereHas(
                'person',
                fn($p) => $p
                    ->where('first_name',    'like', "%{$term}%")
                    ->orWhere('last_name',   'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%")
            );
    }
}

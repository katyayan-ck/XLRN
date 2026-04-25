<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeBranchAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'xlr8_admin_emp_branch_pivot';

    protected $fillable = [
        'employee_code',
        'branch_code',
        'from_date',
        'to_date',
        'is_primary',
        'is_current',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'from_date'   => 'date',
        'to_date'     => 'date',
        'is_primary'  => 'boolean',
        'is_current'  => 'boolean',
    ];

    /**
     * Relationship: Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    /**
     * Relationship: Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'code');
    }
}

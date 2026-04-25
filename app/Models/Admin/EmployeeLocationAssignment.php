<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLocationAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'xlr8_admin_emp_location_pivot';

    protected $fillable = [
        'employee_code',
        'location_code',
        'branch_code',
        'from_date',
        'to_date',
        'is_current',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'from_date'  => 'date',
        'to_date'    => 'date',
        'is_current' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_code', 'code');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'code');
    }
}

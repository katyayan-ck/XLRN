<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDepartmentAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'employee_department_assignments';

    protected $fillable = [
        'employee_id',
        'department_id',
        'from_date',
        'to_date',
        'is_current',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship: Department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}

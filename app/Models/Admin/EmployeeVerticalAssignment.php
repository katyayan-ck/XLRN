<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeVerticalAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'xlr8_admin_emp_vertical_pivot';

    protected $fillable = [
        'employee_id',
        'vertical_id',
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
     * Relationship: Vertical
     */
    public function vertical()
    {
        return $this->belongsTo(Vertical::class);
    }
}

<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeVerticalAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'employee_vertical_assignments';

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

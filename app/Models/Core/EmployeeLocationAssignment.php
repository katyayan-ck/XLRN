<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLocationAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'employee_location_assignments';

    protected $fillable = [
        'employee_id',
        'location_id',
        'branch_id',
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
     * Relationship: Location
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relationship: Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'employees';

    protected $fillable = [
        'code',
        'person_id',
        'designation_id',
        'primary_branch_id',
        'primary_department_id',
        'joining_date',
        'resignation_date',
        'employment_type',
        'employment_status',
        'is_active',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'resignation_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Person
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Relationship: Designation
     */
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Relationship: Primary Branch
     */
    public function primaryBranch()
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    /**
     * Relationship: Primary Department
     */
    public function primaryDepartment()
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    /**
     * Relationship: Branches
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'employee_branch_assignments')
            ->withPivot(['from_date', 'to_date', 'is_primary', 'is_current']);
    }

    /**
     * Relationship: Departments
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'employee_department_assignments')
            ->withPivot(['from_date', 'to_date', 'is_current']);
    }

    /**
     * Relationship: Locations
     */
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'employee_location_assignments')
            ->withPivot(['from_date', 'to_date', 'is_current']);
    }

    /**
     * Relationship: Verticals
     */
    public function verticals()
    {
        return $this->belongsToMany(Vertical::class, 'employee_vertical_assignments')
            ->withPivot(['from_date', 'to_date', 'is_current']);
    }

    /**
     * Relationship: Posts
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'employee_post_assignments')
            ->withPivot(['from_date', 'to_date', 'is_current', 'assignment_order']);
    }

    /**
     * Get current scope (for RBAC filtering)
     */
    public function getCurrentScope()
    {
        return [
            'branches' => $this->branches()->wherePivot('is_current', true)->pluck('branches.id')->toArray(),
            'departments' => $this->departments()->wherePivot('is_current', true)->pluck('departments.id')->toArray(),
            'locations' => $this->locations()->wherePivot('is_current', true)->pluck('locations.id')->toArray(),
            'verticals' => $this->verticals()->wherePivot('is_current', true)->pluck('verticals.id')->toArray(),
        ];
    }

    /**
     * Generate auto code
     */
    public static function generateCode($prefix = 'EMP')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}

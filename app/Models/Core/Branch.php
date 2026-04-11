<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(title: 'Branch')]
/**
 * Branch Model
 * 
 * Represents company branches/offices
 */
class Branch extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'branches';
    public $scopeType = 'branch';

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'description',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'latitude',
        'longitude',
        'is_head_office',
        'is_active',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Locations under this branch
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Relationship: Employees assigned to branch
     */
    public function employees()
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_branch_assignments',
            'branch_id',
            'employee_id'
        )->withPivot(['from_date', 'to_date', 'is_primary', 'is_current']);
    }

    /**
     * Relationship: Departments
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Scope: Only head office
     */
    public function scopeHeadOffice($query)
    {
        return $query->where('is_head_office', true);
    }

    /**
     * Scope: Get branches by city
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope: Get branches by state
     */
    public function scopeByState($query, $state)
    {
        return $query->where('state', $state);
    }

    /**
     * Generate auto code
     */
    public static function generateCode($prefix = 'BR')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}, {$this->state} {$this->pincode}";
    }
}

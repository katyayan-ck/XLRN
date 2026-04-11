<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'locations';
    public $scopeType = 'location';
    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'description',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship: Employees
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_location_assignments')
            ->withPivot(['from_date', 'to_date', 'is_current']);
    }

    /**
     * Generate auto code
     */
    public static function generateCode($branchCode, $prefix = 'LOC')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $branchCode . '-' . $prefix . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}

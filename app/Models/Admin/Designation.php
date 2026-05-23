<?php

namespace App\Models\Admin;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Designation extends SpatieRole
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_admin_designation';

    protected $fillable = [
        'code',
        'name',
        'guard_name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Ensure Spatie always uses our table.
     */
    public function getTable()
    {
        return 'xlr8_admin_designation';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'designation_code', 'code');
    }
}
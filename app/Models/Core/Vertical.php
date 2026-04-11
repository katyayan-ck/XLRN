<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Vertical Model
 * 
 * Business segments (Personal, Commercial, Fleet, etc.)
 */
class Vertical extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'verticals';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Employee assignments
     */
    public function employees()
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_vertical_assignments',
            'vertical_id',
            'employee_id'
        )->withPivot(['from_date', 'to_date', 'is_current']);
    }

    /**
     * Generate auto code
     */
    public static function generateCode($prefix = 'VER')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($lastId + 1, 2, '0', STR_PAD_LEFT);
    }
}

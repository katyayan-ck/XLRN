<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Designation extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'designations';
    protected string $scopeType = 'designation';
    protected $fillable = [
        'code',
        'name',
        'description',
        'hierarchy_level',
        'is_active',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Employees
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Relationship: Posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Generate auto code
     */
    public static function generateCode($prefix = 'DES')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}

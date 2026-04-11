<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'divisions';

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'description',
        'head_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relationship: Head (Person)
     */
    public function head()
    {
        return $this->belongsTo(Person::class, 'head_id');
    }

    /**
     * Generate auto code
     */
    public static function generateCode($deptCode, $prefix = 'DIV')
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $deptCode . '-' . $prefix . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}

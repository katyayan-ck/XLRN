<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeePostAssignment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'employee_post_assignments';

    protected $fillable = [
        'employee_id',
        'post_id',
        'from_date',
        'to_date',
        'assignment_order',
        'is_current',
        'remarks',
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
     * Relationship: Post
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

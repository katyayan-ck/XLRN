<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PersonContact Model
 * 
 * Emergency and reference contacts for a person
 */
class PersonContact extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'person_contacts';

    protected $fillable = [
        'person_id',
        'type',
        'name',
        'mobile',
        'email',
        'relationship',
        'notes',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
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
     * Scope: Get by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Emergency contacts
     */
    public function scopeEmergency($query)
    {
        return $query->where('type', 'emergency');
    }
}

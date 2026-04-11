<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PersonAddress Model
 * 
 * Multiple addresses for a person (Residential, Office, Billing, etc.)
 */
class PersonAddress extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'person_addresses';

    protected $fillable = [
        'person_id',
        'type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'pincode',
        'country',
        'latitude',
        'longitude',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
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
     * Scope: Get primary address
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true)->first();
    }

    /**
     * Get full address string
     */
    public function getFullAddressAttribute()
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ", {$this->address_line_2}";
        }
        $address .= ", {$this->city}, {$this->state} {$this->pincode}";
        return $address;
    }
}

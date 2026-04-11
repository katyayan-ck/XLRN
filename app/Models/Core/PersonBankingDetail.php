<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PersonBankingDetail Model
 * 
 * Bank account information for persons
 */
class PersonBankingDetail extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'person_banking_details';

    protected $fillable = [
        'person_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'account_type',
        'branch_name',
        'swift_code',
        'is_primary',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
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
     * Scope: Only verified accounts
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Get primary bank
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true)->first();
    }

    /**
     * Get masked account number
     */
    public function getMaskedAccountAttribute()
    {
        $number = $this->account_number;
        return substr_replace($number, '****', 2, -4);
    }
}

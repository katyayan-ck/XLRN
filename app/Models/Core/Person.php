<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User; 

class Person extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'persons';

    protected $fillable = [
        'code',
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'display_name',
        'gender',
        'dob',
        'marital_status',
        'spouse_name',
        'occupation',
        'aadhaar_no',
        'pan_no',
        'gst_no',
        'mobile_primary',
        'mobile_secondary',
        'email_primary',
        'email_secondary',
        'extra_data',
    ];

    protected $casts = [
        'dob' => 'date',
        'extra_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Addresses
     */
    public function addresses()
    {
        return $this->hasMany(PersonAddress::class);
    }

    /**
     * Relationship: Contacts
     */
    public function contacts()
    {
        return $this->hasMany(PersonContact::class);
    }

    /**
     * Relationship: Banking details
     */
    public function bankingDetails()
    {
        return $this->hasMany(PersonBankingDetail::class);
    }

    /**
     * Relationship: Garages
     */
    public function garages()
    {
        return $this->hasMany(Garage::class);
    }

    /**
     * Relationship: Employee record (if person is employee)
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Relationship: User record (if person has login)
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Relationship: Department heads
     */
    public function headedDepartments()
    {
        return $this->hasMany(Department::class, 'head_id');
    }

    /**
     * Relationship: Division heads
     */
    public function headedDivisions()
    {
        return $this->hasMany(Division::class, 'head_id');
    }

    /**
     * Scope: Search persons
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('display_name', 'like', "%{$term}%")
            ->orWhere('email_primary', 'like', "%{$term}%")
            ->orWhere('mobile_primary', 'like', "%{$term}%");
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /**
     * Get primary contact
     */
    public function getPrimaryContactAttribute()
    {
        return $this->contacts()
            ->where('is_primary', true)
            ->first() ?? $this->contacts()->first();
    }

    /**
     * Generate auto code
     */
    public static function generateCode()
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return 'PERS-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        parent::registerMediaCollections();

        $this->addMediaCollection('identity_documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useDisk('public');

        $this->addMediaCollection('profile_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->useDisk('public');
    }
}

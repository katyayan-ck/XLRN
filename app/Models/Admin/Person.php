<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

/**
 * Person Master — stores both Individuals and Legal Entities.
 *
 * Key: person_code is the immutable natural FK used system-wide.
 *   Individual  → PAN (preferred) → Aadhaar → legacy PERS-XXXXXX
 *   Legal Entity → PAN → TAN → GSTIN → legacy PERS-XXXXXX
 *
 * Audit fields (created/updated/deleted _at/_by) fully handled by BaseModel.
 */

class Person extends BaseModel implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, CrudTrait;

    protected $table = 'xlr8_admin_person';

    protected $fillable = [
        'person_code',      // Immutable — enforced in booted()
        'entity_type',      // 'individual' | 'legal_entity'
        'code',             // Legacy internal reference code
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
        'tan_no',
        'gst_no',
        'extra_data',
        // Audit fields managed by BaseModel:
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'entity_type' => 'string',
        'dob'         => 'date',
        'extra_data'  => 'array',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ── Boot — person_code immutability guard ────────────────────

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Person $person) {
            if (empty($person->person_code)) {
                $person->person_code = static::generatePersonCode();
            }
        });

        static::updating(function (Person $person) {
            if ($person->isDirty('person_code')) {
                $person->person_code = $person->getOriginal('person_code');
            }
        });
    }

    public static function generatePersonCode(): string
    {
        $lastId = static::withTrashed()->max('id') ?? 0;
        return 'PERS-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
    }

    // ── Code Derivation ──────────────────────────────────────────

    public static function deriveCode(Person $m): string
    {
        if ($m->entity_type === 'legal_entity') {
            $raw = $m->pan_no ?? $m->tan_no ?? $m->gst_no ?? static::generateLegacyCode();
        } else {
            $raw = $m->pan_no ?? $m->aadhaar_no ?? static::generateLegacyCode();
        }
        return strtoupper(trim($raw));
    }

    public static function generateLegacyCode(): string
    {
        $lastId = static::withTrashed()->max('id') ?? 0;
        return 'PERS-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }

    // ── Relationships ────────────────────────────────────────────

    public function contacts(): HasMany
    {
        return $this->hasMany(PersonContact::class, 'person_id');
    }

    public function mobileContacts(): HasMany
    {
        return $this->contacts()->where('data_type', 'Mobile');
    }

    public function emailContacts(): HasMany
    {
        return $this->contacts()->where('data_type', 'Email');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PersonAddress::class, 'person_id');
    }

    public function bankingDetails(): HasMany
    {
        return $this->hasMany(PersonBankingDetail::class, 'person_id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'person_id');
    }

    /** Linked via person_code natural key, not integer FK */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'person_code', 'person_code');
    }

    public function garages(): HasMany
    {
        return $this->hasMany(Garage::class, 'person_id');
    }

    public function headedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'head_id');
    }

    public function headedDivisions(): HasMany
    {
        return $this->hasMany(Division::class, 'head_id');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getPrimaryMobileAttribute(): ?string
    {
        return $this->mobileContacts()->where('contact_type', 'Primary')->value('contact_detail');
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->emailContacts()->where('contact_type', 'Primary')->value('contact_detail');
    }

    public function getPrimaryAddressAttribute(): ?PersonAddress
    {
        return $this->addresses()->where('address_type', 'Primary')->first();
    }

    public function getPrimaryBankAttribute(): ?PersonBankingDetail
    {
        return $this->bankingDetails()->where('account_type', 'Primary')->first();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        return $query->where(
            fn($q) => $q
                ->where('first_name', 'like', "%$term%")
                ->orWhere('last_name', 'like', "%$term%")
                ->orWhere('display_name', 'like', "%$term%")
                ->orWhere('person_code', 'like', "%$term%")
                ->orWhere('pan_no', 'like', "%$term%")
        );
    }

    public function scopeIndividuals($query)
    {
        return $query->where('entity_type', 'individual');
    }
    public function scopeLegalEntities($query)
    {
        return $query->where('entity_type', 'legal_entity');
    }

    // ── Media ────────────────────────────────────────────────────

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

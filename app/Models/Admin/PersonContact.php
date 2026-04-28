<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores all contact data (Mobile/Email/Landline/Fax) for a Person.
 * One record per contact entry. contact_type describes its role.
 *
 * Rule: Only ONE 'Primary' contact_type per data_type per person_id.
 *       Enforced at DB level via UNIQUE KEY + at model level via makePrimary().
 *
 * Auto-Primary: First entry of each data_type for a person is auto-set to 'Primary'.
 */
class PersonContact extends BaseModel
{
    use SoftDeletes, CrudTrait;

    protected $table = 'xlr8_admin_person_contacts';

    const DATA_TYPES    = ['Mobile', 'Email', 'Landline', 'Fax'];
    const CONTACT_TYPES = ['Primary', 'Alternate', 'Office', 'Home', 'Emergency'];

    protected $fillable = [
        'person_id',
        'data_type',       // Mobile | Email | Landline | Fax
        'contact_type',    // Primary | Alternate | Office | Home | Emergency
        'contact_detail',  // The actual number or email address
        // Audit fields managed by BaseModel:
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Boot — auto-Primary for first entry ──────────────────────

    protected static function booted(): void
    {
        parent::booted(); // ← BaseModel handles audit fields

        static::creating(function (PersonContact $c) {
            // If this is the first entry of this data_type for this person, make it Primary
            $exists = static::where('person_id', $c->person_id)
                ->where('data_type', $c->data_type)
                ->whereNull('deleted_at')
                ->exists();
            if (!$exists) {
                $c->contact_type = 'Primary';
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    // ── Business Logic ───────────────────────────────────────────

    /**
     * Promote this entry to Primary for its data_type.
     * Demotes any existing Primary to 'Alternate'.
     */
    public function makePrimary(): void
    {
        static::where('person_id', $this->person_id)
            ->where('data_type', $this->data_type)
            ->where('contact_type', 'Primary')
            ->where('id', '!=', $this->id)
            ->whereNull('deleted_at')
            ->update(['contact_type' => 'Alternate']);

        $this->contact_type = 'Primary';
        $this->save();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeByDataType($query, string $type)
    {
        return $query->where('data_type', $type);
    }
    public function scopePrimary($query)
    {
        return $query->where('contact_type', 'Primary');
    }
    public function scopeMobiles($query)
    {
        return $query->where('data_type', 'Mobile');
    }
    public function scopeEmails($query)
    {
        return $query->where('data_type', 'Email');
    }
    public function scopeEmergency($query)
    {
        return $query->where('contact_type', 'Emergency');
    }
}

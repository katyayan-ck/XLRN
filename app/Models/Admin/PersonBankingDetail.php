<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bank account details for a Person.
 *
 * account_type → Saving | Salary | Current | OD  (what KIND of account — unchanged)
 * is_primary   → boolean (which account is the person's primary — enforced here)
 *
 * Rule: Only ONE is_primary = true per person_id at any time.
 *       Enforced in makePrimary() + booted() auto-primary logic.
 *       Audit fields fully handled by BaseModel.
 */
class PersonBankingDetail extends BaseModel
{
    use CrudTrait, HasFactory, SoftDeletes;

    protected $table = 'xlr8_admin_person_banking_details';

    // account_type values — what kind of bank account (not primary flag)
    const ACCOUNT_TYPES = ['Saving', 'Salary', 'Current', 'OD'];

    protected $fillable = [
        'person_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'account_type',     // Saving | Salary | Current | OD — category of account
        'branch_name',
        'swift_code',
        'is_primary',       // boolean — true for the person's primary bank account
        'is_verified',
        'verified_at',
        // Audit fields managed by BaseModel:
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_primary'  => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ── Boot — auto-primary for first record ─────────────────────

    protected static function booted(): void
    {
        parent::booted(); // ← BaseModel handles created_by / updated_by / deleted_by

        static::creating(function (PersonBankingDetail $b) {
            // First bank account for this person is automatically primary
            $exists = static::where('person_id', $b->person_id)->whereNull('deleted_at')->exists();
            if (!$exists) {
                $b->is_primary = true;
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
     * Make this the primary bank account for this person.
     * Demotes any existing primary to is_primary = false.
     * Rule: only one is_primary = true per person_id at any time.
     */
    public function makePrimary(): void
    {
        // Demote current primary (if different from this record)
        static::where('person_id', $this->person_id)
            ->where('is_primary', true)
            ->where('id', '!=', $this->id)
            ->whereNull('deleted_at')
            ->update(['is_primary' => false]);

        $this->is_primary = true;
        $this->save();
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getMaskedAccountAttribute(): string
    {
        $number = $this->account_number;
        return substr_replace($number, '****', 2, -4);
    }

    // ── Scopes ───────────────────────────────────────────────────

    /** Returns the single primary bank account for a person (use on collection) */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /** Filter by account category */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
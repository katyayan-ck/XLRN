<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonAddress extends BaseModel
{
    use SoftDeletes;

    protected $table = 'xlr8_admin_person_addresses';

    const ADDRESS_TYPES = ['Primary', 'Office', 'Home', 'Alternate', 'Permanent'];

    protected $fillable = [
        'person_id',
        'address_type',    // Primary | Office | Home | Alternate | Permanent
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'pincode',
        'latitude',
        'longitude',
        // Audit fields managed by BaseModel:
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'latitude'   => 'float',
        'longitude'  => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Boot — auto-Primary for first address ────────────────────

    protected static function booted(): void
    {
        parent::booted(); // ← BaseModel handles audit fields

        static::creating(function (PersonAddress $a) {
            $exists = static::where('person_id', $a->person_id)->whereNull('deleted_at')->exists();
            if (!$exists) $a->address_type = 'Primary';
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    // ── Business Logic ───────────────────────────────────────────

    public function makePrimary(): void
    {
        static::where('person_id', $this->person_id)
            ->where('address_type', 'Primary')
            ->where('id', '!=', $this->id)
            ->whereNull('deleted_at')
            ->update(['address_type' => 'Alternate']);

        $this->address_type = 'Primary';
        $this->save();
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            "{$this->state} {$this->pincode}",
            $this->country,
        ])->filter()->implode(', ');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePrimary($query)           { return $query->where('address_type', 'Primary'); }
    public function scopeByType($query, string $t) { return $query->where('address_type', $t); }
}

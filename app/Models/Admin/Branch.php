<?php
namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Branch extends Model
{
    use SoftDeletes;

    protected $table = 'xlr8_admin_branch';

    // Schema has code (varchar 255 unique) AND branch_code (varchar 10 short org key).
    // All cross-table FKs in employee/location/post use branch_code.
    protected $fillable = [
        'code', 'branch_code', 'name', 'short_name', 'description',
        'phone', 'email', 'address', 'city', 'state', 'pincode', 'country',
        'latitude', 'longitude', 'is_head_office', 'is_active',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'is_active'      => 'boolean',
        'latitude'       => 'float',
        'longitude'      => 'float',
    ];

    public function getRouteKeyName(): string { return 'branch_code'; }

    // ── Relations ─────────────────────────────────────────────────────────────
    public function locations(): HasMany
    {
        // location.branch_code → branch.branch_code
        return $this->hasMany(Location::class, 'branch_code', 'branch_code');
    }

    public function primaryEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'primary_branch_code', 'branch_code');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\Iam\Post::class, 'branch_code', 'branch_code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeActive($q)        { return $q->where('is_active', true); }
    public function scopeHeadOffice($q)    { return $q->where('is_head_office', true); }
    public function scopeByCity($q, $city) { return $q->where('city', $city); }
    public function scopeByState($q, $s)   { return $q->where('state', $s); }

    // ── Mutators ──────────────────────────────────────────────────────────────
    public function setBranchCodeAttribute(string $v): void
    {
        $this->attributes['branch_code'] = strtoupper(trim($v));
    }

    // ── Accessors ─────────────────────────────────────────────────────────────
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address, $this->city, $this->state, $this->pincode
        ]));
    }
}
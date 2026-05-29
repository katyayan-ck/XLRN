<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScope extends BaseModel
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_admin_user_scopes';

    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_code',
        'is_active',
        'from_date',
        'to_date',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'from_date'  => 'date',
        'to_date'    => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    /**
     * Scope active records with valid from_date / to_date range.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('from_date')
                  ->orWhere('from_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('to_date')
                  ->orWhere('to_date', '>=', now()->toDateString());
            });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('scope_type', $type);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
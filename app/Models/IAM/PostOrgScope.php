<?php

namespace App\Models\IAM;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostOrgScope extends BaseModel
{
    protected $table = 'xlr8_iam_post_org_scopes';

    protected $fillable = [
        'post_code','scope_type','scope_value',
        'created_by','updated_by','deleted_by',
    ];

    public const TYPES = ['branch','location','department','division','vertical'];

    // ── Relations ─────────────────────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_code', 'post_code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeForPost(Builder $q, string $postCode): Builder
    {
        return $q->where('post_code', $postCode);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('scope_type', $type);
    }

    public function scopeWildcard(Builder $q): Builder
    {
        return $q->whereNull('scope_value');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isWildcard(): bool
    {
        return $this->scope_value === null;
    }
}
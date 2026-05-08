<?php

namespace App\Models\IAM;

use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Post-to-Post reporting lines, scoped by topic and optional param.
 * Priority resolution: exact param > wildcard param > topic only > DesigDeptTree fallback.
 * Workspace Rule: Pure Eloquent — all queries via named scopes.
 */
class PostReporting extends BaseModel
{
    use HasFactory;
    protected $table = 'xlr8_iam_post_reporting';

    protected $fillable = [
        'from_post_code','to_post_code','topic',
        'param_type','param_value',
        'from_date','to_date','priority','notes',
        'created_by','updated_by','deleted_by',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'priority'  => 'integer',
    ];

    // ── Relations (NO SQL FKs) ────────────────────────────────────────────

    public function fromPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'from_post_code', 'post_code');
    }

    public function toPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'to_post_code', 'post_code');
    }

    // ── Named Scopes ─────────────────────────────────────────────────────

    public function scopeForTopic(Builder $q, string $topic): Builder
    {
        return $q->where('topic', $topic);
    }

    public function scopeOnDate(Builder $q, string|Carbon $date): Builder
    {
        $d = Carbon::parse($date)->toDateString();
        return $q->where('from_date', '<=', $d)
                 ->where(function (Builder $q) use ($d) {
                     $q->whereNull('to_date')
                       ->orWhere('to_date', '>=', $d);
                 });
    }

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->whereNull('to_date');
    }

    public function scopeFrom(Builder $q, string $postCode): Builder
    {
        return $q->where('from_post_code', $postCode);
    }

    public function scopeTo(Builder $q, string $postCode): Builder
    {
        return $q->where('to_post_code', $postCode);
    }

    public function scopeByPriority(Builder $q): Builder
    {
        return $q->orderByDesc('priority');
    }

    /**
     * Filter by param — handles wildcard logic cleanly.
     * null paramType = records where param_type IS NULL (applies to all)
     */
    public function scopeWithParam(Builder $q, ?string $paramType, ?string $paramValue): Builder
    {
        if ($paramType === null) {
            return $q->whereNull('param_type');
        }
        return $q->where('param_type', $paramType)
                 ->where(function (Builder $q) use ($paramValue) {
                     $q->where('param_value', $paramValue)
                       ->orWhereNull('param_value');
                 });
    }

    // ── Computed ──────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->to_date === null;
    }

    
}

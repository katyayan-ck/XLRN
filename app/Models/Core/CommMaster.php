<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Models\User;
use App\Models\Core\Keyvalue;

class CommMaster extends BaseModel implements Auditable
{
    use SoftDeletes;
    use AuditableTrait;

    //     Add to entities (e.g., Booking.php):
    // public function commMaster(): \Illuminate\Database\Eloquent\Relations\MorphOne
    // {
    //     return $this->morphOne(CommMaster::class, 'entityable');
    // }

    protected $fillable = [
        'entityable_type',
        'entityable_id',
        'title',
        'description',
        'status_id',
        'actor_id',
    ];

    public function entityable(): MorphTo
    {
        return $this->morphTo();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Keyvalue::class, 'status_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(CommThread::class);
    }

    public function rootThreads(): HasMany
    {
        return $this->threads()->whereNull('parent_id');
    }
}

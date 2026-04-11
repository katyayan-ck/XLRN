<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use App\Models\User;
use App\Models\Core\Keyvalue;

class CommThread extends BaseModel implements Auditable, HasMedia
{
    use SoftDeletes;
    use AuditableTrait;
    use NodeTrait;
    use InteractsWithMedia;

    protected $fillable = [
        'comm_master_id',
        'parent_id',
        'actor_id',
        'action_id',
        'title',
        'message_text',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    public function commMaster(): BelongsTo
    {
        return $this->belongsTo(CommMaster::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(Keyvalue::class, 'action_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf', 'audio/mpeg', 'video/mp4']);
    }
}

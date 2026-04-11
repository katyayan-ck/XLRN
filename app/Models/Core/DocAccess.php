<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Models\User;
use App\Models\Core\Document;

class DocAccess extends BaseModel implements Auditable
{
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'document_id',
        'user_id',
        'access_type',
        'access_combo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'access_combo' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

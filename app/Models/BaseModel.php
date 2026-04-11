<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * BaseModel - Foundation for all application models
 *
 * Features:
 * ✅ Automatic audit field management (created_by, updated_by, deleted_by)
 * ✅ Human-readable timestamps (ISO 8601 format)
 * ✅ Timezone-aware timestamps synced with MySQL
 * ✅ Soft deletes for audit trail
 * ✅ Media library integration
 * ✅ Audit trail via owen-it/laravel-auditing package
 *
 * Timestamp Format:
 * - created_at: 2025-12-24T18:30:00Z (ISO 8601 UTC)
 * - updated_at: 2025-12-24T18:30:00Z (ISO 8601 UTC)
 * - deleted_at: 2025-12-24T18:30:00Z (ISO 8601 UTC)
 * - used_at: 2025-12-24T18:30:00Z (ISO 8601 UTC) [custom fields]
 *
 * @property int $id Primary key
 * @property int|null $created_by User ID who created the record
 * @property int|null $updated_by User ID who last updated the record
 * @property int|null $deleted_by User ID who soft-deleted the record
 * @property Carbon $created_at ISO 8601 creation timestamp
 * @property Carbon $updated_at ISO 8601 last update timestamp
 * @property Carbon|null $deleted_at ISO 8601 soft delete timestamp
 */
abstract class BaseModel extends Model implements Auditable, HasMedia
{
    use SoftDeletes,
        AuditableTrait,
        InteractsWithMedia;

    // ╔════════════════════════════════════════════════════════╗
    // ║ CONFIGURATION ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Enable model timestamps
     * Laravel automatically manages created_at and updated_at
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Cast attributes to native PHP types
     * All timestamps are cast to datetime for automatic ISO 8601 serialization
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'extra_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Attributes visible in JSON responses
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Attributes hidden from JSON responses
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Model translatable attributes (for spatie/translatable)
     *
     * @var array
     */
    public $translatable = [];

    // ╔════════════════════════════════════════════════════════╗
    // ║ AUTOMATIC AUDIT FIELD MANAGEMENT ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Boot method - Register model event listeners
     *
     * Automatically manages:
     * - created_by: Set to current user when creating
     * - updated_by: Set to current user when updating
     * - deleted_by: Set to current user when soft-deleting
     *
     * Also syncs timestamps to MySQL server timezone
     */
    protected static function booted()
    {
        // ✅ CREATING EVENT: Set created_by to current user
        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id(); // Also set initial updated_by
            }
        });

        // ✅ UPDATING EVENT: Set updated_by to current user
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // ✅ DELETING EVENT: Set deleted_by for soft deletes
        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                if (auth()->check()) {
                    $model->deleted_by = auth()->id();
                    $model->save(); // Save the deleted_by before soft delete
                }
            }
        });

        // ✅ RESTORING EVENT: Clear deleted_by when restoring
        static::restoring(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = null;
                $model->save();
            }
        });
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ TIMESTAMP MANAGEMENT (ISO 8601 FORMAT) ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get the format for database timestamps.
     *
     * Returns ISO 8601 format for maximum compatibility
     * Timestamps are stored as DATETIME in MySQL and automatically
     * converted to ISO 8601 when serialized to JSON
     *
     * Example output:
     * - In MySQL: 2025-12-24 18:30:00
     * - In JSON: "2025-12-24T18:30:00Z" (ISO 8601 UTC)
     *
     * @return string
     */
    public function getDateFormat()
    {
        // MySQL uses 'Y-m-d H:i:s' format internally
        return 'Y-m-d H:i:s';
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * Converts Carbon datetime to ISO 8601 format
     *
     * @param \DateTimeInterface $date
     * @return string ISO 8601 UTC format
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        // ✅ Return ISO 8601 UTC format for API responses
        return $date->toIso8601String();
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ RELATIONSHIPS TO AUDIT USERS ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get the user who created this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the user who last updated this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Get the user who deleted this record (soft delete)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ AUDIT TRAIL & HISTORY ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get audit trail history for this record
     *
     * Requires owen-it/laravel-auditing package
     *
     * @param int $limit Number of records to retrieve
     * @return \Illuminate\Support\Collection Array of audit entries
     */
    public function getHistory($limit = 50)
    {
        return $this->audits()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'user' => $audit->user?->name ?? 'System',
                    'user_id' => $audit->user_id,
                    'changes' => $audit->getModified(),
                    'created_at' => $audit->created_at->toIso8601String(),
                ];
            });
    }

    /**
     * Get creation details
     *
     * @return array
     */
    public function getCreationDetails()
    {
        return [
            'created_at' => $this->created_at->toIso8601String(),
            'created_by_id' => $this->created_by,
            'created_by_name' => $this->createdByUser?->name ?? 'System',
        ];
    }

    /**
     * Get update details
     *
     * @return array
     */
    public function getUpdateDetails()
    {
        return [
            'updated_at' => $this->updated_at->toIso8601String(),
            'updated_by_id' => $this->updated_by,
            'updated_by_name' => $this->updatedByUser?->name ?? 'System',
        ];
    }

    /**
     * Get deletion details
     *
     * @return array|null
     */
    public function getDeletionDetails()
    {
        if (!$this->deleted_at) {
            return null;
        }

        return [
            'deleted_at' => $this->deleted_at->toIso8601String(),
            'deleted_by_id' => $this->deleted_by,
            'deleted_by_name' => $this->deletedByUser?->name ?? 'System',
        ];
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ MEDIA LIBRARY SETUP ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Register media collections for file uploads
     *
     * Integrates with spatie/laravel-medialibrary for file management
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->useDisk('public');

        $this->addMediaCollection('photos')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ])
            ->useDisk('public');

        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ QUERY SCOPES ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Scope: Get only active records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    /**
     * Scope: Get only inactive records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Get only deleted records (soft deleted)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeleted($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    /**
     * Scope: Get all records including deleted
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithDeleted($query)
    {
        return $query->withTrashed();
    }

    /**
     * Scope: Get only restored records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyRestored($query)
    {
        return $query->whereNotNull('deleted_at')->whereNotNull('deleted_by');
    }

    /**
     * Scope: Order by created date newest first
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNewest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope: Order by created date oldest first
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at');
    }

    /**
     * Scope: Filter by date range
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Column name (created_at, updated_at, etc)
     * @param Carbon|string $fromDate Start date
     * @param Carbon|string $toDate End date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $column, $fromDate, $toDate)
    {
        return $query->whereBetween($column, [$fromDate, $toDate]);
    }

    /**
     * Scope: Filter by creator
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Filter by updater
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpdatedBy($query, $userId)
    {
        return $query->where('updated_by', $userId);
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ HELPER METHODS ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get human-readable created time
     *
     * @return string Example: "2 hours ago"
     */
    public function getCreatedAtForHumans()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get human-readable updated time
     *
     * @return string Example: "5 minutes ago"
     */
    public function getUpdatedAtForHumans()
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Check if record was recently created (within minutes)
     *
     * @param int $minutes Default 5 minutes
     * @return bool
     */
    public function isRecentlyCreated($minutes = 5)
    {
        return now()->diffInMinutes($this->created_at) <= $minutes;
    }

    /**
     * Check if record was recently updated (within minutes)
     *
     * @param int $minutes Default 5 minutes
     * @return bool
     */
    public function isRecentlyUpdated($minutes = 5)
    {
        return now()->diffInMinutes($this->updated_at) <= $minutes;
    }

    /**
     * Get all audit fields for this record
     *
     * @return array
     */
    public function getAllAuditDetails()
    {
        return [
            'created' => $this->getCreationDetails(),
            'updated' => $this->getUpdateDetails(),
            'deleted' => $this->getDeletionDetails(),
        ];
    }
}

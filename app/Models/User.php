<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Core\Employee;
use App\Models\Admin\Person;
use App\Models\Core\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{
    HasMany,
    HasOne,
    BelongsToMany,
    BelongsTo
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Core\ReportingHierarchy;
use App\Models\Core\ApprovalHierarchy;
use App\Models\Core\GraphNode;
use App\Models\Core\NotificationsMaster;
use App\Models\Core\Alert;
use App\Models\Core\Notification;
use App\Models\Core\Message;
use App\Models\Core\UserDeviceToken;


/**
 * User Model
 * 
 * Application user authentication and authorization
 * Extended with person linkage, employee linkage, role management, and hierarchical data scoping
 */
class User extends Authenticatable implements Auditable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasRoles;
    use SoftDeletes;
    use AuditableTrait;
    use CrudTrait;

protected $fillable = [
    'user_type_id',    // Kept during transition — remove once user_type fully adopted
    'user_type',       // 'Emp' | 'Cust' | 'DSA' | 'Insurer' | 'Associate'
    'username',        // Unique login handle
    'person_code',     // FK → xlr8_admin_person.person_code
    'employee_code',   // FK → xlr8_admin_employee.code (null for non-employees)
    'avatar',
    'password',
    'is_active',
    'last_login_at',
    // Audit fields managed by BaseModel:
    'created_by',
    'updated_by',
    'deleted_by',
];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
    'password'      => 'hashed',
    'is_active'     => 'boolean',
    'last_login_at' => 'datetime',
    'created_at'    => 'datetime',
    'updated_at'    => 'datetime',
    'deleted_at'    => 'datetime',
];


    /**
     * Guard for roles/permissions
     */
    protected $guard_name = 'web';

    /**
     * Boot: Auto-set audit fields
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                if (auth()->check()) {
                    $model->deleted_by = auth()->id();
                    $model->save();
                }
            }
        });
    }


    // ╔════════════════════════════════════════════════════════╗
    // ║        Notifications RELATIONSHIPS       
    // ╚════════════════════════════════════════════════════════╝


    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function sentNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function sentAlerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'sender_id');
    }

    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function notificationsMaster(): HasOne
    {
        return $this->hasOne(NotificationsMaster::class);
    }

    // Helper method to get or create notifications master
    public function getOrCreateNotificationsMaster(): NotificationsMaster
    {
        return $this->notificationsMaster ?? NotificationsMaster::create([
            'user_id' => $this->id,
            'total_count' => 0,
            'unread_count' => 0,
            'created_by' => $this->id,
        ]);
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║        EXISTING RELATIONSHIPS (Preserved)              ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Relationship: Person record
     */
    public function person(): BelongsTo
{
    // Now linked via person_code natural key, not integer person_id
    return $this->belongsTo(Person::class, 'person_code', 'person_code');
}

    /**
     * Relationship: Employee record
     */
    public function employee(): BelongsTo
{
    return $this->belongsTo(Employee::class, 'employee_code', 'code');
}

/**
 * Backpack/Laravel Auth uses this as the login field.
 * Points to the username column on the users table.
 */
public function username(): string
{
    return 'username';
}

/**
 * Required by Illuminate\Contracts\Auth\Authenticatable.
 * Fetches email from person_contacts since email column no longer exists on users.
 */
public function getEmailForPasswordReset(): string
{
    return $this->person?->primary_email ?? '';
}

/**
 * Required by Laravel notifications (mail channel).
 */
public function routeNotificationForMail(): string
{
    return $this->person?->primary_email ?? '';
}

/**
 * Laravel/Backpack internally calls getAuthIdentifierName() for some flows.
 * This ensures the login form field maps correctly.
 */
public function getAuthIdentifierName(): string
{
    return 'username';
}

public function scopeEmployees($query)
{
    return $query->where('user_type', 'Emp')->whereNotNull('employee_code');
}
   
    /**
     * Update last login timestamp
     */
    public function recordLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    
    /**
     * Get user display name
     */
    public function getDisplayNameAttribute(): string
{
    return $this->person?->display_name
        ?? $this->person?->full_name
        ?? $this->username;
}


    /**
     * Get user avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return asset('images/default-avatar.png');
    }

    public function getPrimaryEmailAttribute(): ?string
{
    return $this->person?->primary_email;
}

public function getPrimaryMobileAttribute(): ?string
{
    return $this->person?->primary_mobile;
}

public function scopeSearch($query, string $term)
{
    return $query->where('username', 'like', "%{$term}%")
        ->orWhereHas('person', fn($q) => $q
            ->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('display_name', 'like', "%{$term}%")
            ->orWhere('person_code', 'like', "%{$term}%")
        );
}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Admin\Person;
use App\Models\Admin\Employee;
use App\Models\Admin\UserType;
use App\Models\Core\UserRoleAssignment;
use App\Models\Core\UserDivisionAssignment;
use App\Models\UserDataScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    protected $table = 'users';

    // ── Fillable ──────────────────────────────────────────────────────────────
    // REMOVED: person_id, employee_id, mile_id, code, mobile, name, email,
    //          email_verified_at, remember_token
    // ADDED:   person_code (natural FK), employee_code (optional natural FK),
    //          username, user_type (enum), created_by, updated_by, deleted_by

    protected $fillable = [
        'user_type_id',     // legacy FK kept during transition; will be phased out
        'user_type',        // hardcoded enum: Emp|Cust|DSA|Insurer|Associate
        'username',         // immutable after first set
        'person_code',      // FK → xlr8_admin_person.person_code (natural key)
        'employee_code',    // nullable FK → xlr8_admin_employee.code (Emp type only)
        'avatar',
        'password',
        'is_active',
        'last_login_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password'      => 'hashed',
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    protected string $guard_name = 'web';

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (auth()->check() && empty($user->created_by)) {
                $user->created_by = auth()->id();
            }
        });

        static::updating(function (User $user) {
            // username is IMMUTABLE after first set
            if ($user->isDirty('username') && !empty($user->getOriginal('username'))) {
                $user->username = $user->getOriginal('username');
            }
            if (auth()->check()) {
                $user->updated_by = auth()->id();
            }
        });

        static::deleting(function (User $user) {
            if (!$user->isForceDeleting() && auth()->check()) {
                $user->deleted_by = auth()->id();
                $user->saveQuietly();
            }
        });
    }

    // ── Core relationships ─────────────────────────────────────────────────────
    // FIX: person() now uses person_code ↔ person_code (natural key, not person_id)
    // FIX: employee() now uses employee_code ↔ code (natural key, not employee_id)

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_code', 'person_code');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

    // ── Notifications / messaging (preserved) ─────────────────────────────────

    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function alerts(): HasMany { return $this->hasMany(Alert::class); }
    public function sentNotifications(): HasMany { return $this->hasMany(Notification::class, 'sender_id'); }
    public function sentAlerts(): HasMany { return $this->hasMany(Alert::class, 'sender_id'); }
    public function messagesSent(): HasMany { return $this->hasMany(Message::class, 'sender_id'); }
    public function messagesReceived(): HasMany { return $this->hasMany(Message::class, 'receiver_id'); }
    public function deviceTokens(): HasMany { return $this->hasMany(UserDeviceToken::class); }

    public function notificationsMaster(): HasOne
    {
        return $this->hasOne(NotificationsMaster::class);
    }

    public function getOrCreateNotificationsMaster(): NotificationsMaster
    {
        return $this->notificationsMaster ?? NotificationsMaster::create([
            'user_id'     => $this->id,
            'total_count' => 0,
            'unread_count'=> 0,
            'created_by'  => $this->id,
        ]);
    }

    // ── RBAC (preserved) ──────────────────────────────────────────────────────

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    public function divisionAssignments(): HasMany
    {
        return $this->hasMany(UserDivisionAssignment::class);
    }

    public function currentRoles()
    {
        return $this->roles()
            ->whereIn('roles.id', function ($q) {
                $q->selectRaw('role_id')
                    ->from('user_role_assignments')
                    ->where('user_id', $this->id)
                    ->where('is_current', true)
                    ->where(fn($s) => $s->whereNull('to_date')->orWhere('to_date', '>=', now()));
            });
    }

    public function currentDivisions(): HasMany
    {
        return $this->divisionAssignments()
            ->where('is_current', true)
            ->where(fn($q) => $q->whereNull('to_date')->orWhere('to_date', '>=', now()));
    }

    // ── Data scoping (preserved) ───────────────────────────────────────────────

    public function userDataScopes(): HasMany
    {
        return $this->hasMany(UserDataScope::class);
    }

    public function getActiveScopes()
    {
        return $this->userDataScopes()->where('status', 'active');
    }

    public function getScopeAccess(string $scopeType): array|null
    {
        if ($this->isSuperAdmin()) return null;

        $scopes = $this->getActiveScopes()
            ->where('scope_type', $scopeType)
            ->pluck('scope_value')
            ->all();

        if (empty($scopes)) return [];
        if (in_array(null, $scopes)) return null;
        return array_filter($scopes);
    }

    public function hasAccessTo(string $scopeType, ?int $entityId = null): bool
    {
        if ($this->isSuperAdmin()) return true;
        $allowed = $this->getScopeAccess($scopeType);
        if ($allowed === []) return false;
        if ($allowed === null) return true;
        if ($entityId === null) return false;
        return in_array($entityId, $allowed);
    }

    public function getAccessibleBranches(): ?array     { return $this->isSuperAdmin() ? null : $this->getScopeAccess('branch'); }
    public function getAccessibleLocations(): ?array    { return $this->isSuperAdmin() ? null : $this->getScopeAccess('location'); }
    public function getAccessibleDepartments(): ?array  { return $this->isSuperAdmin() ? null : $this->getScopeAccess('department'); }
    public function getAccessibleDivisions(): ?array    { return $this->isSuperAdmin() ? null : $this->getScopeAccess('division'); }
    public function getAccessibleVerticals(): ?array    { return $this->isSuperAdmin() ? null : $this->getScopeAccess('vertical'); }
    public function getAccessibleBrands(): ?array       { return $this->isSuperAdmin() ? null : $this->getScopeAccess('brand'); }
    public function getAccessibleSegments(): ?array     { return $this->isSuperAdmin() ? null : $this->getScopeAccess('segment'); }
    public function getAccessibleSubSegments(): ?array  { return $this->isSuperAdmin() ? null : $this->getScopeAccess('sub_segment'); }
    public function getAccessibleVehicleModels(): ?array{ return $this->isSuperAdmin() ? null : $this->getScopeAccess('vehicle_model'); }
    public function getAccessibleVariants(): ?array     { return $this->isSuperAdmin() ? null : $this->getScopeAccess('variant'); }
    public function getAccessibleColors(): ?array       { return $this->isSuperAdmin() ? null : $this->getScopeAccess('color'); }

    // ── Business relationships ─────────────────────────────────────────────────
    // FIX: enquiries/quotes/bookings/sales still use mile_id for legacy foreign key
    //      These should be refactored to employee_code when those tables are updated.

    public function enquiries(): HasMany { return $this->hasMany(Enquiry::class, 'mile_id'); }
    public function quotes(): HasMany    { return $this->hasMany(Quote::class,   'mile_id'); }
    public function bookings(): HasMany  { return $this->hasMany(Booking::class, 'mile_id'); }
    public function sales(): HasMany     { return $this->hasMany(Sale::class,    'mile_id'); }

    public function graphNode(): HasOne
    {
        return $this->hasOne(\App\Models\Core\GraphNode::class);
    }

    public function approvalHierarchies(): HasMany
    {
        return $this->hasMany(ApprovalHierarchy::class, 'approver_id');
    }

    public function reportingHierarchies(): HasMany
    {
        return $this->hasMany(ReportingHierarchy::class);
    }

    // FIX: renamed from subordinates() to avoidconflict with Employee::subordinates()
    //      on User; the User-level subordinate chain is via ReportingHierarchy.
    public function reportingSubordinates(): HasMany
    {
        return $this->hasMany(ReportingHierarchy::class, 'supervisor_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────
    // FIX: name/email/mobile are no longer columns — always proxy via person

    public function getDisplayNameAttribute(): string
    {
        return $this->person?->display_name
            ?? $this->person?->full_name
            ?? $this->username;
    }

    // FIX: getPrimaryEmailAttribute proxies person — not a DB column on users
    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->person?->primary_email;
    }

    // FIX: getPrimaryMobileAttribute proxies person — not a DB column on users
    public function getPrimaryMobileAttribute(): ?string
    {
        return $this->person?->primary_mobile;
    }

    // NEW: official_email/mobile for Emp users come from employee record
    public function getOfficialEmailAttribute(): ?string
    {
        return $this->employee?->official_email;
    }

    public function getOfficialMobileAttribute(): ?string
    {
        return $this->employee?->official_mobile;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : asset('images/default-avatar.png');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'super-admin', 'SuperAdmin']);
    }

    public function isSalesConsultant(): bool
    {
        return $this->hasRole('Sales_Consultant');
    }

    public function isEmployee(): bool
    {
        return $this->user_type === 'Emp' && !empty($this->employee_code);
    }

    public function can($abilities, $arguments = []): bool
    {
        if ($this->isSuperAdmin()) return true;
        return parent::can($abilities, $arguments);
    }

    public function hasAllPermissions($permissions): bool
    {
        foreach ((array) $permissions as $p) {
            if (!$this->hasPermissionTo($p)) return false;
        }
        return true;
    }

    public function hasAnyPermission($permissions): bool
    {
        foreach ((array) $permissions as $p) {
            if ($this->hasPermissionTo($p)) return true;
        }
        return false;
    }

    public function recordLogin(): void
    {
        $this->timestamps = false;
        $this->update(['last_login_at' => now()]);
        $this->timestamps = true;
    }

    // FIX: getScope() now resolves scope via employee → person chain (not direct columns)
    public function getScope(): array
    {
        if ($this->isSuperAdmin()) return ['all_access' => true];
        return $this->employee?->getCurrentScope() ?? [];
    }

    // ── Auth username (Fortify / custom guard) ────────────────────────────────
    // FIX: getEmailForPasswordReset() and findForPassport() must use person proxy
    //      since `email` column is removed from users table.

    public function getEmailForPasswordReset(): string
    {
        return $this->person?->primary_email ?? '';
    }

    public function routeNotificationForMail(): string
    {
        return $this->person?->primary_email ?? '';
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->whereNull('deleted_at');
    }

    // FIX: search no longer checks name/email/mobile columns directly on users
    public function scopeSearch($q, string $term)
    {
        return $q->where('username', 'like', "%{$term}%")
            ->orWhereHas('person', fn($p) => $p
                ->where('first_name',   'like', "%{$term}%")
                ->orWhere('last_name',  'like', "%{$term}%")
                ->orWhere('display_name','like', "%{$term}%")
                ->orWhere('person_code','like', "%{$term}%")
            )
            ->orWhereHas('employee', fn($e) => $e
                ->where('official_email',  'like', "%{$term}%")
                ->orWhere('official_mobile','like', "%{$term}%")
            );
    }

    public function scopeByType($q, string $type)
    {
        return $q->where('user_type', $type);
    }

    public function scopeEmployees($q)
    {
        return $q->where('user_type', 'Emp')->whereNotNull('employee_code');
    }
}

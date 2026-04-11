<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Core\Employee;
use App\Models\Core\Person;
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
        'person_id',
        'employee_id',
        'user_type_id',
        'code',
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'is_active',
        'last_login_at',
        'email_verified_at',
        'remember_token',
        'mile_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
        return $this->belongsTo(Person::class);
    }

    /**
     * Relationship: Employee record
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship: User type
     */
    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    /**
     * Relationship: Role assignments
     */
    public function roleAssignments()
    {
        return $this->hasMany(\App\Models\Core\UserRoleAssignment::class);
    }

    /**
     * Relationship: Division assignments
     */
    public function divisionAssignments()
    {
        return $this->hasMany(\App\Models\Core\UserDivisionAssignment::class);
    }

    /**
     * Relationship: Current roles
     */
    public function currentRoles()
    {
        return $this->roles()
            ->whereIn('roles.id', function ($query) {
                $query->selectRaw('role_id')
                    ->from('user_role_assignments')
                    ->where('user_id', $this->id)
                    ->where('is_current', true)
                    ->where(function ($q) {
                        $q->whereNull('to_date')
                            ->orWhere('to_date', '>=', now());
                    });
            });
    }

    /**
     * Relationship: Current divisions
     */
    public function currentDivisions()
    {
        return $this->divisionAssignments()
            ->where('is_current', true)
            ->where(function ($q) {
                $q->whereNull('to_date')
                    ->orWhere('to_date', '>=', now());
            });
    }

    /**
     * Relationship: Enquiries
     */
    public function enquiries()
    {
        return $this->hasMany(Enquiry::class, 'mile_id');
    }

    /**
     * Relationship: Quotes
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class, 'mile_id');
    }

    /**
     * Relationship: Bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'mile_id');
    }

    /**
     * Relationship: Sales
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'mile_id');
    }

    /**
     * Relationship: GraphNode
     */
    public function graphNode()
    {
        return $this->hasOne(\App\Models\Core\GraphNode::class);
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║     NEW: HIERARCHICAL DATA SCOPING (Added Methods)     ║
    // ║     Does NOT break any existing functionality           ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Relationship: User's assigned data scopes
     * 
     * Table: user_data_scopes
     * Stores: user_id, scope_type (branch|location|dept|etc), scope_value (ID or NULL for wildcard)
     * 
     * @return HasMany
     */
    public function userDataScopes(): HasMany
    {
        return $this->hasMany(\App\Models\UserDataScope::class);
    }

    /**
     * Get active scopes only
     * 
     * @return HasMany
     */
    public function getActiveScopes(): HasMany
    {
        return $this->userDataScopes()->where('status', 'active');
    }

    /**
     * Get user's scope access for a specific type
     * 
     * Returns:
     *   null        → Wildcard (all instances of this type)
     *   []          → No access to this type
     *   [1, 5, 10]  → Specific IDs only
     * 
     * @param string $scopeType (branch, location, department, etc.)
     * @return array|null
     */
    // User.php
    public function getScopeAccess(string $scopeType): array|null
    {
        // Not strictly required anymore for scoping, but safe:
        if ($this->isSuperAdmin()) {
            return null; // wildcard, but scope will not be attached anyway
        }

        $scopes = $this->getActiveScopes()
            ->where('scope_type', $scopeType)
            ->pluck('scope_value')
            ->all();

        if (empty($scopes)) {
            return [];          // no access for that type
        }

        if (in_array(null, $scopes, true)) {
            return null;        // wildcard
        }

        return array_filter($scopes); // specific IDs
    }


    /**
     * Check if user has access to specific entity
     * SuperAdmin automatically has access to everything
     * 
     * @param string $scopeType
     * @param int|null $entityId
     * @return bool
     */
    public function hasAccessTo(string $scopeType, int|null $entityId = null): bool
    {
        // ✅ SuperAdmin has access to everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        $allowedValues = $this->getScopeAccess($scopeType);

        // No scope assigned = no access
        if ($allowedValues === []) {
            return false;
        }

        // Wildcard (null in database) = access to everything
        if ($allowedValues === null) {
            return true;
        }

        // Check if user's entity ID is in allowed list
        if ($entityId === null) {
            return false;
        }

        return in_array($entityId, $allowedValues);
    }


    // ╔════════════════════════════════════════════════════════╗
    // ║        SCOPE ACCESS HELPERS (by entity type)           ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get all branch IDs user has access to
     * @return array|null  → array of IDs, null for wildcard, empty for no access
     */
    public function getAccessibleBranches(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }

        return $this->getScopeAccess('branch');
    }

    /**
     * Get all location IDs user has access to
     */
    public function getAccessibleLocations(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('location');
    }

    /**
     * Get all department IDs user has access to
     */
    public function getAccessibleDepartments(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('department');
    }

    /**
     * Get all division IDs user has access to
     */
    public function getAccessibleDivisions(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('division');
    }

    /**
     * Get all vertical IDs user has access to
     */
    public function getAccessibleVerticals(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('vertical');
    }

    /**
     * Get all brand IDs user has access to
     */
    public function getAccessibleBrands(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('brand');
    }

    /**
     * Get all segment IDs user has access to
     */
    public function getAccessibleSegments(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('segment');
    }

    /**
     * Get all sub-segment IDs user has access to
     */
    public function getAccessibleSubSegments(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('sub_segment');
    }

    /**
     * Get all vehicle model IDs user has access to
     */
    public function getAccessibleVehicleModels(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('vehicle_model');
    }

    /**
     * Get all variant IDs user has access to
     */
    public function getAccessibleVariants(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('variant');
    }

    /**
     * Get all color IDs user has access to
     */
    public function getAccessibleColors(): array|null
    {
        if ($this->isSuperAdmin()) {
            return null;  // wildcard
        }
        return $this->getScopeAccess('color');
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║     EXISTING METHODS (Preserved & Enhanced)            ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get user scope (branches, departments, locations, etc.)
     * 
     * UPDATED: Now includes data scoping information
     * BACKWARD COMPATIBLE: Existing logic still works
     */
    public function getScope()
    {
        if ($this->hasRole('super_admin|admin')) {
            return [
                'all_access' => true,
            ];
        }

        if ($this->employee) {
            $scope = $this->employee->getCurrentScope();

            // Merge with data scopes if any
            $dataScopes = [
                'branches' => $this->getAccessibleBranches(),
                'locations' => $this->getAccessibleLocations(),
                'departments' => $this->getAccessibleDepartments(),
                'divisions' => $this->getAccessibleDivisions(),
                'verticals' => $this->getAccessibleVerticals(),
                'brands' => $this->getAccessibleBrands(),
                'segments' => $this->getAccessibleSegments(),
            ];

            return array_merge($scope, ['data_scopes' => $dataScopes]);
        }

        return [];
    }

    /**
     * Check if user has all required permissions
     */
    public function hasAllPermissions($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has any of the permissions
     */
    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is super admin
     * UPDATED: Now supports both 'super_admin' and 'super-admin' naming conventions
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'super-admin', 'SuperAdmin']);
    }

    /**
     * Check if user is sales consultant
     */
    public function isSalesConsultant(): bool
    {
        return $this->hasRole('Sales_Consultant');
    }

    /**
     * Enhanced permission check with SuperAdmin bypass
     * 
     * SuperAdmin automatically gets true, others checked against permissions
     */
    public function can($abilities, $arguments = []): bool
    {
        // SuperAdmin bypass - can do anything
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Default: use parent class implementation (Spatie permissions)
        return parent::can($abilities, $arguments);
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║         EXISTING SCOPES (Preserved)                    ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Scope: Only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    /**
     * Scope: Only employees
     */
    public function scopeEmployees($query)
    {
        return $query->whereNotNull('employee_id');
    }

    /**
     * Scope: Only admins
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['super_admin', 'admin']);
        });
    }

    /**
     * Scope: Search users
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%");
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║         EXISTING HELPER METHODS (Preserved)            ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Update last login timestamp
     */
    public function recordLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Generate auto code
     */
    public static function generateCode()
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return 'USR-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get user display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->person?->display_name ?? $this->name;
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


    // In User.php, add:
    public function approvalHierarchies()
    {
        return $this->hasMany(ApprovalHierarchy::class, 'approver_id');
    }

    public function reportingHierarchies()
    {
        return $this->hasMany(ReportingHierarchy::class);
    }

    public function subordinates()
    {
        return $this->hasMany(ReportingHierarchy::class, 'supervisor_id');
    }

    // Performance aggregate
    public function aggregatePerformance(string $topic, array $combo, string $metric, $from = null, $to = null): float
    {
        $reportingRoot = $this->reportingHierarchies()->where('topic', $topic)->whereJsonContains('combo_json', $combo)->first();
        if (!$reportingRoot) return 0.0;

        $userIds = $reportingRoot->getSubtreeUserIds($topic, $combo);

        // Example for bookings (adapt for quotes/enquiries)
        //$query = Booking::whereIn('created_by', $userIds)->where('topic', $topic)->whereJsonContains('combo_json', $combo);
        // if ($from && $to) $query->whereBetween('created_at', [$from, $to]);

        // return match ($metric) {
        //     'count' => $query->count(),
        //     'value' => $query->sum('amount'),
        //     default => 0.0,
        // };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Admin\Person;
use App\Models\Admin\Employee;
use App\Models\Admin\UserType;
use App\Models\IAM\Post;
use App\Models\IAM\UserDataScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * User Model
 *
 * Central authentication model with full RBAC, org scoping and person/employee linking.
 * Uses natural keys (person_code, employee_code) instead of legacy integer IDs.
 */
class User extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    protected $table = 'users';

    // ─────────────────────────────────────────────────────────────
    // Fillable & Casts
    // ─────────────────────────────────────────────────────────────
    protected $fillable = [
        'username',           // Immutable login handle
        'password',
        'user_type',          // Emp | Associate | DSA | Insurer | Cust
        'person_code',        // Natural FK → xlr8_admin_person.person_code
        'employee_code',      // Natural FK → xlr8_admin_employee.code (for Emp users)
        'avatar',
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

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (auth()->check() && empty($user->created_by)) {
                $user->created_by = auth()->id();
            }
        });

        static::updating(function (User $user) {
            // Username is immutable after creation
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

    // ─────────────────────────────────────────────────────────────
    // Core Relations
    // ─────────────────────────────────────────────────────────────
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_code', 'person_code');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    // ─────────────────────────────────────────────────────────────
    // Organisation Assignments (via Employee pivots)
    // ─────────────────────────────────────────────────────────────
    public function primaryBranchCode(): ?string
    {
        return $this->employee?->primary_branch_code;
    }

    public function primaryLocationCode(): ?string
    {
        return $this->employee?->primary_loc_code;
    }

    public function primaryDepartmentCode(): ?string
    {
        return $this->employee?->primary_dept_code;
    }

    public function primaryDivisionCode(): ?string
    {
        return $this->employee?->primary_div_code;
    }

public function branches()
{
    return $this->hasManyThrough(
        \App\Models\Admin\Branch::class,
        \App\Models\Admin\EmpBranchPivot::class,
        'employee_code', 'code',
        'employee_code', 'branch_code'
    )->whereNull('to_date');
}

public function locations()
{
    return $this->hasManyThrough(
        \App\Models\Admin\Location::class,
        \App\Models\Admin\EmpLocationPivot::class,
        'employee_code', 'code',
        'employee_code', 'location_code'
    )->whereNull('to_date');
}

public function departments()
{
    return $this->hasManyThrough(
        \App\Models\Admin\Department::class,
        \App\Models\Admin\EmpDepartmentPivot::class,
        'employee_code', 'code',
        'employee_code', 'dept_code'
    )->whereNull('to_date');
}

public function divisions()
{
    return $this->hasManyThrough(
        \App\Models\Admin\Division::class,
        \App\Models\Admin\EmpDivisionPivot::class,
        'employee_code', 'code',
        'employee_code', 'div_code'
    )->whereNull('to_date');
}


    // ─────────────────────────────────────────────────────────────
    // Post & Role Assignments
    // ─────────────────────────────────────────────────────────────
public function posts()
{
    return $this->hasManyThrough(
        \App\Models\IAM\Post::class,
        \App\Models\Admin\EmpPostAssignment::class,
        'emp_code',          // ← pivot uses emp_code
        'post_code',
        'employee_code',
        'post_code'
    )
    ->whereNull('to_date')
    ->where('is_post', 1)
    ->select('xlr8_iam_roles.*');
}

public function primaryPost(): ?string
{
    return $this->posts()
        ->where('assignment_type', 'primary')
        ->value('xlr8_iam_roles.post_code');
}

    /** First active post (convenience) */
    public function post(): ?Post
    {
        return $this->posts()->first();
    }

    // ─────────────────────────────────────────────────────────────
    // Data Scopes (Branch, Location, Department, etc.)
    // ─────────────────────────────────────────────────────────────
    public function dataScopes(): HasMany
    {
        return $this->hasMany(UserDataScope::class);
    }

    public function getActiveScopes(): array
    {
        return $this->dataScopes()
            ->where('status', 'active')
            ->get()
            ->groupBy('scope_type')
            ->map(fn($group) => $group->pluck('scope_value')->filter()->all())
            ->toArray();
    }

    /**
     * Check if user has access to a specific scope value
     * Returns true if wildcard (null) or explicit match
     */
    public function hasScope(string $scopeType, $value = null): bool
    {
        $scopes = $this->getActiveScopes()[$scopeType] ?? [];
        return empty($scopes) || in_array(null, $scopes) || in_array($value, $scopes);
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors & Helpers
    // ─────────────────────────────────────────────────────────────
    public function getDisplayNameAttribute(): string
    {
        return $this->person?->display_name ?? $this->username;
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->person?->primary_email;
    }

    public function getPrimaryMobileAttribute(): ?string
    {
        return $this->person?->primary_mobile;
    }

    public function getOfficialEmailAttribute(): ?string
    {
        return $this->employee?->official_email;
    }

    public function getOfficialMobileAttribute(): ?string
    {
        return $this->employee?->official_mobile;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'super-admin', 'SuperAdmin']);
    }

    public function isEmployee(): bool
    {
        return $this->user_type === 'Emp' && !empty($this->employee_code);
    }

    // Add these accessors
public function getAllEmailsAttribute(): \Illuminate\Support\Collection
{
    return $this->person?->all_emails ?? collect();
}

public function getAllMobilesAttribute(): \Illuminate\Support\Collection
{
    return $this->person?->all_mobiles ?? collect();
}

public function getAllAddressesAttribute(): \Illuminate\Support\Collection
{
    return $this->person?->all_addresses ?? collect();
}

public function getAllBankingAttribute(): \Illuminate\Support\Collection
{
    return $this->person?->all_banking ?? collect();
}

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeEmployees($query)
    {
        return $query->where('user_type', 'Emp')->whereNotNull('employee_code');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('username', 'like', "%{$term}%")
            ->orWhereHas('person', fn($p) => $p->where('display_name', 'like', "%{$term}%")
                ->orWhere('person_code', 'like', "%{$term}%"));
    }

    // ─────────────────────────────────────────────────────────────
    // Password Reset / Notifications
    // ─────────────────────────────────────────────────────────────
    public function getEmailForPasswordReset(): string
    {
        return $this->getPrimaryEmailAttribute() ?? '';
    }

    public function routeNotificationForMail(): string
    {
        return $this->getEmailForPasswordReset();
    }
}
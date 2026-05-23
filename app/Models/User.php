<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Admin\Employee;
use App\Models\Admin\Person;
use App\Models\Admin\UserScope;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use CrudTrait, Notifiable, SoftDeletes, HasFactory, HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'user_type',
        'person_code',
        'employee_code',
        'user_type_id',
        'avatar',
        'is_active',
        'last_login_at',
        'remember_token',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_login_at'  => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_code', 'person_code');
    }

    /**
     * New: Flexible per-user data scopes (Branch/Location/Department/etc.)
     */
    public function scopes()
    {
        return $this->hasMany(UserScope::class);
    }

    public function activeScopes()
    {
        return $this->scopes()->active();
    }

    /**
     * Designation acts as Spatie Role (for authorization/permissions)
     * Returns the primary Designation role assigned to this user.
     */
    public function designation()
    {
        return $this->roles()->where('guard_name', 'web')->first();
    }

    // ─────────────────────────────────────────────────────────────
    // PRIMARY CODE HELPERS (from Employee)
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

    public function primaryVerticalCode(): ?string
    {
        return $this->employee?->vertical_code;
    }

    public function primarySegmentCode(): ?string
    {
        return $this->employee?->segment_code;
    }

    public function primarySubSegmentCode(): ?string
    {
        return $this->employee?->sub_segment_code;
    }

    // ─────────────────────────────────────────────────────────────
    // ALL XXX HELPERS (via Employee pivots - preserved from working version)
    // ─────────────────────────────────────────────────────────────

    public function branches(): Collection
    {
        return $this->employee?->branches() ?? collect();
    }

    public function locations(): Collection
    {
        return $this->employee?->locations() ?? collect();
    }

    public function departments(): Collection
    {
        return $this->employee?->departments() ?? collect();
    }

    public function divisions(): Collection
    {
        return $this->employee?->divisions() ?? collect();
    }

    // Add more (segments, verticals, etc.) in Employee model if needed

    // ─────────────────────────────────────────────────────────────
    // PERSON PROXY ACCESSORS (Mobile / Email / Address / Banking)
    // ─────────────────────────────────────────────────────────────

    public function getPrimaryMobileAttribute(): ?string
    {
        return $this->person?->primary_mobile;
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->person?->primary_email;
    }

    public function getAllMobilesAttribute()
    {
        return $this->person?->all_mobiles ?? collect();
    }

    public function getAllEmailsAttribute()
    {
        return $this->person?->all_emails ?? collect();
    }

    public function getPrimaryAddressAttribute()
    {
        return $this->person?->primary_address;
    }

    public function getAllAddressesAttribute()
    {
        return $this->person?->all_addresses ?? collect();
    }

    public function getPrimaryBankAttribute()
    {
        return $this->person?->primary_bank;
    }

    public function getAllBankingAttribute()
    {
        return $this->person?->all_banking ?? collect();
    }

    // ─────────────────────────────────────────────────────────────
    // NEW USER SCOPE HELPERS (Data Filtering)
    // ─────────────────────────────────────────────────────────────

    public function hasScope(string $type, string $code): bool
    {
        $type = strtoupper(trim($type));
        $code = strtoupper(trim($code));

        return $this->activeScopes()
            ->where('scope_type', $type)
            ->where('scope_code', $code)
            ->exists();
    }

    public function getScopeCodes(string $type): array
    {
        $type = strtoupper(trim($type));

        return $this->activeScopes()
            ->where('scope_type', $type)
            ->pluck('scope_code')
            ->map(fn($c) => strtoupper($c))
            ->toArray();
    }

    public function getAllScopes(): array
    {
        return $this->activeScopes()
            ->get()
            ->groupBy('scope_type')
            ->map(fn($items) => $items->pluck('scope_code')->map(fn($c) => strtoupper($c))->toArray())
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // POSTS / ROLES / PERMISSIONS (Backward Compatible)
    // ─────────────────────────────────────────────────────────────

    public function posts()
    {
        if (!$this->employee) {
            return collect();
        }

        // Reuse existing logic from Employee or keep your current implementation
        return $this->employee->posts();
    }

    public function primaryPost()
    {
        return $this->posts()->where('assignment_type', 'primary')->first();
    }

    // Spatie methods inherited via HasRoles:
    // - getRoleNames()
    // - getAllPermissions()
    // - hasRole()
    // - hasPermissionTo()
    // - assignRole()
    // - removeRole()
    // Since Designation is the Role model, these work directly with Designation codes/names.

    // ─────────────────────────────────────────────────────────────
    // BOOT
    // ─────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        // Optional: Auto-assign designation role when employee designation changes
        // You can add observer or here if needed in future
    }
}
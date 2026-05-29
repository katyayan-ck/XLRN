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
use Illuminate\Database\Eloquent\Builder;
use App\Services\OrgService;

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
        'bypass_data_scoping',
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
        'is_active'           => 'boolean',
        'bypass_data_scoping' => 'boolean',
        'last_login_at'       => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
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

    public function scopes()
    {
        return $this->hasMany(UserScope::class);
    }

    public function activeScopes()
    {
        return $this->scopes()->where('is_active', true);
    }

    // ─────────────────────────────────────────────────────────────
    // DASHBOARD HELPERS
    // ─────────────────────────────────────────────────────────────
    public function getAccessProfileAttribute(): ?array
    {
        return OrgService::getCurrentUser();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->person?->display_name 
            ?? $this->employee?->person?->display_name 
            ?? $this->username 
            ?? 'N/A';
    }

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

    public function primaryPost(): ?string
    {
        return $this->employee?->desig_code ?? $this->employee?->designation_code ?? '—';
    }

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

    // ─────────────────────────────────────────────────────────────
    // LEGACY RELATIONS (Updated to use user_scopes for compatibility)
    // These methods previously used old pivot tables that no longer exist.
    // They now pull data from the modern xlr8_admin_user_scopes table
    // while keeping the same method names and return behavior.
    // ─────────────────────────────────────────────────────────────

    public function branches()
    {
        $codes = $this->activeScopes()
            ->where('scope_type', 'branch')
            ->pluck('scope_code')
            ->unique()
            ->values()
            ->toArray();

        if (empty($codes)) {
            return collect();
        }

        return \App\Models\Admin\Branch::whereIn('code', $codes)
            ->where('is_active', true)
            ->get();
    }

    public function locations()
    {
        $codes = $this->activeScopes()
            ->where('scope_type', 'location')
            ->pluck('scope_code')
            ->unique()
            ->values()
            ->toArray();

        if (empty($codes)) {
            return collect();
        }

        return \App\Models\Admin\Location::whereIn('code', $codes)
            ->where('is_active', true)
            ->get();
    }

    public function departments()
    {
        $codes = $this->activeScopes()
            ->where('scope_type', 'department')
            ->pluck('scope_code')
            ->unique()
            ->values()
            ->toArray();

        if (empty($codes)) {
            return collect();
        }

        return \App\Models\Admin\Department::whereIn('code', $codes)
            ->where('is_active', true)
            ->get();
    }

    public function divisions()
    {
        $codes = $this->activeScopes()
            ->where('scope_type', 'division')
            ->pluck('scope_code')
            ->unique()
            ->values()
            ->toArray();

        if (empty($codes)) {
            return collect();
        }

        return \App\Models\Admin\Division::whereIn('code', $codes)
            ->where('is_active', true)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────
    // DATA SCOPING HELPERS
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

    public function bypassesDataScoping(): bool
    {
        return (bool) $this->bypass_data_scoping;
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
    // BOOT
    // ─────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();
    }
}
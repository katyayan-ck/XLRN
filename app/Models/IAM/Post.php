<?php

namespace App\Models\IAM;

use App\Models\Admin\{Branch, Department, Designation, DesigDeptTree, Division, EmpPostAssignment, Location};
use App\Models\IAM\{PostOrgScope, PostPermission, PostReporting, PostVehicleScope};
use App\Models\BaseModel;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

/**
 * Post = a named organisational position stored as a Spatie Role row.
 * is_post = true discriminates Posts from system Roles.
 * Workspace Rules:
 *   - NO SQL FKs — Eloquent only
 *   - BaseModel provides 6 audit columns + SoftDeletes
 *   - name always equals post_code (Spatie's unique key)
 *   - guard_name always 'web'
 */
class Post extends Role
{
    use SoftDeletes, HasFactory;   // ← HasFactory HERE, same line as SoftDeletes

    protected $table = 'xlr8_iam_roles';

    protected $fillable = [
        'name',           // Spatie required — always = post_code
        'guard_name',     // Spatie required — always 'web'
        'post_code',
        'display_name',
        'is_post',
        'branch_code',
        'loc_code',
        'dept_code',
        'div_code',
        'desig_code',
        'tree_code',
        'seq_no',
        'max_occupants',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_post'       => 'boolean',
        'is_active'     => 'boolean',
        'max_occupants' => 'integer',
        'seq_no'        => 'integer',
        'metadata'      => 'array',
    ];

    // ── Factory ───────────────────────────────────────────────────────────

    protected static function newFactory(): \Database\Factories\IAM\PostFactory
{
    return \Database\Factories\IAM\PostFactory::new();
}

public static function create(array $attributes = []): static
{
    // Generate post_code first if not given
    if (empty($attributes['post_code'])) {
        $attributes['post_code'] = static::generateCode(
            $attributes['loc_code']   ?? $attributes['branch_code'] ?? 'GEN',
            $attributes['dept_code']  ?? 'GEN',
            $attributes['div_code']   ?? null,
            $attributes['desig_code'] ?? 'GEN'
        );
    }

    // Spatie REQUIRES name — set it before parent::create() is called
    $attributes['name']       = $attributes['post_code'];
    $attributes['guard_name'] = $attributes['guard_name'] ?? 'web';
    $attributes['is_post']    = true;

    return parent::create($attributes);
}


    // ── Boot ──────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Global scope: Post queries never accidentally return system Roles
        static::addGlobalScope('posts_only', fn (Builder $q) => $q->where('is_post', true));

        static::creating(function (Post $post) {
            $post->is_post    = true;
            $post->guard_name = $post->guard_name ?? 'web';  // ← 'web' not 'api'

            if (empty($post->post_code)) {
                $post->post_code = static::generateCode(
                    $post->loc_code    ?? $post->branch_code ?? 'GEN',
                    $post->dept_code   ?? 'GEN',
                    $post->div_code    ?? null,
                    $post->desig_code  ?? 'GEN'
                );
            }

            // Spatie uses `name` as the unique role identifier
            $post->name = $post->post_code;

            if (auth()->check()) {
                $post->created_by = $post->created_by ?? auth()->id();
                $post->updated_by = auth()->id();
            }
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('post_code')) {
                $post->name = $post->post_code;
            }
            if (auth()->check()) {
                $post->updated_by = auth()->id();
            }
        });

        static::deleting(function (Post $post) {
            if (!$post->isForceDeleting() && auth()->check()) {
                $post->deleted_by = auth()->id();
                $post->saveQuietly();
            }
        });
    }

    // ── Code-based Relations (NO SQL FKs — Eloquent only) ─────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'branch_code');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'loc_code', 'loc_code');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_code', 'dept_code');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'div_code', 'div_code');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'desig_code', 'desig_code');
    }

    public function desigDeptTree(): BelongsTo
    {
        return $this->belongsTo(DesigDeptTree::class, 'tree_code', 'tree_code');
    }

    // ── Scope Tables ──────────────────────────────────────────────────────

    public function orgScopes(): HasMany
    {
        return $this->hasMany(PostOrgScope::class, 'post_code', 'post_code');
    }

    public function vehicleScopes(): HasMany
    {
        return $this->hasMany(PostVehicleScope::class, 'post_code', 'post_code');
    }

    // ── Permissions ───────────────────────────────────────────────────────

    public function postPermissions(): HasMany
    {
        return $this->hasMany(PostPermission::class, 'post_code', 'post_code');
    }

    // ── Reporting Lines ───────────────────────────────────────────────────

    public function reportingLines(): HasMany
    {
        return $this->hasMany(PostReporting::class, 'from_post_code', 'post_code');
    }

    public function inboundReportingLines(): HasMany
    {
        return $this->hasMany(PostReporting::class, 'to_post_code', 'post_code');
    }

    // ── HR Assignments ────────────────────────────────────────────────────

    public function empAssignments(): HasMany
    {
        return $this->hasMany(EmpPostAssignment::class, 'post_code', 'post_code');
    }

    public function currentAssignments(): HasMany
    {
        return $this->empAssignments()->whereNull('to_date');
    }

    // ── Local Scopes ──────────────────────────────────────────────────────

    public function scopeVacant(Builder $q): Builder
    {
        return $q->withCount(['currentAssignments'])
                 ->having('current_assignments_count', '<', \DB::raw('max_occupants'));
    }

    public function scopeByBranch(Builder $q, string $code): Builder
    {
        return $q->where('branch_code', $code);
    }

    public function scopeByLocation(Builder $q, string $code): Builder
    {
        return $q->where('loc_code', $code);
    }

    public function scopeByDept(Builder $q, string $code): Builder
    {
        return $q->where('dept_code', $code);
    }

    public function scopeByDesig(Builder $q, string $code): Builder
    {
        return $q->where('desig_code', $code);
    }

public function scopeActive(Builder $q): Builder
{
    return $q->where('is_active', true);
}

    // ── Business Logic ────────────────────────────────────────────────────

    public function isVacant(): bool
    {
        return $this->currentAssignments()->count() < $this->max_occupants;
    }

    public function vacancyCount(): int
    {
        return max(0, $this->max_occupants - $this->currentAssignments()->count());
    }

    /**
     * Union org scope for a given scope_type.
     * Returns null = wildcard, [] = no access, [...codes] = specific.
     */
    public function getOrgScopeFor(string $scopeType): ?array
    {
        $scopes = $this->orgScopes->where('scope_type', $scopeType);
        if ($scopes->contains('scope_value', null)) return null;
        return $scopes->pluck('scope_value')->filter()->unique()->values()->all();
    }

    public function getVehicleScopeFor(string $scopeType): ?array
    {
        $scopes = $this->vehicleScopes->where('scope_type', $scopeType);
        if ($scopes->contains('scope_value', null)) return null;
        return $scopes->pluck('scope_value')->filter()->unique()->values()->all();
    }

    // ── Code Generator ────────────────────────────────────────────────────

    /**
     * Generates: BRANCH-DEPT-DIV-DESIG-001  or  BRANCH-DEPT-DESIG-001
     * Uses withoutGlobalScopes() so the is_post global scope doesn't interfere.
     */
    public static function generateCode(
        string $locOrBranch,
        string $deptCode,
        ?string $divCode,
        string $desigCode
    ): string {
        $parts = array_filter([$locOrBranch, $deptCode, $divCode, $desigCode]);
        $base  = strtoupper(implode('-', $parts));
        $seq   = static::withoutGlobalScopes()
                        ->where('is_post', true)
                        ->where('post_code', 'LIKE', "{$base}-%")
                        ->count() + 1;
        return $base . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}


// namespace App\Models\IAM;

// use App\Models\Admin\{Branch, Department, Designation, Division, Employee, Location};
// use App\Models\BaseModel;
// use Backpack\CRUD\app\Models\Traits\CrudTrait;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
// use Spatie\Permission\Models\Role;

// class Post extends BaseModel
// {
//     use CrudTrait, HasFactory;

//     protected $table = 'xlr8_iam_post';

//     protected $fillable = [
//         'code',                  // {LOC}-{DIV}-{DESIG}[-{SEQ}] — immutable, unique
//         'name',                  // Human readable: "Sales FSC Sujangarh 01"
//         'spatie_role_name',      // = code, synced on save
//         'location_code',         // ORM → location
//         'branch_code',           // ORM → branch
//         'dept_code',             // ORM → department
//         'div_code',              // ORM → division (within dept)
//         'desig_code',            // ORM → designation
//         'reports_to_post_code',  // ORM self-relation
//         'max_occupants',
//         'is_active',
//         'description',
//     ];

//     protected $casts = [
//         'is_active'     => 'boolean',
//         'max_occupants' => 'integer',
//         'created_at'    => 'datetime',
//         'updated_at'    => 'datetime',
//         'deleted_at'    => 'datetime',
//     ];

//     // ── Boot ────────────────────────────────────────────────────

//     protected static function booted(): void
//     {
//         parent::booted();

//         static::creating(function (Post $p) {
//             // Auto-generate code if not set
//             if (empty($p->code)) {
//                 $p->code = static::generateCode(
//                     $p->location_code, $p->div_code, $p->desig_code
//                 );
//             }
//             $p->spatie_role_name = $p->code;
//         });

//         static::updating(function (Post $p) {
//             // code is immutable
//             if ($p->isDirty('code')) $p->code = $p->getOriginal('code');
//             $p->spatie_role_name = $p->code; // Always in sync
//         });

//         // Sync Spatie Role on save
//         static::saved(function (Post $p) {
//             Role::firstOrCreate(
//                 ['name' => $p->spatie_role_name, 'guard_name' => 'web'],
//                 ['name' => $p->spatie_role_name, 'guard_name' => 'web']
//             );
//         });
//     }

//     // ── Relationships ────────────────────────────────────────────

//     public function branch(): BelongsTo
//     {
//         return $this->belongsTo(Branch::class, 'branch_code', 'code');
//     }

//     public function location(): BelongsTo
//     {
//         return $this->belongsTo(Location::class, 'location_code', 'code');
//     }

//     public function department(): BelongsTo
//     {
//         return $this->belongsTo(Department::class, 'dept_code', 'code');
//     }

//     public function division(): BelongsTo
//     {
//         return $this->belongsTo(Division::class, 'div_code', 'code');
//     }

//     public function designation(): BelongsTo
//     {
//         return $this->belongsTo(Designation::class, 'desig_code', 'code');
//     }

//     public function reportsTo(): BelongsTo
//     {
//         return $this->belongsTo(Post::class, 'reports_to_post_code', 'code');
//     }

//     public function subordinatePosts(): HasMany
//     {
//         return $this->hasMany(Post::class, 'reports_to_post_code', 'code');
//     }

//     public function employees(): BelongsToMany
//     {
//         return $this->belongsToMany(Employee::class, 'xlr8_admin_emp_post_pivot', 'post_code', 'emp_code', 'code', 'code')
//             ->withPivot(['from_date','to_date','is_current','assignment_order','remarks'])
//             ->withTimestamps();
//     }

//     public function currentEmployees(): BelongsToMany
//     {
//         return $this->employees()->wherePivot('is_current', true);
//     }

//     public function spatieRole(): ?Role
//     {
//         return Role::where('name', $this->spatie_role_name)->first();
//     }

//     // ── Helpers ──────────────────────────────────────────────────

//     public function isVacant(): bool
//     {
//         return $this->currentEmployees()->count() < $this->max_occupants;
//     }

//     public function vacancyCount(): int
//     {
//         return max(0, $this->max_occupants - $this->currentEmployees()->count());
//     }

//     /**
//      * Assign employee to this post and sync Spatie role
//      */
//     public function assignEmployee(Employee $emp, string $fromDate, int $order = 1): void
//     {
//         // Validate: emp's desig_code must match this post's desig_code
//         if ($emp->desig_code !== $this->desig_code) {
//             throw new \DomainException(
//                 "Employee designation [{$emp->desig_code}] does not match post designation [{$this->desig_code}]"
//             );
//         }

//         $this->employees()->attach($emp->code, [
//             'from_date'        => $fromDate,
//             'is_current'       => true,
//             'assignment_order' => $order,
//             'created_by'       => auth()->id(),
//             'created_at'       => now(),
//             'updated_at'       => now(),
//         ]);

//         // Sync Spatie role to user
//         if ($user = $emp->user) {
//             $user->assignRole($this->spatie_role_name);
//         }
//     }

//     /**
//      * Generate post code: {LOC}-{DIV}-{DESIG}[-{SEQ}]
//      */
//     public static function generateCode(string $locCode, string $divCode, string $desigCode): string
//     {
//         $base = strtoupper("{$locCode}-{$divCode}-{$desigCode}");
//         // Check if a post with this base already exists
//         $existing = static::where('code', 'like', "{$base}%")->count();
//         $seq = str_pad($existing + 1, 3, '0', STR_PAD_LEFT);
//         return "{$base}-{$seq}";
//     }

//     // ── Scopes ───────────────────────────────────────────────────

//     public function scopeActive($q) { return $q->where('is_active', true); }
//     public function scopeVacant($q) {
//         return $q->withCount(['currentEmployees'])->having('current_employees_count','<', 1);
//     }
//     public function scopeByBranch($q, $bc)  { return $q->where('branch_code', $bc); }
//     public function scopeByDept($q, $dc)    { return $q->where('dept_code', $dc); }
//     public function scopeByDesig($q, $dc)   { return $q->where('desig_code', $dc); }
// }
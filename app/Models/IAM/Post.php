<?php
namespace App\Models\IAM;

use App\Models\Admin\{Branch, Department, Designation, Division, Employee, Location};
use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Spatie\Permission\Models\Role;

class Post extends BaseModel
{
    use CrudTrait, HasFactory;

    protected $table = 'xlr8_iam_post';

    protected $fillable = [
        'code',                  // {LOC}-{DIV}-{DESIG}[-{SEQ}] — immutable, unique
        'name',                  // Human readable: "Sales FSC Sujangarh 01"
        'spatie_role_name',      // = code, synced on save
        'location_code',         // ORM → location
        'branch_code',           // ORM → branch
        'dept_code',             // ORM → department
        'div_code',              // ORM → division (within dept)
        'desig_code',            // ORM → designation
        'reports_to_post_code',  // ORM self-relation
        'max_occupants',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'max_occupants' => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    // ── Boot ────────────────────────────────────────────────────

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Post $p) {
            // Auto-generate code if not set
            if (empty($p->code)) {
                $p->code = static::generateCode(
                    $p->location_code, $p->div_code, $p->desig_code
                );
            }
            $p->spatie_role_name = $p->code;
        });

        static::updating(function (Post $p) {
            // code is immutable
            if ($p->isDirty('code')) $p->code = $p->getOriginal('code');
            $p->spatie_role_name = $p->code; // Always in sync
        });

        // Sync Spatie Role on save
        static::saved(function (Post $p) {
            Role::firstOrCreate(
                ['name' => $p->spatie_role_name, 'guard_name' => 'web'],
                ['name' => $p->spatie_role_name, 'guard_name' => 'web']
            );
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'code');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_code', 'code');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_code', 'code');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'div_code', 'code');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'desig_code', 'code');
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'reports_to_post_code', 'code');
    }

    public function subordinatePosts(): HasMany
    {
        return $this->hasMany(Post::class, 'reports_to_post_code', 'code');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'xlr8_admin_emp_post_pivot', 'post_code', 'emp_code', 'code', 'code')
            ->withPivot(['from_date','to_date','is_current','assignment_order','remarks'])
            ->withTimestamps();
    }

    public function currentEmployees(): BelongsToMany
    {
        return $this->employees()->wherePivot('is_current', true);
    }

    public function spatieRole(): ?Role
    {
        return Role::where('name', $this->spatie_role_name)->first();
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isVacant(): bool
    {
        return $this->currentEmployees()->count() < $this->max_occupants;
    }

    public function vacancyCount(): int
    {
        return max(0, $this->max_occupants - $this->currentEmployees()->count());
    }

    /**
     * Assign employee to this post and sync Spatie role
     */
    public function assignEmployee(Employee $emp, string $fromDate, int $order = 1): void
    {
        // Validate: emp's desig_code must match this post's desig_code
        if ($emp->desig_code !== $this->desig_code) {
            throw new \DomainException(
                "Employee designation [{$emp->desig_code}] does not match post designation [{$this->desig_code}]"
            );
        }

        $this->employees()->attach($emp->code, [
            'from_date'        => $fromDate,
            'is_current'       => true,
            'assignment_order' => $order,
            'created_by'       => auth()->id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Sync Spatie role to user
        if ($user = $emp->user) {
            $user->assignRole($this->spatie_role_name);
        }
    }

    /**
     * Generate post code: {LOC}-{DIV}-{DESIG}[-{SEQ}]
     */
    public static function generateCode(string $locCode, string $divCode, string $desigCode): string
    {
        $base = strtoupper("{$locCode}-{$divCode}-{$desigCode}");
        // Check if a post with this base already exists
        $existing = static::where('code', 'like', "{$base}%")->count();
        $seq = str_pad($existing + 1, 3, '0', STR_PAD_LEFT);
        return "{$base}-{$seq}";
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeVacant($q) {
        return $q->withCount(['currentEmployees'])->having('current_employees_count','<', 1);
    }
    public function scopeByBranch($q, $bc)  { return $q->where('branch_code', $bc); }
    public function scopeByDept($q, $dc)    { return $q->where('dept_code', $dc); }
    public function scopeByDesig($q, $dc)   { return $q->where('desig_code', $dc); }
}
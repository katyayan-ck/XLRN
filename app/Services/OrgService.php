<?php

namespace App\Services;

use App\Models\Admin\{Branch, Location, Department, Division, Vertical};
use App\Models\Vehicle\{Segment, SubSegment, VehicleModel, Variant, Color};
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class OrgService
{
    private const CACHE_TTL = 3600;

    // ── Master Entities (code-based) ─────────────────────────────────────
    public static function branches(): array
    {
        return Cache::remember('org.branches', self::CACHE_TTL, fn() =>
            Branch::where('is_active', true)
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function locations(?string $branchCode = null): array
    {
        $key = $branchCode ? "org.locations.{$branchCode}" : 'org.locations.all';
        return Cache::remember($key, self::CACHE_TTL, fn() => 
            Location::where('is_active', true)
                ->when($branchCode, fn($q) => $q->where('branch_code', $branchCode))
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function departments(): array
    {
        return Cache::remember('org.departments', self::CACHE_TTL, fn() =>
            Department::where('is_active', true)
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function divisions(?string $deptCode = null): array
    {
        $key = $deptCode ? "org.divisions.{$deptCode}" : 'org.divisions.all';
        return Cache::remember($key, self::CACHE_TTL, fn() => 
            Division::where('is_active', true)
                ->when($deptCode, fn($q) => $q->where('dept_code', $deptCode))
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function verticals(): array
    {
        return Cache::remember('org.verticals', self::CACHE_TTL, fn() =>
            Vertical::where('is_active', true)
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function segments(): array
    {
        return Cache::remember('org.segments', self::CACHE_TTL, fn() =>
            Segment::where('is_active', true)
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function subSegments(?string $segmentCode = null): array
    {
        $key = $segmentCode ? "org.subsegments.{$segmentCode}" : 'org.subsegments.all';
        return Cache::remember($key, self::CACHE_TTL, fn() => 
            SubSegment::where('is_active', true)
                ->when($segmentCode, fn($q) => $q->where('segment_code', $segmentCode))
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function models(?string $segmentCode = null): array
    {
        $key = $segmentCode ? "org.models.{$segmentCode}" : 'org.models.all';
        return Cache::remember($key, self::CACHE_TTL, fn() => 
            VehicleModel::where('is_active', true)
                ->when($segmentCode, fn($q) => $q->where('segment_code', $segmentCode))
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    public static function variants(?string $modelCode = null): array
    {
        $key = $modelCode ? "org.variants.{$modelCode}" : 'org.variants.all';
        return Cache::remember($key, self::CACHE_TTL, fn() => 
            Variant::where('is_active', true)
                ->when($modelCode, fn($q) => $q->where('model_code', $modelCode))
                ->select('code', 'name')
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray()
        );
    }

    // ── User Query Helpers ───────────────────────────────────────────────
    public static function usersByPost(string $postCode, string $branchCode = 'ALL', string $locationCode = 'ALL'): array
{
    return User::whereHas('posts', function ($q) use ($postCode) {
        $q->where('xlr8_iam_roles.post_code', $postCode);   // ← qualified
    })
    ->when($branchCode !== 'ALL', fn($q) => $q->whereHas('branches', fn($b) => $b->where('code', $branchCode)))
    ->when($locationCode !== 'ALL', fn($q) => $q->whereHas('locations', fn($l) => $l->where('code', $locationCode)))
    ->select('id', 'username', 'employee_code')
    ->get()
    ->toArray();
}

    public static function usersByDesignation(string $desigCode, string $branchCode = 'ALL'): array
    {
        return User::whereHas('employee', fn($q) => $q->where('desig_code', $desigCode))
            ->when($branchCode !== 'ALL', fn($q) => $q->whereHas('branches', fn($b) => $b->where('code', $branchCode)))
            ->select('id', 'username', 'employee_code')
            ->get()
            ->toArray();
    }

    // ── Single lookups ───────────────────────────────────────────────────
    public static function branchName(string $code): string { return self::branches()[$code] ?? $code; }
    public static function locationName(string $code): string { return self::locations()[$code] ?? $code; }
    public static function departmentName(string $code): string { return self::departments()[$code] ?? $code; }
    public static function divisionName(string $code): string { return self::divisions()[$code] ?? $code; }
    public static function verticalName(string $code): string { return self::verticals()[$code] ?? $code; }
    public static function segmentName(string $code): string { return self::segments()[$code] ?? $code; }
    public static function subSegmentName(string $code): string { return self::subSegments()[$code] ?? $code; }
}

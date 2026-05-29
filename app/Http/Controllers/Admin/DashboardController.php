<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends CrudController
{
    public function index()
    {
        $user = backpack_user();

        try {
            $user->load([
                'employee',
                'person',
                'scopes' => fn($q) => $q->where('is_active', true),
            ]);

            $details = $this->getCurrentUserDetails($user);

            if ($user->hasRole('super-admin') || $user->bypass_data_scoping) {
                return $this->getSuperAdminDashboard($user, $details);
            }

            return $this->getScopedUserDashboard($user, $details);

        } catch (\Exception $e) {
            Log::error('Dashboard error', ['error' => $e->getMessage()]);
            return view('vendor.backpack.ui.dashboard', ['error' => 'Failed to load dashboard data.']);
        }
    }

    private function getCurrentUserDetails(User $user): array
    {
        $employee = $user->employee;
        $person   = $user->person;

        // Get scopes
        $scopes = collect();
        try {
            $scopes = $user->scopes()
                ->where('is_active', true)
                ->get()
                ->groupBy('scope_type')
                ->map(fn($items) => $items->pluck('scope_code')->unique()->values()->toArray());
        } catch (\Throwable $e) {
            Log::warning('Could not load user scopes', ['error' => $e->getMessage()]);
        }

        // Helper: Format single value as "Name [Code]"
        $formatSingle = function ($modelClass, $code) {
            if (!$code) return '—';
            $item = $modelClass::where('code', $code)->where('is_active', true)->first();
            return $item ? ($item->name . ' [' . $item->code . ']') : $code;
        };

        // Designation with name
        $designationName = '—';
        $desigCode = $employee?->designation_code ?? $employee?->desig_code;
        if ($desigCode) {
            $desig = \App\Models\Admin\Designation::where('code', $desigCode)->first();
            $designationName = $desig ? ($desig->name . ' [' . $desigCode . ']') : $desigCode;
        }

        // Profile image with fallback
        $profileImage = $user->avatar 
            ?? $person?->getFirstMediaUrl('profile_photos')
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->display_name ?? $user->username ?? 'User') 
               . '&background=0D8ABC&color=fff&size=128&rounded=true';

        // Format All Access lists
        $formatList = function ($modelClass, $codes) {
            if (empty($codes)) return [];
            return $modelClass::whereIn('code', $codes)
                ->where('is_active', true)
                ->get()
                ->map(fn($item) => ($item->name ?? $item->code) . ' [' . $item->code . ']')
                ->toArray();
        };

        return [
            'name'                => $user->display_name ?? $user->username ?? 'N/A',
            'username'            => $user->username ?? '—',
            'user_type'           => $user->user_type ?? 'Emp',
            'designation'         => $designationName,
            'mile_id'             => $employee?->mile_id ?? '—',
            'profile_image'       => $profileImage,

            // Primary fields with "Name [Code]" format
            'primary_branch'      => $formatSingle(\App\Models\Admin\Branch::class,     $employee?->primary_branch_code),
            'primary_location'    => $formatSingle(\App\Models\Admin\Location::class,   $employee?->primary_loc_code),
            'primary_department'  => $formatSingle(\App\Models\Admin\Department::class, $employee?->primary_dept_code),
            'primary_division'    => $formatSingle(\App\Models\Admin\Division::class,   $employee?->primary_div_code),
            'vertical'            => $formatSingle(\App\Models\Admin\Vertical::class,   $employee?->vertical_code),
            'segment'             => $formatSingle(\App\Models\Vehicle\Segment::class,  $employee?->segment_code),
            'sub_segment'         => $formatSingle(\App\Models\Vehicle\SubSegment::class, $employee?->sub_segment_code),

            'primary_mobile'      => $person?->primary_mobile ?? '—',
            'primary_email'       => $person?->primary_email ?? '—',
            'primary_address'     => '—',
            'primary_banking'     => '—',

            // All Access (already formatted)
            'all_branches'        => $formatList(\App\Models\Admin\Branch::class,      $scopes['branch'] ?? []),
            'all_locations'       => $formatList(\App\Models\Admin\Location::class,    $scopes['location'] ?? []),
            'all_departments'     => $formatList(\App\Models\Admin\Department::class,  $scopes['department'] ?? []),
            'all_divisions'       => $formatList(\App\Models\Admin\Division::class,    $scopes['division'] ?? []),
            'all_verticals'       => $formatList(\App\Models\Admin\Vertical::class,    $scopes['vertical'] ?? []),
            'all_segments'        => $formatList(\App\Models\Vehicle\Segment::class,   $scopes['segment'] ?? []),
            'all_sub_segments'    => $formatList(\App\Models\Vehicle\SubSegment::class,$scopes['sub_segment'] ?? []),
            'all_models'          => $formatList(\App\Models\Vehicle\VehicleModel::class, $scopes['model'] ?? []),
        ];
    }

    private function getSuperAdminDashboard(User $user, array $details)
    {
        $stats = Cache::remember('dashboard.superadmin.stats', 3600, fn() => [
            'total_branches'    => \App\Models\Admin\Branch::count(),
            'total_locations'   => \App\Models\Admin\Location::count(),
            'total_departments' => \App\Models\Admin\Department::count(),
            'total_employees'   => \App\Models\Admin\Employee::count(),
            'active_users'      => User::where('is_active', true)->count(),
        ]);

        return view('vendor.backpack.ui.dashboard', [
            'user'                 => $user,
            'user_access_label'    => '🔑 Full Access (SuperAdmin)',
            'current_user_details' => $details,
            'total_branches'       => $stats['total_branches'] ?? 0,
            'total_locations'      => $stats['total_locations'] ?? 0,
            'total_departments'    => $stats['total_departments'] ?? 0,
            'total_employees'      => $stats['total_employees'] ?? 0,
            'active_users'         => $stats['active_users'] ?? 0,
        ]);
    }

    private function getScopedUserDashboard(User $user, array $details)
    {
        $stats = Cache::remember('dashboard.scoped.' . $user->id, 900, fn() => [
            'total_branches'    => $user->branches()->count(),
            'total_locations'   => $user->locations()->count(),
            'total_departments' => $user->departments()->count(),
            'total_employees'   => \App\Models\Admin\Employee::count(),
            'active_users'      => User::where('is_active', true)->count(),
        ]);

        return view('vendor.backpack.ui.dashboard', [
            'user'                 => $user,
            'user_access_label'    => '👁️ Scoped Access',
            'current_user_details' => $details,
            'total_branches'       => $stats['total_branches'] ?? 0,
            'total_locations'      => $stats['total_locations'] ?? 0,
            'total_departments'    => $stats['total_departments'] ?? 0,
            'total_employees'      => $stats['total_employees'] ?? 0,
            'active_users'         => $stats['active_users'] ?? 0,
        ]);
    }
}
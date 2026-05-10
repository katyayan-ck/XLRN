<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\{Branch, Location, Department, Employee};
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
            Log::info('Dashboard accessed', ['user_id' => $user->id, 'username' => $user->username]);

            $details = $this->getCurrentUserDetails($user);

            if ($user->isSuperAdmin()) {
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

        return [
            'name'                => $user->display_name ?? $user->username ?? 'N/A',
            'username'            => $user->username ?? '—',
            'user_type'           => $user->user_type ?? 'Emp',
            'designation'         => $employee?->desig_code ?? '—',
            'mile_id'             => $employee?->mile_id ?? '—',
            'vertical'            => $employee?->vertical_code ?? '—',
            'segment'             => $employee?->segment_code ?? '—',
            'sub_segment'         => $employee?->sub_segment_code ?? '—',

            'primary_branch'      => $user->primaryBranchCode() ?? '—',
            'primary_location'    => $user->primaryLocationCode() ?? '—',
            'primary_department'  => $user->primaryDepartmentCode() ?? '—',
            'primary_division'    => $user->primaryDivisionCode() ?? '—',
            'primary_post'        => $user->primaryPost() ?? '—',

            'all_branches'        => $user->branches()->pluck('name', 'code')->toArray(),
            'all_locations'       => $user->locations()->pluck('name', 'code')->toArray(),
            'all_departments'     => $user->departments()->pluck('name', 'code')->toArray(),
            'all_divisions'       => $user->divisions()->pluck('name', 'code')->toArray(),

            'primary_mobile'      => $user->primary_mobile ?? '—',
            'primary_email'       => $user->primary_email ?? '—',
            'all_mobiles'         => $user->all_mobiles->toArray(),
            'all_emails'          => $user->all_emails->toArray(),

            'primary_address'     => $person?->primary_address?->full_address ?? '—',
            'all_addresses'       => $person?->all_addresses->pluck('full_address')->toArray(),
            'primary_banking'     => $person?->primary_bank?->masked_account ?? '—',
            'all_banking'         => $person?->all_banking->pluck('masked_account')->toArray(),

            'roles'               => $user->getRoleNames()->toArray(),
            'posts'               => $user->posts()->pluck('post_code')->toArray(),
        ];
    }

    private function getSuperAdminDashboard(User $user, array $details)
    {
        $stats = Cache::remember('dashboard.superadmin.stats', 3600, fn() => [
            'total_branches'   => Branch::count(),
            'total_locations'  => Location::count(),
            'total_departments'=> Department::count(),
            'total_employees'  => Employee::count(),
            'active_users'     => User::where('is_active', true)->count(),
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
            'total_branches'   => $user->branches()->count(),
            'total_locations'  => $user->locations()->count(),
            'total_departments'=> $user->departments()->count(),
            'total_employees'  => Employee::count(),
            'active_users'     => User::where('is_active', true)->count(),
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

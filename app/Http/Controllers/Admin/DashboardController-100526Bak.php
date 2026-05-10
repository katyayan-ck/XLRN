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
// print_r("<br> User array : "); print_r($user->toArray());
// print_r("<br> Primary Branch Code : "); print_r($user->primaryBranchCode());
// print_r("<br> Primary Location Code : "); print_r($user->primaryLocationCode());
// print_r("<br> Primary Department Code : "); print_r($user->primaryDepartmentCode());
// print_r("<br> Primary Division Code : "); print_r($user->primaryDivisionCode());
// print_r("<br> Primary Post : "); print_r($user->primaryPost());
// print_r("<br> All Branches : "); print_r($user->branches()->pluck('name', 'code')->toArray());
// print_r("<br> All Locations : "); print_r($user->locations()->pluck('name', 'code')->toArray());
// print_r("<br> All Departments : "); print_r($user->departments()->pluck('name', 'code')->toArray());
// print_r("<br> All Divisions : "); print_r($user->divisions()->pluck('name', 'code')->toArray());
// print_r("<br> Designation : "); print_r($user->employee->desig_code ?? '');
// print_r("<br> Vertical : "); print_r($user->employee->vertical_code ?? '');
// print_r("<br> Segment : "); print_r($user->employee->segment_code ?? '');
// print_r("<br> Sub Segment : "); print_r($user->employee->sub_segment_code ?? '');
// print_r("<br> Primary Mobile : "); print_r($user->primary_mobile);
// print_r("<br> Primary Email : "); print_r($user->primary_email);
// print_r("<br> All Mobiles : "); print_r($user->all_mobiles->toArray());
// print_r("<br> All Emails : "); print_r($user->all_emails->toArray());
// print_r("<br> Primary Address : "); print_r($user->person->primary_address->full_address ?? '');
// print_r("<br> All Addresses : "); print_r($user->person->all_addresses->pluck('full_address')->toArray());
// print_r("<br> Primary Banking : "); print_r($user->person->primary_bank->masked_account ?? '');
// print_r("<br> All Banking : "); print_r($user->person->all_banking->pluck('masked_account')->toArray());
// print_r("<br> Roles : "); print_r($user->getRoleNames()->toArray());
// print_r("<br> Posts : "); print_r($user->posts()->pluck('post_code')->toArray());
// print_r("<br> Assigned Permissions : "); print_r($user->getAllPermissions()->pluck('name')->toArray());
// print_r("<br> All Permissions : "); print_r($user->getAllPermissions()->pluck('name')->toArray());
// exit;
        try {
            Log::info('Dashboard accessed', [
                'user_id' => $user->id,
                'username' => $user->username,
                'is_superadmin' => $user->isSuperAdmin(),
            ]);

            $details = $this->getCurrentUserDetails($user);

            if ($user->isSuperAdmin()) {
                return $this->getSuperAdminDashboard($user, $details);
            }

            return $this->getScopedUserDashboard($user, $details);
        } catch (\Exception $e) {
            Log::error('Dashboard error', ['error' => $e->getMessage()]);
            return view('vendor.backpack.ui.dashboard', [
                'error' => 'Failed to load dashboard data.',
            ]);
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
        'is_active'           => $user->is_active ?? false,

        // Primary
        'primary_branch'      => $user->primaryBranchCode() ?? '—',
        'primary_location'    => $user->primaryLocationCode() ?? '—',
        'primary_department'  => $user->primaryDepartmentCode() ?? '—',
        'primary_division'    => $user->primaryDivisionCode() ?? '—',
        'primary_post'        => $user->primaryPost() ?? '—',

        // All assigned
        'all_branches'        => $user->branches()->pluck('name', 'code') ?? collect(),
        'all_locations'       => $user->locations()->pluck('name', 'code') ?? collect(),
        'all_departments'     => $user->departments()->pluck('name', 'code') ?? collect(),
        'all_divisions'       => $user->divisions()->pluck('name', 'code') ?? collect(),

        // Org fields from employee
        'designation'         => $employee?->desig_code ?? '—',
        'vertical'            => $employee?->vertical_code ?? '—',
        'segment'             => $employee?->segment_code ?? '—',
        'sub_segment'         => $employee?->sub_segment_code ?? '—',

        // Contact
        'primary_mobile'      => $user->primary_mobile ?? $person?->primary_mobile ?? '—',
        'primary_email'       => $user->primary_email ?? $person?->primary_email ?? '—',
        'all_mobiles'         => $user->all_mobiles ?? collect(),
        'all_emails'          => $user->all_emails ?? collect(),

        // Address & Banking
        'primary_address'     => $person?->primary_address?->full_address ?? '—',
        'all_addresses'       => $person?->all_addresses?->pluck('full_address') ?? collect(),
        'primary_banking'     => $person?->primary_bank?->masked_account ?? '—',
        'all_banking'         => $person?->all_banking?->pluck('masked_account') ?? collect(),

        // Roles & Posts
        'roles'               => $user->getRoleNames()->toArray() ?? [],
        'posts'               => $user->posts()->pluck('post_code')->toArray() ?? [],
    ];

}

    private function getSuperAdminDashboard(User $user, array $details)
    {
    //dd($details);    
    $cacheKey = 'dashboard.superadmin.stats';
        $stats = Cache::remember($cacheKey, 3600, function () {
            return [
                'total_branches' => Branch::count(),
                'total_locations' => Location::count(),
                'total_departments' => Department::count(),
                'total_employees' => Employee::count(),
                'active_users' => User::where('is_active', true)->count(),
            ];
        });

        return view('vendor.backpack.ui.dashboard', [
            'user'                  => $user,
            'user_access_label'     => '🔑 Full Access (SuperAdmin)',
            'current_user_details'  => $details,
            'total_branches'        => $stats['total_branches'] ?? 0,
            'total_locations'       => $stats['total_locations'] ?? 0,
            'total_departments'     => $stats['total_departments'] ?? 0,
            'total_employees'       => $stats['total_employees'] ?? 0,
            'active_users'          => $stats['active_users'] ?? 0,
        ]);
    }

    private function getScopedUserDashboard(User $user, array $details)
    {
       // dd($details);
        $cacheKey = 'dashboard.scoped.' . $user->id;
        $stats = Cache::remember($cacheKey, 900, fn() => [
            'total_branches' => $user->branches()->count(),
            'total_locations' => $user->locations()->count(),
            'total_departments' => $user->departments()->count(),
            'total_employees' => Employee::count(),
            'active_users' => User::where('is_active', true)->count(),
        ]);

        return view('vendor.backpack.ui.dashboard', [
            'user'                  => $user,
            'user_access_label'     => '👁️ Scoped Access',
            'current_user_details'  => $details,
            'total_branches'        => $stats['total_branches'] ?? 0,
            'total_locations'       => $stats['total_locations'] ?? 0,
            'total_departments'     => $stats['total_departments'] ?? 0,
            'total_employees'       => $stats['total_employees'] ?? 0,
            'active_users'          => $stats['active_users'] ?? 0,
        ]);
    }
}

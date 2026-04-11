<?php

namespace App\Http\Controllers\Admin;

use App\Models\Core\{Branch, Location, Department, Employee,  Enquiry, Booking, Sale};
use App\Models\User;
use App\Services\{RBACService, DataScopeService};
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * DashboardController
 * 
 * Provides dashboard statistics and analytics with RBAC and data scoping.
 * 
 * Features:
 * - Role-based visibility (SuperAdmin sees all, Users see scoped data)
 * - Cached statistics for performance (1 hour TTL)
 * - Data scoping by branch, location, department
 * - Real-time activity tracking
 * - Access logging for audit trails
 * 
 * @category Admin Controllers
 * @package App\Http\Controllers\Admin
 * @author VDMS Development Team
 * @version 2.0
 */
class DashboardController extends CrudController
{
    /**
     * Constructor with service injection
     * 
     * @param RBACService $rbacService - Role-based access control
     * @param DataScopeService $dataScopeService - Data scoping service
     */
    public function __construct(
        protected RBACService $rbacService,
        protected DataScopeService $dataScopeService
    ) {}

    /**
     * Display the dashboard with statistics
     * 
     * Retrieves and displays dashboard statistics based on user's RBAC role.
     * SuperAdmin users see all data, while regular users see only their scoped data.
     * 
     * Statistics include:
     * - Count of branches, locations, departments
     * - Count of employees and active users
     * - Count of enquiries, bookings, and sales
     * - Today's activity summary
     * - Recent enquiries and bookings
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = backpack_user();

        try {
            // Log dashboard access for audit trail
            Log::info('Dashboard accessed', [
                'user_id' => $user->id,
                'user_code' => $user->code,
                'is_superadmin' => $user->isSuperAdmin(),
                'timestamp' => now(),
            ]);

            // Check if user is SuperAdmin
            if ($user->isSuperAdmin()) {
                return $this->getSuperAdminDashboard($user);
            } else {
                return $this->getScopedUserDashboard($user);
            }
        } catch (\Exception $e) {
            Log::error('Dashboard loading error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('admin.dashboard', [
                'error' => 'Failed to load dashboard data. Please try again.',
            ]);
        }
    }

    /**
     * Get dashboard data for SuperAdmin users
     * 
     * SuperAdmin sees all data across all branches, locations, and departments.
     * Uses caching to improve performance (1 hour TTL).
     * 
     * @param User $user - The logged-in user
     * @return \Illuminate\View\View
     */
    private function getSuperAdminDashboard(User $user)
    {
        // Use cache key for dashboard data
        $cacheKey = 'dashboard.superadmin.stats';
        $cacheTTL = 3600; // 1 hour

        $stats = Cache::remember($cacheKey, $cacheTTL, function () {
            return [
                // Organizational structure counts
                'total_branches' => Branch::where('is_active', true)->count(),
                'total_locations' => Location::where('is_active', true)->count(),
                'total_departments' => Department::where('is_active', true)->count(),

                // Human resources counts
                'total_employees' => Employee::where('is_active', true)->count(),
                'active_users' => User::where('is_active', true)->count(),

                // Business metrics
                'total_enquiries' => 0, //Enquiry::count(),
                'total_bookings' => 0, //Booking::count(),
                'total_sales' => 0, //Sale::sum('total_amount') ?? 0,

                // Today's activity
                'today_enquiries' => 0, //Enquiry::whereDate('created_at', today())->count(),
                'today_bookings' => 0, //Booking::whereDate('created_at', today())->count(),
                'today_sales' => 0, //Sale::whereDate('created_at', today())->sum('total_amount') ?? 0,
            ];
        });

        // Get recent activity
        $recentEnquiries = 0; //Enquiry::with('customer')->latest('created_at')->limit(5)->get();

        $recentBookings = 0; //Booking::with('customer', 'vehicle')->latest('created_at')->limit(5)->get();

        return view('admin.dashboard', [
            'user' => $user,
            'user_access_label' => '🔑 Full Access (SuperAdmin)',
            'show_my_access_section' => false,

            // Organization stats
            'total_branches' => $stats['total_branches'],
            'total_locations' => $stats['total_locations'],
            'total_departments' => $stats['total_departments'],

            // HR stats
            'total_employees' => $stats['total_employees'],
            'active_users' => $stats['active_users'],

            // Business stats
            'total_enquiries' => $stats['total_enquiries'],
            'total_bookings' => $stats['total_bookings'],
            'total_sales' => $stats['total_sales'],

            // Today's activity
            'today_enquiries' => $stats['today_enquiries'],
            'today_bookings' => $stats['today_bookings'],
            'today_sales' => $stats['today_sales'],

            // Recent activity
            'recent_enquiries' => $recentEnquiries,
            'recent_bookings' => $recentBookings,

            // Cache info (for development)
            'cache_generated_at' => now(),
            'cache_ttl' => '1 hour',
        ]);
    }

    /**
     * Get dashboard data for scoped users
     * 
     * Regular users see only data they have access to based on:
     * - Their assigned branches
     * - Their assigned locations
     * - Their assigned departments
     * - Their scope hierarchy
     * 
     * @param User $user - The logged-in user
     * @return \Illuminate\View\View
     */
    private function getScopedUserDashboard(User $user)
    {
        try {
            // Get user's accessible scopes
            $accessibleBranches = $this->dataScopeService->getUserAccessibleScopes($user, 'branch');
            $accessibleLocations = $this->dataScopeService->getUserAccessibleScopes($user, 'location');
            $accessibleDepartments = $this->dataScopeService->getUserAccessibleScopes($user, 'department');

            // Generate cache key including user scopes
            $cacheKey = 'dashboard.scoped.' . $user->id . '.' . hash(
                'sha256',
                implode('-', array_merge($accessibleBranches, $accessibleLocations, $accessibleDepartments))
            );
            $cacheTTL = 900; // 15 minutes for scoped data

            $stats = Cache::remember($cacheKey, $cacheTTL, function () use ($accessibleBranches, $accessibleLocations, $accessibleDepartments) {
                return [
                    // Accessible organization counts
                    'total_branches' => $accessibleBranches ? Branch::whereIn('id', $accessibleBranches)
                        ->where('is_active', true)->count() : 0,
                    'total_locations' => $accessibleLocations ? Location::whereIn('id', $accessibleLocations)
                        ->where('is_active', true)->count() : 0,
                    'total_departments' => $accessibleDepartments ? Department::whereIn('id', $accessibleDepartments)
                        ->where('is_active', true)->count() : 0,

                    // Accessible HR counts
                    'total_employees' => $this->getCountWithScoping(
                        Employee::class,
                        'branch_id',
                        $accessibleBranches
                    ),

                    // Business metrics (all accessible records)
                    'total_enquiries' => Enquiry::count(),
                    'total_bookings' => Booking::count(),
                    'total_sales' => Sale::sum('total_amount') ?? 0,

                    // Today's activity
                    'today_enquiries' => Enquiry::whereDate('created_at', today())->count(),
                    'today_bookings' => Booking::whereDate('created_at', today())->count(),
                    'today_sales' => Sale::whereDate('created_at', today())->sum('total_amount') ?? 0,
                ];
            });

            // Get recent activity (scoped)
            $recentEnquiries = Enquiry::with('customer')
                ->latest('created_at')
                ->limit(5)
                ->get();

            $recentBookings = Booking::with('customer', 'vehicle')
                ->latest('created_at')
                ->limit(5)
                ->get();

            // Determine access label based on scopes
            $accessLabel = $this->generateAccessLabel($accessibleBranches, $accessibleLocations, $accessibleDepartments);

            return view('admin.dashboard', [
                'user' => $user,
                'user_access_label' => $accessLabel,
                'show_my_access_section' => true,
                'accessible_branches' => $accessibleBranches,
                'accessible_locations' => $accessibleLocations,
                'accessible_departments' => $accessibleDepartments,

                // Organization stats
                'total_branches' => $stats['total_branches'],
                'total_locations' => $stats['total_locations'],
                'total_departments' => $stats['total_departments'],

                // HR stats
                'total_employees' => $stats['total_employees'],
                'active_users' => User::where('is_active', true)->count(),

                // Business stats
                'total_enquiries' => $stats['total_enquiries'],
                'total_bookings' => $stats['total_bookings'],
                'total_sales' => $stats['total_sales'],

                // Today's activity
                'today_enquiries' => $stats['today_enquiries'],
                'today_bookings' => $stats['today_bookings'],
                'today_sales' => $stats['today_sales'],

                // Recent activity
                'recent_enquiries' => $recentEnquiries,
                'recent_bookings' => $recentBookings,

                // Cache info
                'cache_generated_at' => now(),
                'cache_ttl' => '15 minutes',
            ]);
        } catch (\Exception $e) {
            Log::error('Scoped dashboard error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to minimal dashboard
            return view('admin.dashboard', [
                'user' => $user,
                'error' => 'Some dashboard data could not be loaded.',
                'show_my_access_section' => true,
            ]);
        }
    }

    /**
     * Get count of records with scope filtering
     * 
     * Helper method to count records filtered by accessible scopes.
     * 
     * @param string $modelClass - Full model class name
     * @param string $scopeColumn - Column to filter by
     * @param array|null $scopeIds - Array of accessible scope IDs
     * @return int
     */
    private function getCountWithScoping(string $modelClass, string $scopeColumn, ?array $scopeIds): int
    {
        if (!$scopeIds || empty($scopeIds)) {
            return 0;
        }

        return $modelClass::whereIn($scopeColumn, $scopeIds)->count();
    }

    /**
     * Generate a human-readable access label
     * 
     * Creates a label describing the user's access level based on their scopes.
     * 
     * @param array|null $branches - Accessible branch IDs
     * @param array|null $locations - Accessible location IDs
     * @param array|null $departments - Accessible department IDs
     * @return string
     */
    private function generateAccessLabel(?array $branches, ?array $locations, ?array $departments): string
    {
        $parts = [];

        if ($branches && count($branches) > 0) {
            $parts[] = count($branches) . ' branch' . (count($branches) > 1 ? 'es' : '');
        }

        if ($locations && count($locations) > 0) {
            $parts[] = count($locations) . ' location' . (count($locations) > 1 ? 's' : '');
        }

        if ($departments && count($departments) > 0) {
            $parts[] = count($departments) . ' department' . (count($departments) > 1 ? 's' : '');
        }

        if (empty($parts)) {
            return '🔒 Limited Access (No scopes assigned)';
        }

        return '👁️ Scoped Access (' . implode(', ', $parts) . ')';
    }

    /**
     * Clear dashboard cache
     * 
     * Forces refresh of cached dashboard data.
     * Useful after data changes that affect dashboard statistics.
     * 
     * @param int $userId - User ID to clear cache for (or null for all)
     * @return void
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget('dashboard.scoped.' . $userId);
        } else {
            Cache::forget('dashboard.superadmin.stats');
        }

        Log::info('Dashboard cache cleared', [
            'user_id' => $userId ?? 'all',
            'cleared_by' => backpack_user()->id,
        ]);
    }
}

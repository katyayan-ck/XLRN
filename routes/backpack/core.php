<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.


Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    // Route::get('performance-report', [PerformanceController::class, 'report']);
    Route::get('home', [DashboardController::class, 'index'])
        ->name('backpack.dashboard.home');
    Route::crud('approval-hierarchy', 'ApprovalHierarchyCrudController');
    Route::crud('system-settings', 'SystemSettingCrudController');
    Route::crud('branch', 'BranchCrudController');
    Route::crud('brand', 'BrandCrudController');
    Route::post('brand/import', 'BrandCrudController@import')->name('brand.import');
    Route::crud('color', 'ColorCrudController');
    Route::crud('dashboard-controller', 'DashboardControllerCrudController');
    Route::crud('department', 'DepartmentCrudController');
    Route::crud('designation', 'DesignationCrudController');
    Route::crud('division', 'DivisionCrudController');
    Route::crud('employee-branch-assignment', 'EmployeeBranchAssignmentCrudController');
    Route::crud('employee', 'EmployeeCrudController');
    Route::crud('employee-department-assignment', 'EmployeeDepartmentAssignmentCrudController');
    Route::crud('employee-location-assignment', 'EmployeeLocationAssignmentCrudController');
    Route::crud('employee-post-assignment', 'EmployeePostAssignmentCrudController');
    Route::crud('employee-vertical-assignment', 'EmployeeVerticalAssignmentCrudController');
    Route::crud('garage', 'GarageCrudController');
    Route::crud('graph-edge', 'GraphEdgeCrudController');
    Route::crud('graph-node', 'GraphNodeCrudController');
    Route::crud('reporting-hierarchy', 'ReportingHierarchyCrudController');
    Route::crud('keyvalue', 'KeyvalueCrudController');
    Route::crud('keyword-master', 'KeywordMasterCrudController');
    Route::crud('location', 'LocationCrudController');
    Route::crud('modules', 'ModulesCrudController');
    Route::crud('permission', 'PermissionCrudController');
    Route::crud('role', 'RoleCrudController');
    Route::crud('person-address', 'PersonAddressCrudController');
    Route::crud('person-banking-detail', 'PersonBankingDetailCrudController');
    Route::crud('person-contact', 'PersonContactCrudController');
    Route::crud('person', 'PersonCrudController');
    Route::crud('post', 'PostCrudController');
    Route::crud('post-permission', 'PostPermissionCrudController');
    Route::crud('process', 'ProcessCrudController');
    Route::crud('reporting-hierarchy', 'ReportingHierarchyCrudController');
    Route::crud('segment', 'SegmentCrudController');
    Route::crud('sub-segment', 'SubSegmentCrudController');
    Route::crud('user-type', 'UserTypeCrudController');
    Route::crud('variant', 'VariantCrudController');
    Route::crud('vehicle-model', 'VehicleModelCrudController');
    Route::crud('vertical', 'VerticalCrudController');
    Route::crud('user', 'UserCrudController');



    Route::crud('spare-request', 'SpareRequestCrudController');

    // Route::get('get-variants/{model}', 'SpareImportController@getVariants')
    //     ->name('get.variants');

    Route::get('check-ro-number/{rn}', 'SpareImportController@checkRoNumber')
        ->name('check-ro-number');

    Route::get('fetch-parts', [App\Http\Controllers\Admin\SpareRequestCrudController::class, 'fetchParts'])
        ->name('fetch.parts');

    Route::get('spare/consumption', 'SpareImportController@spareConsumptionReport')
        ->name('spare.consumption');

    Route::get('spare/partwise-requirement', [App\Http\Controllers\Admin\SparePartwiseController::class, 'index'])
        ->name('spare.partwise');

    Route::get('spare/partwise-requirement/data', [App\Http\Controllers\Admin\SparePartwiseController::class, 'data'])
        ->name('spare.partwise.data');

    Route::get('spare/ro-closure', 'SpareTechnicianController@closure')
        ->name('spare.ro-closure');

    Route::get('spare/orderingreport', [App\Http\Controllers\Admin\SpareOrderingreportController::class, 'index'])
        ->name('spare.orderingreport');

    Route::get('spare/orderingreport/data', [App\Http\Controllers\Admin\SpareOrderingreportController::class, 'data'])
        ->name('spare.orderingreport.data');

    // Spare Request List Data (AG Grid)
    Route::get('spare-request/data', [App\Http\Controllers\Admin\SpareRequestCrudController::class, 'data'])
        ->name('spare-request.data');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */

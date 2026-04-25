<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\RBACService;
use App\Services\DataScopeService;
use App\Services\AuthService;
use App\Services\ApprovalService;
use App\Services\FirebaseService;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // // Register services as singletons for performance
        $this->app->singleton(RBACService::class, function ($app) {
            return new RBACService();
        });

        $this->app->singleton(DataScopeService::class, function ($app) {
            return new DataScopeService();
        });

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });

        $this->app->singleton(ApprovalService::class, function ($app) {
            return new ApprovalService();
        });

        // Firebase Services
        $this->app->singleton(FirebaseService::class, function ($app) {
            return new FirebaseService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(FirebaseService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $files = glob(base_path('routes/backpack/*.php'));

        foreach ($files as $file) {
            Route::middleware(
                array_merge(
                    (array) config('backpack.base.web_middleware', 'web'),
                    (array) config('backpack.base.middleware_key', 'admin')
                )
            )->prefix(config('backpack.base.route_prefix', 'admin'))
                ->namespace('App\Http\Controllers\Admin')
                ->group($file);
        }
    }
}

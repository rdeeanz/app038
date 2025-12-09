<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module service bindings
        $this->registerModuleServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerModuleRoutes();
    }

    /**
     * Register module service bindings
     */
    protected function registerModuleServices(): void
    {
        // ERP Integration
        $this->app->bind(
            \App\Modules\ERPIntegration\Repositories\ERPIntegrationRepositoryInterface::class,
            \App\Modules\ERPIntegration\Repositories\ERPIntegrationRepository::class
        );

        // Sales
        $this->app->bind(
            \App\Modules\Sales\Repositories\SalesRepositoryInterface::class,
            \App\Modules\Sales\Repositories\SalesRepository::class
        );

        // Inventory
        $this->app->bind(
            \App\Modules\Inventory\Repositories\InventoryRepositoryInterface::class,
            \App\Modules\Inventory\Repositories\InventoryRepository::class
        );

        // Auth
        $this->app->bind(
            \App\Modules\Auth\Repositories\AuthRepositoryInterface::class,
            \App\Modules\Auth\Repositories\AuthRepository::class
        );

        // Monitoring
        $this->app->bind(
            \App\Modules\Monitoring\Repositories\MonitoringRepositoryInterface::class,
            \App\Modules\Monitoring\Repositories\MonitoringRepository::class
        );

        // SAP Connector - bind interface to factory
        $this->app->bind(
            \App\Modules\ERPIntegration\Connectors\SapConnectorInterface::class,
            function ($app) {
                $type = config('sap.default', 'odata');
                return \App\Modules\ERPIntegration\Services\SapConnectorFactory::create($type);
            }
        );
    }

    /**
     * Register module routes
     */
    protected function registerModuleRoutes(): void
    {
        // ERP Integration routes
        if (file_exists(base_path('app/Modules/ERPIntegration/routes/api.php'))) {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/ERPIntegration/routes/api.php'));
        }

        // Sales routes
        if (file_exists(base_path('app/Modules/Sales/routes/api.php'))) {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Sales/routes/api.php'));
        }

        // Inventory routes
        if (file_exists(base_path('app/Modules/Inventory/routes/api.php'))) {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Inventory/routes/api.php'));
        }

        // Auth routes
        if (file_exists(base_path('app/Modules/Auth/routes/api.php'))) {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Auth/routes/api.php'));
        }

        // Monitoring routes
        if (file_exists(base_path('app/Modules/Monitoring/routes/api.php'))) {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Monitoring/routes/api.php'));
        }
    }
}


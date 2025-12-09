<?php

namespace App\Providers;

use App\Services\VaultService;
use Illuminate\Support\ServiceProvider;

class VaultServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(VaultService::class, function ($app) {
            return new VaultService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load Vault configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/vault.php',
            'vault'
        );

        // Override database credentials from Vault if enabled
        if (config('vault.database.enabled', false)) {
            $this->loadDatabaseCredentialsFromVault();
        }
    }

    /**
     * Load database credentials from Vault
     */
    protected function loadDatabaseCredentialsFromVault(): void
    {
        try {
            $vault = app(VaultService::class);
            $credentials = $vault->getDatabaseCredentials();

            config([
                'database.connections.pgsql.username' => $credentials['username'] ?? config('database.connections.pgsql.username'),
                'database.connections.pgsql.password' => $credentials['password'] ?? config('database.connections.pgsql.password'),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to load database credentials from Vault: ' . $e->getMessage());
        }
    }
}


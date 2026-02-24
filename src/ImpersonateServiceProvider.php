<?php

namespace Backtik\Impersonate;

use Backtik\Impersonate\Http\Middleware\ShareImpersonationState;
use Illuminate\Support\ServiceProvider;

class ImpersonateServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/impersonate.php', 'impersonate');

        $this->app->singleton(ImpersonateManager::class, function () {
            return new ImpersonateManager;
        });

        $this->app->alias(ImpersonateManager::class, 'impersonate');
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->publishViews();
        $this->loadRoutes();
        $this->registerMiddlewareAlias();
    }

    /**
     * Publish the config file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/impersonate.php' => config_path('impersonate.php'),
        ], 'impersonate-config');
    }

    /**
     * Publish the migration files.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'impersonate-migrations');
    }

    /**
     * Load and publish the package views.
     */
    protected function publishViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/impersonate'),
        ], 'impersonate-views');
    }

    /**
     * Load the package routes.
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Register middleware alias for the Inertia sharing middleware.
     */
    protected function registerMiddlewareAlias(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('impersonate.share', ShareImpersonationState::class);
    }
}

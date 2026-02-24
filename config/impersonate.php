<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auth Guard
    |--------------------------------------------------------------------------
    |
    | The authentication guard to use for impersonation. Set to null to use
    | the application's default guard.
    |
    */

    'guard' => null,

    /*
    |--------------------------------------------------------------------------
    | Who Can Impersonate
    |--------------------------------------------------------------------------
    |
    | A closure that receives a User model and returns true if that user is
    | allowed to impersonate others. When set to null, the package checks
    | for an isAdmin() method or an is_admin attribute on the user.
    |
    */

    'can_impersonate' => null,

    /*
    |--------------------------------------------------------------------------
    | Who Can Be Impersonated
    |--------------------------------------------------------------------------
    |
    | A closure that receives a User model and returns true if that user can
    | be impersonated. When set to null, any user can be impersonated (subject
    | to the protect_superadmins setting below).
    |
    */

    'can_be_impersonated' => null,

    /*
    |--------------------------------------------------------------------------
    | Protect Superadmins
    |--------------------------------------------------------------------------
    |
    | When true, users with is_superadmin = true cannot be impersonated.
    | This is a hard safety net on top of can_be_impersonated.
    |
    */

    'protect_superadmins' => true,

    /*
    |--------------------------------------------------------------------------
    | Redirect After Start
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to after impersonation starts.
    |
    */

    'redirect_after_start' => '/',

    /*
    |--------------------------------------------------------------------------
    | Redirect After Leave
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to after leaving impersonation.
    | Use 'back' to redirect to the previous page.
    |
    */

    'redirect_after_leave' => 'back',

    /*
    |--------------------------------------------------------------------------
    | Share Inertia Props
    |--------------------------------------------------------------------------
    |
    | When true and Inertia is installed, the package will share impersonation
    | state as page props via the ShareImpersonationState middleware.
    | You must register the middleware in your app's middleware stack.
    |
    */

    'share_inertia' => false,

    /*
    |--------------------------------------------------------------------------
    | Log Impersonation
    |--------------------------------------------------------------------------
    |
    | When true, impersonation start/stop events are logged to the
    | impersonation_logs database table. You must publish and run
    | the migration: php artisan vendor:publish --tag=impersonate-migrations
    |
    */

    'log_impersonation' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix for the impersonation routes.
    |
    */

    'route_prefix' => 'impersonate',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware applied to the impersonation routes.
    |
    */

    'route_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Route Without Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to exclude from the impersonation routes. Useful when your
    | web middleware group includes app-specific middleware (e.g., permission
    | checks) that would block the impersonation routes.
    |
    */

    'route_without_middleware' => [],

];

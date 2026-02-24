# Laravel Impersonate

Session-based user impersonation for Laravel 12+. Allows administrators to sign in as any user with a full `Auth::login()` session switch, audit logging, and optional Inertia.js integration.

## Requirements

- PHP ^8.2
- Laravel ^12.0

## Installation

### Via Composer (private repository)

```bash
composer require backtik/laravel-impersonate
```

### Via path repository (local development)

Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/backtik/laravel-impersonate"
        }
    ]
}
```

Then require the package:

```bash
composer require backtik/laravel-impersonate:@dev
```

The service provider is auto-discovered — no manual registration needed.

## Setup

### 1. Publish the config

```bash
php artisan vendor:publish --tag=impersonate-config
```

### 2. Publish and run the migration

```bash
php artisan vendor:publish --tag=impersonate-migrations
php artisan migrate
```

This creates an `impersonation_logs` table for audit logging.

## Configuration

All options are in `config/impersonate.php`:

| Option                     | Type       | Default           | Description                                                                                                                                          |
| -------------------------- | ---------- | ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| `guard`                    | `?string`  | `null`            | Auth guard to use. `null` uses the app default.                                                                                                      |
| `can_impersonate`          | `?Closure` | `null`            | Closure receiving a user, returns `true` if they may impersonate. When `null`, checks for `isAdmin()` method or `is_admin` attribute.                |
| `can_be_impersonated`      | `?Closure` | `null`            | Closure receiving a user, returns `true` if they can be impersonated. When `null`, all users can be impersonated (subject to `protect_superadmins`). |
| `protect_superadmins`      | `bool`     | `true`            | Prevents users with `is_superadmin = true` from being impersonated.                                                                                  |
| `redirect_after_start`     | `string`   | `'/'`             | URL to redirect to after impersonation starts.                                                                                                       |
| `redirect_after_leave`     | `string`   | `'back'`          | URL to redirect to after leaving. `'back'` returns to the previous page.                                                                             |
| `share_inertia`            | `bool`     | `false`           | Share impersonation state as Inertia page props via middleware.                                                                                      |
| `log_impersonation`        | `bool`     | `true`            | Log impersonation start/stop events to the database.                                                                                                 |
| `route_prefix`             | `string`   | `'impersonate'`   | URL prefix for the package routes.                                                                                                                   |
| `route_middleware`         | `array`    | `['web', 'auth']` | Middleware applied to the package routes.                                                                                                            |
| `route_without_middleware` | `array`    | `[]`              | Middleware to exclude from package routes (useful when your web group includes app-specific middleware that would interfere).                        |

### Authorization examples

```php
// config/impersonate.php

// Only superadmins can impersonate
'can_impersonate' => fn ($user) => $user->is_superadmin,

// Admins and superadmins can impersonate
'can_impersonate' => fn ($user) => $user->isAdmin(),

// Only non-admin users can be impersonated
'can_be_impersonated' => fn ($user) => ! $user->isAdmin(),
```

## Routes

The package registers two POST routes:

| Method | URI               | Name                | Description                                  |
| ------ | ----------------- | ------------------- | -------------------------------------------- |
| POST   | `/{prefix}/{id}`  | `impersonate.start` | Start impersonating user with the given ID   |
| POST   | `/{prefix}/leave` | `impersonate.leave` | Stop impersonating and restore admin session |

## Usage

### Starting impersonation

Send a POST request to the `impersonate.start` route:

```php
// In a controller
return redirect()->route('impersonate.start', ['id' => $user->id]);
```

```html
<!-- In a Blade template -->
<form method="POST" action="{{ route('impersonate.start', $user->id) }}">
    @csrf
    <button type="submit">Impersonate</button>
</form>
```

```vue
<!-- In an Inertia/Vue component -->
<script setup>
import { router } from "@inertiajs/vue3";

function impersonate(userId) {
    router.post(route("impersonate.start", { id: userId }));
}
</script>
```

### Leaving impersonation

```vue
<script setup>
import { router } from "@inertiajs/vue3";

function leaveImpersonation() {
    router.post(route("impersonate.leave"));
}
</script>
```

### Using the manager directly

The `ImpersonateManager` singleton is available via the container:

```php
use Backtik\Impersonate\ImpersonateManager;

$manager = app(ImpersonateManager::class);

// Check if currently impersonating
$manager->isImpersonating(); // bool

// Get the original admin user
$manager->getOriginalUser(); // ?Authenticatable

// Check authorization
$manager->canImpersonate($user); // bool
$manager->canBeImpersonated($user); // bool

// Manual start/stop (authorization checks are your responsibility)
$manager->start($targetUser);
$manager->leave();
```

## Inertia.js Integration

### 1. Enable Inertia sharing

In `config/impersonate.php`:

```php
'share_inertia' => true,
```

### 2. Register the middleware

Add `ShareImpersonationState` to your web middleware stack in `bootstrap/app.php`, **after** `HandleInertiaRequests`:

```php
use Backtik\Impersonate\Http\Middleware\ShareImpersonationState;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        ShareImpersonationState::class,
        // ... other middleware
    ]);
})
```

Or use the registered alias:

```php
$middleware->web(append: ['impersonate.share']);
```

### 3. Access impersonation state in Vue

The middleware shares the following props on every page:

```typescript
interface ImpersonationProps {
    impersonating: {
        active: boolean;
        originalUser: {
            id: number;
            name: string | null;
            email: string | null;
        } | null;
    };
}
```

Example banner component:

```vue
<script setup>
import { usePage, router } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const impersonating = computed(() => page.props.impersonating);
</script>

<template>
    <div v-if="impersonating?.active" class="bg-indigo-600 text-white text-center py-2 px-4">
        <span>
            Impersonating <strong>{{ impersonating.originalUser?.name }}</strong>
        </span>
        <button @click="router.post(route('impersonate.leave'))" class="ml-4 underline">Leave impersonation</button>
    </div>
</template>
```

## Optional User Model Trait

Add the `CanBeImpersonated` trait to your User model for convenience methods:

```php
use Backtik\Impersonate\Traits\CanBeImpersonated;

class User extends Authenticatable
{
    use CanBeImpersonated;
}
```

This provides:

```php
$user->canImpersonate();       // Can this user impersonate others?
$user->canBeImpersonated();    // Can this user be impersonated?
$user->isBeingImpersonated();  // Is the current session impersonating someone?
```

## Events

Two events are dispatched that you can listen to:

| Event                                             | Properties                | When                       |
| ------------------------------------------------- | ------------------------- | -------------------------- |
| `Backtik\Impersonate\Events\ImpersonationStarted` | `$admin`, `$impersonated` | After impersonation begins |
| `Backtik\Impersonate\Events\ImpersonationStopped` | `$admin`, `$impersonated` | After impersonation ends   |

Example listener:

```php
use Backtik\Impersonate\Events\ImpersonationStarted;

class NotifyAdminOfImpersonation
{
    public function handle(ImpersonationStarted $event): void
    {
        logger()->info('Impersonation started', [
            'admin' => $event->admin->getAuthIdentifier(),
            'target' => $event->impersonated->getAuthIdentifier(),
        ]);
    }
}
```

## Audit Logging

When `log_impersonation` is enabled (default), every impersonation session is logged to the `impersonation_logs` table:

| Column                 | Type                 | Description                 |
| ---------------------- | -------------------- | --------------------------- |
| `admin_id`             | FK → users           | The admin who impersonated  |
| `impersonated_user_id` | FK → users           | The user being impersonated |
| `started_at`           | timestamp            | When impersonation started  |
| `stopped_at`           | timestamp (nullable) | When impersonation ended    |
| `ip_address`           | string(45)           | Admin's IP address          |
| `user_agent`           | text                 | Admin's user agent string   |

Query logs using the `ImpersonationLog` model:

```php
use Backtik\Impersonate\Models\ImpersonationLog;

// All impersonation logs
ImpersonationLog::with(['admin', 'impersonatedUser'])->latest()->get();

// Logs for a specific admin
ImpersonationLog::where('admin_id', $adminId)->get();

// Active impersonations (no stopped_at)
ImpersonationLog::whereNull('stopped_at')->get();
```

## Customization

### Role-based authorization

Restrict impersonation to specific roles using closures in the config:

```php
// config/impersonate.php

// Only users with a specific role can impersonate
'can_impersonate' => fn ($user) => $user->role?->name === 'admin',

// Prevent impersonating other admins
'can_be_impersonated' => fn ($user) => ! in_array($user->role?->name, ['admin', 'superadmin']),
```

### Custom guard

If your app uses a non-default guard (e.g., for a separate admin panel):

```php
'guard' => 'admin',
```

### Custom redirects

Redirect to a specific dashboard after impersonation starts or ends:

```php
'redirect_after_start' => '/dashboard',
'redirect_after_leave' => '/admin/users',
```

Use `'back'` to return to the previous page after leaving:

```php
'redirect_after_leave' => 'back',
```

### Event listeners for notifications

Send a Slack notification or email whenever impersonation occurs:

```php
// app/Listeners/LogImpersonationToSlack.php

namespace App\Listeners;

use Backtik\Impersonate\Events\ImpersonationStarted;
use Illuminate\Support\Facades\Log;

class LogImpersonationToSlack
{
    public function handle(ImpersonationStarted $event): void
    {
        Log::channel('slack')->warning('Impersonation started', [
            'admin_id' => $event->admin->getAuthIdentifier(),
            'admin_email' => $event->admin->email,
            'impersonated_id' => $event->impersonated->getAuthIdentifier(),
            'impersonated_email' => $event->impersonated->email,
        ]);
    }
}
```

Register the listener in `AppServiceProvider` or via event discovery:

```php
use Backtik\Impersonate\Events\ImpersonationStarted;
use Backtik\Impersonate\Events\ImpersonationStopped;
use App\Listeners\LogImpersonationToSlack;

// In EventServiceProvider or AppServiceProvider::boot()
Event::listen(ImpersonationStarted::class, LogImpersonationToSlack::class);
Event::listen(ImpersonationStopped::class, function ($event) {
    // inline handler
});
```

### Excluding app middleware from package routes

If your web middleware group includes app-specific middleware that blocks the impersonation routes (e.g., permission checks, release form acceptance), exclude them:

```php
'route_without_middleware' => [
    \App\Http\Middleware\CheckUserPermissions::class,
    \App\Http\Middleware\CheckReleaseFormAcceptance::class,
],
```

### Disabling audit logging

If you don't need database logging (e.g., in a development environment):

```php
'log_impersonation' => false,
```

You can still listen to events for custom logging without the database table.

### Conditional UI with the trait

Use the `CanBeImpersonated` trait to conditionally show UI elements:

```php
// In a controller or Inertia shared data
'canImpersonate' => auth()->user()?->canImpersonate() ?? false,
```

```vue
<!-- In a Vue component -->
<button v-if="$page.props.canImpersonate" @click="impersonate(user.id)">
    Impersonate
</button>
```

### Programmatic impersonation

Use the manager directly in custom controllers or artisan commands:

```php
use Backtik\Impersonate\ImpersonateManager;

// In a custom controller with additional business logic
public function impersonateWithAudit(Request $request, int $id): RedirectResponse
{
    $manager = app(ImpersonateManager::class);
    $target = User::findOrFail($id);

    // Custom pre-checks
    if ($target->hasActiveSessions()) {
        return back()->with('error', 'User has active sessions, proceed with caution.');
    }

    // Custom logging / notifications before starting
    activity()->log("Admin started impersonating user #{$id}");

    $manager->start($target);

    return redirect('/dashboard');
}
```

### Time-limited impersonation

Use a scheduled command or middleware to auto-expire impersonation sessions:

```php
// app/Console/Commands/ExpireImpersonations.php

use Backtik\Impersonate\Models\ImpersonationLog;

// Find sessions that have been active for more than 1 hour
$stale = ImpersonationLog::whereNull('stopped_at')
    ->where('started_at', '<', now()->subHour())
    ->get();

foreach ($stale as $log) {
    $log->update(['stopped_at' => now()]);
    // Optionally invalidate the session or notify the admin
}
```

## Testing

The package includes a standalone test suite using [Pest](https://pestphp.com/) and [Orchestra Testbench](https://github.com/orchestral/testbench). No host application is required.

```bash
# From the package directory
cd packages/backtik/laravel-impersonate

# Install dependencies
composer install

# Run all tests
composer test
```

Tests use SQLite in-memory so there's nothing to configure. The suite covers:

- **Start/Leave routes** — redirects, session state, flash messages
- **Authorization** — admin checks, superadmin protection, custom closures, nesting prevention
- **Audit logging** — log creation, log update on leave, IP/UA storage, disabled logging
- **Events** — `ImpersonationStarted` / `ImpersonationStopped` dispatch and payloads
- **Manager** — state detection, authorization methods, custom config closures
- **Trait** — `canImpersonate()`, `canBeImpersonated()`, `isBeingImpersonated()` delegation

A GitHub Actions workflow runs the tests automatically on every pull request and push to `main`.

## Security

- **Nesting prevention**: Impersonation cannot be nested — an admin must leave their current impersonation before starting a new one.
- **Superadmin protection**: By default, users with `is_superadmin = true` cannot be impersonated regardless of other settings.
- **Full session switch**: Uses `Auth::login()` for a complete session switch, ensuring all authorization checks apply to the impersonated user.
- **Audit trail**: All impersonation sessions are logged with IP address and user agent.

## License

MIT

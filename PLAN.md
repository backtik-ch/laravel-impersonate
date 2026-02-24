# backtik/laravel-impersonate — Implementation Plan

Session-based user impersonation for Laravel 12+. Allows admins to "become" any user by fully switching the authenticated session, then easily revert back.

## Architecture

- **`Auth::login($target)`** to fully switch the session to the impersonated user
- **`session('impersonate.original_id')`** stores the real admin's user ID for restoration
- **Configurable authorization** via closures in config (`can_impersonate`, `can_be_impersonated`)
- **Event-driven** — fires `ImpersonationStarted` / `ImpersonationStopped` events
- **Optional audit logging** to an `impersonation_logs` database table
- **Optional Inertia integration** — shares impersonation state as page props via middleware

## Package Structure

```
packages/backtik/laravel-impersonate/
├── composer.json
├── PLAN.md                          ← this file
├── README.md
├── config/
│   └── impersonate.php              ← publishable config
├── database/
│   └── migrations/
│       └── create_impersonation_logs_table.php
├── routes/
│   └── web.php                      ← impersonate.start, impersonate.leave
├── src/
│   ├── ImpersonateServiceProvider.php
│   ├── ImpersonateManager.php       ← core logic (start/leave/check)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ImpersonateController.php
│   │   └── Middleware/
│   │       └── ShareImpersonationState.php
│   ├── Models/
│   │   └── ImpersonationLog.php
│   ├── Events/
│   │   ├── ImpersonationStarted.php
│   │   └── ImpersonationStopped.php
│   └── Traits/
│       └── CanBeImpersonated.php    ← optional trait for User model
└── tests/
    └── (later — Orchestra Testbench + Pest)
```

## Phases

### Phase 1 — Package Scaffolding ✅

- [x] Create directory structure
- [x] Write `composer.json` with auto-discovery
- [x] Init git, push to GitHub
- [x] Add path repository in Mease `composer.json`

### Phase 2 — Core Package Code ✅

- [x] `config/impersonate.php` — all configurable options
- [x] `ImpersonateManager` — singleton service with `start()`, `leave()`, `isImpersonating()`, `getOriginalUser()`
- [x] `ImpersonateServiceProvider` — register manager, merge config, publish config + migrations, load routes
- [x] `ImpersonateController` — `start(int $id)` and `leave()` endpoints with authorization checks
- [x] `routes/web.php` — package routes with configurable prefix, middleware, and middleware exclusions
- [x] `ImpersonationLog` model + migration (opt-in via config)
- [x] `ImpersonationStarted` / `ImpersonationStopped` events
- [x] `CanBeImpersonated` trait (optional convenience)
- [x] `ShareImpersonationState` middleware for Inertia integration

### Phase 3 — Mease Integration ✅

- [x] `composer require` the package (path repository)
- [x] Publish config: `php artisan vendor:publish --tag=impersonate-config`
- [x] Publish + run migration: `php artisan vendor:publish --tag=impersonate-migrations && php artisan migrate`
- [x] Register `ShareImpersonationState` middleware in `bootstrap/app.php`
- [x] Configure `route_without_middleware` to exclude app-specific middleware
- [x] Create `ImpersonationBanner.vue` component
- [x] Add banner to `AuthenticatedLayout.vue` (above WarningBar)
- [x] Add "Impersonate" button to `Admin/Users.vue` page
- [x] Add translations in `resources/js/i18n/locales/en.json` and `fr.json`
- [x] Add `isImpersonating` computed to `userStore.ts`

### Phase 4 — Testing ✅

- [x] App-level feature tests in Mease (`tests/Feature/ImpersonationTest.php`) — 11 tests, 31 assertions
- [x] Run `vendor/bin/pint --dirty`
- [ ] Package-level tests with Orchestra Testbench (later, when the package is extracted)

## Config Options (`config/impersonate.php`)

| Key                        | Type       | Default           | Description                                                   |
| -------------------------- | ---------- | ----------------- | ------------------------------------------------------------- |
| `guard`                    | `?string`  | `null`            | Auth guard to use (null = default)                            |
| `can_impersonate`          | `?Closure` | `null`            | Who can impersonate (null = checks `isAdmin()` or `is_admin`) |
| `can_be_impersonated`      | `?Closure` | `null`            | Who can be impersonated (null = anyone except superadmins)    |
| `protect_superadmins`      | `bool`     | `true`            | Prevent impersonating superadmin users                        |
| `redirect_after_start`     | `string`   | `/`               | Where to redirect after starting impersonation                |
| `redirect_after_leave`     | `string`   | `back`            | Where to redirect after leaving (`back`, URL, or route name)  |
| `share_inertia`            | `bool`     | `false`           | Share impersonation state via `Inertia::share()`              |
| `log_impersonation`        | `bool`     | `true`            | Log impersonation events to database                          |
| `route_prefix`             | `string`   | `impersonate`     | URL prefix for package routes                                 |
| `route_middleware`         | `array`    | `['web', 'auth']` | Middleware applied to package routes                          |
| `route_without_middleware` | `array`    | `[]`              | Middleware to exclude from package routes                     |

## Security Rules

1. Only users passing `can_impersonate` check can start impersonation
2. Superadmins cannot be impersonated (when `protect_superadmins` is true)
3. Nested impersonation is forbidden (cannot impersonate while already impersonating)
4. The "leave" route only requires `auth` — no admin permission needed (since the session is the impersonated user)
5. Session keys are namespaced under `impersonate.*` to avoid collisions
6. Impersonation is lost on session expiry (natural safeguard)

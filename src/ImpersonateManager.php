<?php

namespace Backtik\Impersonate;

use Backtik\Impersonate\Events\ImpersonationStarted;
use Backtik\Impersonate\Events\ImpersonationStopped;
use Backtik\Impersonate\Models\ImpersonationLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ImpersonateManager
{
    /**
     * Start impersonating the given user.
     *
     * Authorization checks should be performed before calling this method.
     */
    public function start(Authenticatable $target): void
    {
        $admin = $this->resolveGuard()->user();

        // Store the original admin's ID in the session
        session()->put('impersonate.original_id', $admin->getAuthIdentifier());
        session()->put('impersonate.guard', $this->getGuardName());

        // Log the impersonation if enabled
        if (config('impersonate.log_impersonation', true)) {
            $log = ImpersonationLog::create([
                'admin_id' => $admin->getAuthIdentifier(),
                'impersonated_user_id' => $target->getAuthIdentifier(),
                'started_at' => now(),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
            session()->put('impersonate.log_id', $log->id);
        }

        // Fully switch the authenticated session to the target user
        $this->resolveGuard()->login($target);

        event(new ImpersonationStarted($admin, $target));
    }

    /**
     * Leave impersonation and restore the original admin session.
     *
     * Caller should verify isImpersonating() before calling this method.
     */
    public function leave(): void
    {
        $originalId = session('impersonate.original_id');
        $guardName = session('impersonate.guard', $this->getGuardName());
        $impersonatedUser = Auth::guard($guardName)->user();

        // Resolve the original admin user
        $provider = Auth::guard($guardName)->getProvider();
        $originalUser = $provider->retrieveById($originalId);

        // Update the log entry if logging is enabled
        if (config('impersonate.log_impersonation', true) && session()->has('impersonate.log_id')) {
            ImpersonationLog::where('id', session('impersonate.log_id'))
                ->update(['stopped_at' => now()]);
        }

        // Clear impersonation session keys before switching
        $this->flushSession();

        // Restore the original admin session
        Auth::guard($guardName)->login($originalUser);

        if ($impersonatedUser) {
            event(new ImpersonationStopped($originalUser, $impersonatedUser));
        }
    }

    /**
     * Check if the current session is impersonating a user.
     */
    public function isImpersonating(): bool
    {
        return session()->has('impersonate.original_id');
    }

    /**
     * Get the original admin user (the one who started impersonation).
     */
    public function getOriginalUser(): ?Authenticatable
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        $guardName = session('impersonate.guard', $this->getGuardName());
        $provider = Auth::guard($guardName)->getProvider();

        return $provider->retrieveById(session('impersonate.original_id'));
    }

    /**
     * Check if the given user is allowed to impersonate others.
     */
    public function canImpersonate(Authenticatable $user): bool
    {
        $callback = config('impersonate.can_impersonate');

        if (is_callable($callback)) {
            return $callback($user);
        }

        // Default: check for isAdmin() method, then is_admin attribute
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        return (bool) ($user->is_admin ?? false);
    }

    /**
     * Check if the given user can be impersonated.
     */
    public function canBeImpersonated(Authenticatable $user): bool
    {
        // Hard protection for superadmins
        if (config('impersonate.protect_superadmins', true)) {
            if (property_exists($user, 'is_superadmin') || isset($user->is_superadmin)) {
                if ($user->is_superadmin) {
                    return false;
                }
            }
        }

        $callback = config('impersonate.can_be_impersonated');

        if (is_callable($callback)) {
            return $callback($user);
        }

        return true;
    }

    /**
     * Flush all impersonation session keys.
     */
    protected function flushSession(): void
    {
        session()->forget([
            'impersonate.original_id',
            'impersonate.guard',
            'impersonate.log_id',
        ]);
    }

    /**
     * Get the configured auth guard name.
     */
    protected function getGuardName(): ?string
    {
        return config('impersonate.guard');
    }

    /**
     * Resolve the auth guard instance.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function resolveGuard(): \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
    {
        /** @var \Illuminate\Contracts\Auth\StatefulGuard */
        return Auth::guard($this->getGuardName());
    }
}

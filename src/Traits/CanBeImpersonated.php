<?php

namespace Backtik\Impersonate\Traits;

use Backtik\Impersonate\ImpersonateManager;

/**
 * Optional trait for your User model providing convenience methods.
 *
 * Usage: add `use \Backtik\Impersonate\Traits\CanBeImpersonated;` to your User model.
 */
trait CanBeImpersonated
{
    /**
     * Check if this user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return app(ImpersonateManager::class)->canBeImpersonated($this);
    }

    /**
     * Check if this user can impersonate others.
     */
    public function canImpersonate(): bool
    {
        return app(ImpersonateManager::class)->canImpersonate($this);
    }

    /**
     * Check if the current session is impersonating someone.
     */
    public function isBeingImpersonated(): bool
    {
        return app(ImpersonateManager::class)->isImpersonating();
    }
}

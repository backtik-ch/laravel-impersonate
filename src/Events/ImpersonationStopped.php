<?php

namespace Backtik\Impersonate\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;

class ImpersonationStopped
{
    use Dispatchable;

    /**
     * @param  Authenticatable  $admin  The admin who was impersonating.
     * @param  Authenticatable  $impersonated  The user who was being impersonated.
     */
    public function __construct(
        public Authenticatable $admin,
        public Authenticatable $impersonated,
    ) {}
}

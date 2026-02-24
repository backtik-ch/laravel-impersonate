<?php

namespace Backtik\Impersonate\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;

class ImpersonationStarted
{
    use Dispatchable;

    /**
     * @param  Authenticatable  $admin  The admin who started the impersonation.
     * @param  Authenticatable  $impersonated  The user being impersonated.
     */
    public function __construct(
        public Authenticatable $admin,
        public Authenticatable $impersonated,
    ) {}
}

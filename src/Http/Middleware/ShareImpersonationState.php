<?php

namespace Backtik\Impersonate\Http\Middleware;

use Backtik\Impersonate\ImpersonateManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareImpersonationState
{
    public function __construct(protected ImpersonateManager $manager) {}

    /**
     * Handle an incoming request.
     *
     * Shares impersonation state with the frontend via Inertia::share().
     * Only active when config('impersonate.share_inertia') is true and
     * Inertia is installed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('impersonate.share_inertia', false)) {
            return $next($request);
        }

        if (! class_exists(\Inertia\Inertia::class)) {
            return $next($request);
        }

        $isImpersonating = $this->manager->isImpersonating();

        $data = [
            'active' => $isImpersonating,
            'originalUser' => null,
        ];

        if ($isImpersonating) {
            $originalUser = $this->manager->getOriginalUser();
            $data['originalUser'] = $originalUser ? [
                'id' => $originalUser->getAuthIdentifier(),
                'name' => $originalUser->name ?? null,
                'email' => $originalUser->email ?? null,
            ] : null;
        }

        \Inertia\Inertia::share([
            'impersonating' => fn () => $data,
        ]);

        return $next($request);
    }
}

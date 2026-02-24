<?php

namespace Backtik\Impersonate\Http\Controllers;

use Backtik\Impersonate\ImpersonateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function __construct(protected ImpersonateManager $manager) {}

    /**
     * Start impersonating the given user.
     */
    public function start(Request $request, int $id): RedirectResponse
    {
        $guardName = config('impersonate.guard');
        $provider = Auth::guard($guardName)->getProvider();
        $target = $provider->retrieveById($id);

        if (! $target) {
            abort(404, 'User not found.');
        }

        $admin = Auth::guard($guardName)->user();

        abort_unless((bool) $admin, 403, 'You must be authenticated to impersonate.');
        abort_if($this->manager->isImpersonating(), 403, 'Cannot nest impersonation. Leave your current impersonation first.');
        abort_unless($this->manager->canImpersonate($admin), 403, 'You are not allowed to impersonate users.');
        abort_unless($this->manager->canBeImpersonated($target), 403, 'This user cannot be impersonated.');

        $this->manager->start($target);

        $redirect = config('impersonate.redirect_after_start', '/');

        return redirect()->to($redirect)->with('success', __('Now impersonating :name.', [
            'name' => $target->name ?? $target->email ?? $id,
        ]));
    }

    /**
     * Stop impersonating and restore the original admin session.
     */
    public function leave(Request $request): RedirectResponse
    {
        abort_unless($this->manager->isImpersonating(), 403, 'You are not currently impersonating anyone.');

        $this->manager->leave();

        $redirect = config('impersonate.redirect_after_leave', 'back');

        if ($redirect === 'back') {
            return redirect()->back()->with('success', __('Impersonation ended.'));
        }

        return redirect()->to($redirect)->with('success', __('Impersonation ended.'));
    }
}

<?php

use Backtik\Impersonate\Models\ImpersonationLog;

it('creates a log entry when impersonation starts', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $log = ImpersonationLog::latest()->first();

    expect($log)->not->toBeNull();
    expect($log->admin_id)->toBe($admin->id);
    expect($log->impersonated_user_id)->toBe($user->id);
    expect($log->started_at)->not->toBeNull();
    expect($log->stopped_at)->toBeNull();
});

it('updates the log entry when impersonation ends', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $this->post(route('impersonate.leave'));

    $log = ImpersonationLog::latest()->first();

    expect($log->stopped_at)->not->toBeNull();
});

it('stores IP address and user agent in the log', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $log = ImpersonationLog::latest()->first();

    expect($log->ip_address)->not->toBeNull();
    expect($log->user_agent)->not->toBeNull();
});

it('does not create a log when logging is disabled', function () {
    config(['impersonate.log_impersonation' => false]);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect(ImpersonationLog::count())->toBe(0);
});

it('does not update a log on leave when logging is disabled', function () {
    config(['impersonate.log_impersonation' => false]);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    // Should not throw even with no log
    $this->post(route('impersonate.leave'));

    expect(ImpersonationLog::count())->toBe(0);
    expect(auth()->id())->toBe($admin->id);
});

it('stores the log ID in the session during impersonation', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $log = ImpersonationLog::latest()->first();

    expect(session('impersonate.log_id'))->toBe($log->id);
});

it('has correct relationships on the log model', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $log = ImpersonationLog::with(['admin', 'impersonatedUser'])->latest()->first();

    expect($log->admin->id)->toBe($admin->id);
    expect($log->impersonatedUser->id)->toBe($user->id);
});

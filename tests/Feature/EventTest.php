<?php

use Backtik\Impersonate\Events\ImpersonationStarted;
use Backtik\Impersonate\Events\ImpersonationStopped;
use Illuminate\Support\Facades\Event;

it('dispatches ImpersonationStarted event when impersonation begins', function () {
    Event::fake([ImpersonationStarted::class]);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    Event::assertDispatched(ImpersonationStarted::class, function ($event) use ($admin, $user) {
        return $event->admin->getAuthIdentifier() === $admin->id
            && $event->impersonated->getAuthIdentifier() === $user->id;
    });
});

it('dispatches ImpersonationStopped event when impersonation ends', function () {
    Event::fake([ImpersonationStopped::class]);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $this->post(route('impersonate.leave'));

    Event::assertDispatched(ImpersonationStopped::class, function ($event) use ($admin, $user) {
        return $event->admin->getAuthIdentifier() === $admin->id
            && $event->impersonated->getAuthIdentifier() === $user->id;
    });
});

it('does not dispatch events when impersonation is denied', function () {
    Event::fake([ImpersonationStarted::class]);

    $regularUser = createRegularUser();
    $otherUser = createRegularUser();

    $this->actingAs($regularUser)
        ->post(route('impersonate.start', ['id' => $otherUser->id]));

    Event::assertNotDispatched(ImpersonationStarted::class);
});

it('does not dispatch stopped event when not impersonating', function () {
    Event::fake([ImpersonationStopped::class]);

    $admin = createAdminUser();

    $this->actingAs($admin)
        ->post(route('impersonate.leave'));

    Event::assertNotDispatched(ImpersonationStopped::class);
});

it('event payloads contain the correct users', function () {
    Event::fake([ImpersonationStarted::class, ImpersonationStopped::class]);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $this->post(route('impersonate.leave'));

    Event::assertDispatched(ImpersonationStarted::class, 1);
    Event::assertDispatched(ImpersonationStopped::class, 1);
});

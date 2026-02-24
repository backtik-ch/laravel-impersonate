<?php

it('allows leaving impersonation and restores the admin session', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    // Start impersonation
    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect(auth()->id())->toBe($user->id);

    // Leave impersonation
    $response = $this->post(route('impersonate.leave'));

    $response->assertRedirect();
    expect(auth()->id())->toBe($admin->id);
});

it('cleans up session keys after leaving', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $this->post(route('impersonate.leave'));

    expect(session()->has('impersonate.original_id'))->toBeFalse();
    expect(session()->has('impersonate.guard'))->toBeFalse();
    expect(session()->has('impersonate.log_id'))->toBeFalse();
});

it('returns 403 when not impersonating', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.leave'));

    $response->assertForbidden();
});

it('redirects to configured URL after leaving', function () {
    config(['impersonate.redirect_after_leave' => '/admin/users']);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response = $this->post(route('impersonate.leave'));

    $response->assertRedirect('/admin/users');
});

it('redirects back when configured with "back"', function () {
    config(['impersonate.redirect_after_leave' => 'back']);

    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->from('/some-page')
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response = $this->from('/another-page')
        ->post(route('impersonate.leave'));

    $response->assertRedirect('/another-page');
});

it('flashes a success message after leaving impersonation', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response = $this->post(route('impersonate.leave'));

    $response->assertSessionHas('success');
});

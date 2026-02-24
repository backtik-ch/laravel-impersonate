<?php

it('allows an admin to start impersonating a regular user', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertRedirect(config('impersonate.redirect_after_start', '/'));
    expect(auth()->id())->toBe($user->id);
    expect(session('impersonate.original_id'))->toBe($admin->id);
});

it('allows a superadmin to start impersonating a regular user', function () {
    $superadmin = createSuperadminUser();
    $user = createRegularUser();

    $response = $this->actingAs($superadmin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertRedirect(config('impersonate.redirect_after_start', '/'));
    expect(auth()->id())->toBe($user->id);
});

it('returns 404 for a non-existent user', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => 99999]));

    $response->assertNotFound();
});

it('redirects unauthenticated users', function () {
    $user = createRegularUser();

    $response = $this->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertRedirect(route('login'));
});

it('stores the original admin ID in the session', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect(session('impersonate.original_id'))->toBe($admin->id);
    expect(session('impersonate.guard'))->toBeNull();
});

it('switches the authenticated user to the target', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect(auth()->id())->toBe($user->id);
    expect(auth()->user()->email)->toBe($user->email);
});

it('redirects to the configured URL after start', function () {
    config(['impersonate.redirect_after_start' => '/custom-dashboard']);

    $admin = createAdminUser();
    $user = createRegularUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertRedirect('/custom-dashboard');
});

it('flashes a success message after starting impersonation', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertSessionHas('success');
});

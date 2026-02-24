<?php

it('canImpersonate delegates to the manager', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    expect($admin->canImpersonate())->toBeTrue();
    expect($user->canImpersonate())->toBeFalse();
});

it('canBeImpersonated delegates to the manager', function () {
    $user = createRegularUser();
    $superadmin = createSuperadminUser();

    expect($user->canBeImpersonated())->toBeTrue();
    expect($superadmin->canBeImpersonated())->toBeFalse();
});

it('isBeingImpersonated returns false when not impersonating', function () {
    $user = createRegularUser();

    expect($user->isBeingImpersonated())->toBeFalse();
});

it('isBeingImpersonated returns true during impersonation', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect($user->isBeingImpersonated())->toBeTrue();
});

it('isBeingImpersonated returns false after leaving', function () {
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $this->post(route('impersonate.leave'));

    expect($user->isBeingImpersonated())->toBeFalse();
});

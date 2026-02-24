<?php

use Backtik\Impersonate\ImpersonateManager;

it('reports not impersonating by default', function () {
    $manager = app(ImpersonateManager::class);

    expect($manager->isImpersonating())->toBeFalse();
});

it('returns null for original user when not impersonating', function () {
    $manager = app(ImpersonateManager::class);

    expect($manager->getOriginalUser())->toBeNull();
});

it('detects impersonation state after starting', function () {
    $manager = app(ImpersonateManager::class);
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect($manager->isImpersonating())->toBeTrue();
});

it('returns the original admin user during impersonation', function () {
    $manager = app(ImpersonateManager::class);
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $original = $manager->getOriginalUser();

    expect($original)->not->toBeNull();
    expect($original->getAuthIdentifier())->toBe($admin->id);
});

it('detects admin via isAdmin method', function () {
    $manager = app(ImpersonateManager::class);
    $admin = createAdminUser();
    $user = createRegularUser();

    expect($manager->canImpersonate($admin))->toBeTrue();
    expect($manager->canImpersonate($user))->toBeFalse();
});

it('allows any user to be impersonated by default', function () {
    $manager = app(ImpersonateManager::class);
    $user = createRegularUser();

    expect($manager->canBeImpersonated($user))->toBeTrue();
});

it('blocks superadmins from being impersonated', function () {
    $manager = app(ImpersonateManager::class);
    $superadmin = createSuperadminUser();

    expect($manager->canBeImpersonated($superadmin))->toBeFalse();
});

it('allows superadmins to be impersonated when protection is off', function () {
    config(['impersonate.protect_superadmins' => false]);

    $manager = app(ImpersonateManager::class);
    $superadmin = createSuperadminUser();

    expect($manager->canBeImpersonated($superadmin))->toBeTrue();
});

it('uses custom can_impersonate closure', function () {
    config(['impersonate.can_impersonate' => fn ($user) => $user->email === 'allowed@example.com']);

    $manager = app(ImpersonateManager::class);
    $allowed = createAdminUser(['email' => 'allowed@example.com']);
    $denied = createAdminUser(['email' => 'denied@example.com']);

    expect($manager->canImpersonate($allowed))->toBeTrue();
    expect($manager->canImpersonate($denied))->toBeFalse();
});

it('uses custom can_be_impersonated closure', function () {
    config(['impersonate.can_be_impersonated' => fn ($user) => $user->name !== 'Protected']);

    $manager = app(ImpersonateManager::class);
    $protected = createRegularUser(['name' => 'Protected']);
    $normal = createRegularUser(['name' => 'Normal']);

    expect($manager->canBeImpersonated($protected))->toBeFalse();
    expect($manager->canBeImpersonated($normal))->toBeTrue();
});

it('clears impersonation state after leaving', function () {
    $manager = app(ImpersonateManager::class);
    $admin = createAdminUser();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    expect($manager->isImpersonating())->toBeTrue();

    $this->post(route('impersonate.leave'));

    expect($manager->isImpersonating())->toBeFalse();
    expect($manager->getOriginalUser())->toBeNull();
});

<?php

it('prevents a non-admin from impersonating', function () {
    $regularUser = createRegularUser();
    $otherUser = createRegularUser();

    $response = $this->actingAs($regularUser)
        ->post(route('impersonate.start', ['id' => $otherUser->id]));

    $response->assertForbidden();
    expect(auth()->id())->toBe($regularUser->id);
});

it('prevents impersonating a superadmin when protection is enabled', function () {
    $admin = createAdminUser();
    $superadmin = createSuperadminUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $superadmin->id]));

    $response->assertForbidden();
    expect(auth()->id())->toBe($admin->id);
    expect(session()->has('impersonate.original_id'))->toBeFalse();
});

it('allows impersonating a superadmin when protection is disabled', function () {
    config(['impersonate.protect_superadmins' => false]);

    $admin = createAdminUser();
    $superadmin = createSuperadminUser();

    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $superadmin->id]));

    $response->assertRedirect();
    expect(auth()->id())->toBe($superadmin->id);
});

it('prevents nested impersonation', function () {
    $admin = createAdminUser();
    $user1 = createRegularUser();
    $user2 = createRegularUser();

    $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user1->id]));

    expect(auth()->id())->toBe($user1->id);

    $response = $this->post(route('impersonate.start', ['id' => $user2->id]));

    $response->assertForbidden();
    expect(auth()->id())->toBe($user1->id);
});

it('respects a custom can_impersonate closure', function () {
    config(['impersonate.can_impersonate' => fn ($user) => $user->email === 'special@example.com']);

    $admin = createAdminUser();
    $specialAdmin = createAdminUser(['email' => 'special@example.com']);
    $user = createRegularUser();

    // Regular admin blocked by custom closure
    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertForbidden();

    // Special admin allowed
    $response = $this->actingAs($specialAdmin)
        ->post(route('impersonate.start', ['id' => $user->id]));

    $response->assertRedirect();
    expect(auth()->id())->toBe($user->id);
});

it('respects a custom can_be_impersonated closure', function () {
    config(['impersonate.can_be_impersonated' => fn ($user) => $user->email !== 'protected@example.com']);

    $admin = createAdminUser();
    $protectedUser = createRegularUser(['email' => 'protected@example.com']);
    $normalUser = createRegularUser();

    // Protected user cannot be impersonated
    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $protectedUser->id]));

    $response->assertForbidden();

    // Normal user can be impersonated
    $response = $this->actingAs($admin)
        ->post(route('impersonate.start', ['id' => $normalUser->id]));

    $response->assertRedirect();
    expect(auth()->id())->toBe($normalUser->id);
});

it('uses is_admin attribute fallback when isAdmin method does not exist', function () {
    $manager = app(\Backtik\Impersonate\ImpersonateManager::class);

    // Create a mock user without isAdmin method but with is_admin attribute
    $user = new class implements \Illuminate\Contracts\Auth\Authenticatable
    {
        public bool $is_admin = true;

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): mixed
        {
            return 1;
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return '';
        }
    };

    expect($manager->canImpersonate($user))->toBeTrue();

    $user->is_admin = false;
    expect($manager->canImpersonate($user))->toBeFalse();
});

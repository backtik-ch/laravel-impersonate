<?php

namespace Backtik\Impersonate\Tests\Fixtures;

use Backtik\Impersonate\Traits\CanBeImpersonated;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal User model for package testing.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_admin
 * @property bool $is_superadmin
 */
class User extends Authenticatable
{
    use CanBeImpersonated;

    protected $guarded = [];

    protected $table = 'users';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_superadmin' => 'boolean',
        ];
    }

    /**
     * Default authorization check used by the package when no closure is configured.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
}

<?php

use Backtik\Impersonate\Tests\Fixtures\User;
use Backtik\Impersonate\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

/**
 * Create an admin user (is_admin = true, is_superadmin = false).
 */
function createAdminUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Admin User',
        'email' => 'admin-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
        'is_superadmin' => false,
    ], $attributes));
}

/**
 * Create a superadmin user (is_admin = true, is_superadmin = true).
 */
function createSuperadminUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Superadmin User',
        'email' => 'superadmin-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
        'is_superadmin' => true,
    ], $attributes));
}

/**
 * Create a regular user (no admin privileges).
 */
function createRegularUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Regular User',
        'email' => 'user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
        'is_superadmin' => false,
    ], $attributes));
}

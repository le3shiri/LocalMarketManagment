<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Roles and permissions are reference data every feature depends on. */
    protected bool $seed = true;

    protected string $seeder = RolePermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Blade views; they don't need compiled assets.
        $this->withoutVite();
    }
}

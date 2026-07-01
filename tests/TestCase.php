<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every route belongs to the "web" group and passes through
     * IdentifikasiTenant, so tests need a tenant matching the request
     * host ("localhost", from APP_URL) once the database is ready.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            Tenant::factory()->create([
                'domain' => 'localhost',
                'status_aktif' => true,
            ]);
        }
    }
}

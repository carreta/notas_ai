<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_run_from_zero_and_app_boots(): void
    {
        // RefreshDatabase runs migrations on in-memory SQLite;
        // Schema::hasTable verifies each expected table was created
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('sessions'));

        $response = $this->get('/');
        $response->assertStatus(200);
    }
}

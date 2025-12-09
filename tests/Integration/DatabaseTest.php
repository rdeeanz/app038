<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_connect_to_database(): void
    {
        $result = DB::select('SELECT 1 as test');

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->test);
    }

    public function test_can_execute_migrations(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");

        $this->assertNotEmpty($tables);
    }

    public function test_can_run_seeders(): void
    {
        $this->artisan('migrate')->assertSuccessful();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder'])->assertSuccessful();

        $roles = DB::table('roles')->count();
        $this->assertGreaterThan(0, $roles);
    }
}


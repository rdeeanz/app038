<?php

namespace Tests\Feature\Inertia;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IntegrationMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_monitor_page_renders_with_inertia(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get('/integration-monitor');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('IntegrationMonitor')
            ->has('auth.user')
            ->has('integrations')
            ->has('syncHistory')
        );
    }

    public function test_integration_monitor_requires_authentication(): void
    {
        $response = $this->get('/integration-monitor');

        $response->assertRedirect('/login');
    }

    public function test_integration_monitor_requires_permission(): void
    {
        $user = User::factory()->create();
        // User without permission

        $response = $this->actingAs($user)->get('/integration-monitor');

        $response->assertStatus(403);
    }
}


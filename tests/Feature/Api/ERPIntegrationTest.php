<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ERPIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_initiate_erp_sync(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/erp-integration/sync', [
            'type' => 'products',
            'endpoint' => '/api/products',
            'priority' => 'normal',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        Queue::assertPushed(\App\Modules\ERPIntegration\Jobs\SyncERPDataJob::class);
    }

    public function test_sync_requires_permission(): void
    {
        $user = User::factory()->create();
        // User without permission

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/erp-integration/sync', [
            'type' => 'products',
            'endpoint' => '/api/products',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_test_erp_connection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/erp-integration/test-connection', [
            'integration_id' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'connected',
                'message',
            ]);
    }
}


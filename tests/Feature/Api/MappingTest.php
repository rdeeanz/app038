<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_get_mapping_file(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        Sanctum::actingAs($user);

        // Create a test mapping file
        Storage::put('config/mappings/test.yaml', 'target: test\nfields:\n  id: source: id');

        $response = $this->getJson('/api/mappings/test.yaml');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'content',
            ]);
    }

    public function test_can_create_mapping_file(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mappings', [
            'filename' => 'new-mapping.yaml',
            'content' => 'target: new-mapping\nfields:\n  id:\n    source: id',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'content',
            ]);
    }

    public function test_can_update_mapping_file(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        Sanctum::actingAs($user);

        // Create initial file
        Storage::put('config/mappings/test.yaml', 'target: test');

        $response = $this->putJson('/api/mappings/test.yaml', [
            'content' => 'target: test\nfields:\n  updated: true',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Mapping file updated successfully',
            ]);
    }

    public function test_can_test_mapping_transformation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        Sanctum::actingAs($user);

        Storage::put('config/mappings/test.yaml', <<<'YAML'
target: test
fields:
  order_number:
    source: order_number
  order_date:
    source: order_date
YAML
        );

        $response = $this->postJson('/api/mappings/test', [
            'mapping_file' => 'test.yaml',
            'test_data' => [
                'order_number' => 'ORD-001',
                'order_date' => '2024-01-15',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'result',
            ]);
    }
}


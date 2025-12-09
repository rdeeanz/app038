<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.view');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/inventory/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_can_create_product(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.create');

        Sanctum::actingAs($user);

        $productData = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'stock' => 100,
        ];

        $response = $this->postJson('/api/inventory/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'price',
                    'stock',
                ],
            ]);
    }

    public function test_can_update_stock(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.update');

        Sanctum::actingAs($user);

        // First create a product
        $createResponse = $this->postJson('/api/inventory/products', [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'stock' => 100,
        ]);

        $productId = $createResponse->json('data.id');

        // Update stock
        $response = $this->patchJson("/api/inventory/products/{$productId}/stock", [
            'stock' => 150,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'stock' => 150,
                ],
            ]);
    }

    public function test_can_get_low_stock_alerts(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.view');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/inventory/low-stock');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }
}


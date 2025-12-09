<?php

namespace App\Modules\Inventory\Repositories;

interface InventoryRepositoryInterface
{
    /**
     * List all products
     */
    public function list(array $filters = []): array;

    /**
     * Find product by ID
     */
    public function find(string $id): ?array;

    /**
     * Create a new product
     */
    public function create(array $data): array;

    /**
     * Update stock levels
     */
    public function updateStock(string $id, array $data): array;

    /**
     * Get low stock products
     */
    public function getLowStock(array $filters = []): array;

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array;
}


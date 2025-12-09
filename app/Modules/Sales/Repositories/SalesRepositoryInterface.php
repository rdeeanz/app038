<?php

namespace App\Modules\Sales\Repositories;

interface SalesRepositoryInterface
{
    /**
     * List all orders
     */
    public function list(array $filters = []): array;

    /**
     * Find order by ID
     */
    public function find(string $id): ?array;

    /**
     * Create a new order
     */
    public function create(array $data): array;

    /**
     * Update order
     */
    public function update(string $id, array $data): array;

    /**
     * Delete order
     */
    public function delete(string $id): bool;

    /**
     * Get sales statistics
     */
    public function getStatistics(array $filters = []): array;
}


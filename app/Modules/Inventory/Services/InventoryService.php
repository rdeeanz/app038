<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Jobs\UpdateInventoryJob;
use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        protected InventoryRepositoryInterface $repository
    ) {}

    /**
     * List products with filters
     */
    public function listProducts(array $filters = []): array
    {
        return $this->repository->list($filters);
    }

    /**
     * Create a new product
     */
    public function createProduct(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $product = $this->repository->create($data);

            Log::info('Product created', [
                'product_id' => $product['id'],
            ]);

            return $product;
        });
    }

    /**
     * Get product by ID
     */
    public function getProduct(string $id): array
    {
        $product = $this->repository->find($id);

        if (!$product) {
            throw new \Exception("Product not found: {$id}");
        }

        return $product;
    }

    /**
     * Update stock levels
     */
    public function updateStock(string $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->repository->updateStock($id, $data);

            // Dispatch job to handle inventory updates asynchronously
            UpdateInventoryJob::dispatch($id, $data)
                ->onQueue('inventory');

            Log::info('Stock updated', [
                'product_id' => $id,
                'data' => $data,
            ]);

            return $product;
        });
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(array $filters = []): array
    {
        return $this->repository->getLowStock($filters);
    }

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}


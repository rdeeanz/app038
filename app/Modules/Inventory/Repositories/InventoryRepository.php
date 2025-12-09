<?php

namespace App\Modules\Inventory\Repositories;

use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{
    protected string $table = 'products';

    /**
     * List all products
     */
    public function list(array $filters = []): array
    {
        $query = DB::table($this->table);

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['low_stock'])) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        return $query->orderBy('name')->get()->toArray();
    }

    /**
     * Find product by ID
     */
    public function find(string $id): ?array
    {
        $product = DB::table($this->table)->where('id', $id)->first();

        return $product ? (array) $product : null;
    }

    /**
     * Create a new product
     */
    public function create(array $data): array
    {
        $id = DB::table($this->table)->insertGetId([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->find($id);
    }

    /**
     * Update stock levels
     */
    public function updateStock(string $id, array $data): array
    {
        DB::table($this->table)
            ->where('id', $id)
            ->update([
                'stock' => DB::raw('stock + ' . ($data['quantity'] ?? 0)),
                'updated_at' => now(),
            ]);

        return $this->find($id);
    }

    /**
     * Get low stock products
     */
    public function getLowStock(array $filters = []): array
    {
        $query = DB::table($this->table)
            ->whereColumn('stock', '<=', 'min_stock');

        return $query->orderBy('stock', 'asc')->get()->toArray();
    }

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_products' => DB::table($this->table)->count(),
            'total_stock_value' => DB::table($this->table)->sum(DB::raw('stock * price')),
            'low_stock_count' => DB::table($this->table)
                ->whereColumn('stock', '<=', 'min_stock')
                ->count(),
        ];
    }
}


<?php

namespace App\Modules\Sales\Repositories;

use Illuminate\Support\Facades\DB;

class SalesRepository implements SalesRepositoryInterface
{
    protected string $table = 'orders';

    /**
     * List all orders
     */
    public function list(array $filters = []): array
    {
        $query = DB::table($this->table);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Find order by ID
     */
    public function find(string $id): ?array
    {
        $order = DB::table($this->table)->where('id', $id)->first();

        return $order ? (array) $order : null;
    }

    /**
     * Create a new order
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
     * Update order
     */
    public function update(string $id, array $data): array
    {
        DB::table($this->table)
            ->where('id', $id)
            ->update([
                ...$data,
                'updated_at' => now(),
            ]);

        return $this->find($id);
    }

    /**
     * Delete order
     */
    public function delete(string $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    /**
     * Get sales statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = DB::table($this->table);

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total'),
            'average_order_value' => $query->avg('total'),
        ];
    }
}


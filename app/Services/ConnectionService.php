<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class ConnectionService
{
    /**
     * Get database connection with fallback logic
     *
     * @param string $connection
     * @return \Illuminate\Database\Connection
     */
    public static function getDatabaseConnection(string $connection = 'pgsql')
    {
        try {
            $conn = DB::connection($connection);
            $conn->getPdo(); // Test connection
            return $conn;
        } catch (Exception $e) {
            Log::warning("Primary database connection failed: {$e->getMessage()}", [
                'connection' => $connection,
            ]);

            // Try fallback connection
            $fallbackConnection = $connection . '_fallback';
            if (config("database.connections.{$fallbackConnection}")) {
                try {
                    $fallbackConn = DB::connection($fallbackConnection);
                    $fallbackConn->getPdo();
                    Log::info("Using fallback database connection: {$fallbackConnection}");
                    return $fallbackConn;
                } catch (Exception $fallbackException) {
                    Log::error("Fallback database connection also failed: {$fallbackException->getMessage()}");
                    throw $fallbackException;
                }
            }

            throw $e;
        }
    }

    /**
     * Get Redis connection with fallback logic
     *
     * @param string $connection
     * @return \Illuminate\Redis\Connections\Connection
     */
    public static function getRedisConnection(string $connection = 'default')
    {
        try {
            $redis = Redis::connection($connection);
            $redis->ping(); // Test connection
            return $redis;
        } catch (Exception $e) {
            Log::warning("Primary Redis connection failed: {$e->getMessage()}", [
                'connection' => $connection,
            ]);

            // Try fallback connection
            if (config("database.redis.fallback")) {
                try {
                    $fallbackRedis = Redis::connection('fallback');
                    $fallbackRedis->ping();
                    Log::info("Using fallback Redis connection");
                    return $fallbackRedis;
                } catch (Exception $fallbackException) {
                    Log::error("Fallback Redis connection also failed: {$fallbackException->getMessage()}");
                    throw $fallbackException;
                }
            }

            throw $e;
        }
    }

    /**
     * Get cache connection with fallback logic
     *
     * @param string $store
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public static function getCacheConnection(string $store = 'redis')
    {
        try {
            $cache = Cache::store($store);
            $cache->put('connection_test', 'ok', 1); // Test connection
            return $cache;
        } catch (Exception $e) {
            Log::warning("Primary cache connection failed: {$e->getMessage()}", [
                'store' => $store,
            ]);

            // Fallback to database cache
            try {
                $fallbackCache = Cache::store('database');
                Log::info("Using fallback database cache");
                return $fallbackCache;
            } catch (Exception $fallbackException) {
                Log::error("Fallback cache connection also failed: {$fallbackException->getMessage()}");
                // Last resort: file cache
                return Cache::store('file');
            }
        }
    }

    /**
     * Get queue connection with fallback logic
     *
     * @param string $connection
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public static function getQueueConnection(string $connection = 'rabbitmq')
    {
        try {
            $queue = app('queue')->connection($connection);
            // Test by getting the queue name
            $queue->getConnectionName();
            return $queue;
        } catch (Exception $e) {
            Log::warning("Primary queue connection failed: {$e->getMessage()}", [
                'connection' => $connection,
            ]);

            // Try fallback connections in order
            $fallbackConnections = [
                $connection . '_fallback',
                'redis',
                'redis_fallback',
                'database',
            ];

            foreach ($fallbackConnections as $fallbackConnection) {
                if (config("queue.connections.{$fallbackConnection}")) {
                    try {
                        $fallbackQueue = app('queue')->connection($fallbackConnection);
                        $fallbackQueue->getConnectionName();
                        Log::info("Using fallback queue connection: {$fallbackConnection}");
                        return $fallbackQueue;
                    } catch (Exception $fallbackException) {
                        Log::debug("Fallback queue connection failed: {$fallbackConnection}");
                        continue;
                    }
                }
            }

            // Last resort: sync driver
            Log::warning("All queue connections failed, using sync driver");
            return app('queue')->connection('sync');
        }
    }

    /**
     * Test all connections and return status
     *
     * @return array
     */
    public static function testAllConnections(): array
    {
        $status = [
            'database' => false,
            'redis' => false,
            'cache' => false,
            'queue' => false,
        ];

        // Test database
        try {
            self::getDatabaseConnection();
            $status['database'] = true;
        } catch (Exception $e) {
            $status['database'] = $e->getMessage();
        }

        // Test Redis
        try {
            self::getRedisConnection();
            $status['redis'] = true;
        } catch (Exception $e) {
            $status['redis'] = $e->getMessage();
        }

        // Test Cache
        try {
            self::getCacheConnection();
            $status['cache'] = true;
        } catch (Exception $e) {
            $status['cache'] = $e->getMessage();
        }

        // Test Queue
        try {
            self::getQueueConnection();
            $status['queue'] = true;
        } catch (Exception $e) {
            $status['queue'] = $e->getMessage();
        }

        return $status;
    }
}


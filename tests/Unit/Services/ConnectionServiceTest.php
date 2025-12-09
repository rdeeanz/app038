<?php

namespace Tests\Unit\Services;

use App\Services\ConnectionService;
use Tests\TestCase;

class ConnectionServiceTest extends TestCase
{
    public function test_can_get_database_connection(): void
    {
        $connection = ConnectionService::getDatabaseConnection('pgsql');

        $this->assertNotNull($connection);
    }

    public function test_can_get_redis_connection(): void
    {
        $connection = ConnectionService::getRedisConnection('default');

        $this->assertNotNull($connection);
    }

    public function test_can_get_cache_connection(): void
    {
        $cache = ConnectionService::getCacheConnection('redis');

        $this->assertNotNull($cache);
    }

    public function test_can_get_queue_connection(): void
    {
        $queue = ConnectionService::getQueueConnection('rabbitmq');

        $this->assertNotNull($queue);
    }

    public function test_can_test_all_connections(): void
    {
        $status = ConnectionService::testAllConnections();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('database', $status);
        $this->assertArrayHasKey('redis', $status);
        $this->assertArrayHasKey('cache', $status);
        $this->assertArrayHasKey('queue', $status);
    }
}

<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_connect_to_redis(): void
    {
        $result = Redis::ping();

        $this->assertEquals('PONG', $result);
    }

    public function test_can_store_and_retrieve_from_cache(): void
    {
        Cache::put('test_key', 'test_value', 60);

        $value = Cache::get('test_key');

        $this->assertEquals('test_value', $value);
    }

    public function test_can_store_and_retrieve_from_redis(): void
    {
        Redis::set('test_key', 'test_value');
        Redis::expire('test_key', 60);

        $value = Redis::get('test_key');

        $this->assertEquals('test_value', $value);
    }

    public function test_cache_remember_works(): void
    {
        $value = Cache::remember('remember_key', 60, function () {
            return 'cached_value';
        });

        $this->assertEquals('cached_value', $value);

        // Second call should use cache
        $cachedValue = Cache::remember('remember_key', 60, function () {
            return 'new_value';
        });

        $this->assertEquals('cached_value', $cachedValue);
    }
}


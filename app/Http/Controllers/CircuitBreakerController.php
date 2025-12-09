<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CircuitBreaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CircuitBreakerController extends Controller
{
    /**
     * Get circuit breaker status for all services
     */
    public function index(): JsonResponse
    {
        $services = config('circuit_breaker.services', []);
        $metrics = [];

        foreach (array_keys($services) as $service) {
            $metrics[$service] = CircuitBreaker::getMetrics($service);
        }

        // Add default service
        $metrics['default'] = CircuitBreaker::getMetrics('default');

        return response()->json([
            'circuit_breakers' => $metrics,
        ]);
    }

    /**
     * Get circuit breaker status for a specific service
     */
    public function show(string $service): JsonResponse
    {
        $metrics = CircuitBreaker::getMetrics($service);

        return response()->json([
            'service' => $service,
            'state' => $metrics['state'],
            'failure_count' => $metrics['failure_count'],
            'success_count' => $metrics['success_count'],
            'last_failure' => $metrics['last_failure']?->toIso8601String(),
        ]);
    }

    /**
     * Reset circuit breaker for a service
     */
    public function reset(string $service): JsonResponse
    {
        $prefix = config('circuit_breaker.cache_prefix', 'circuit_breaker');
        
        \Cache::forget("{$prefix}:{$service}:state");
        \Cache::forget("{$prefix}:{$service}:failure_count");
        \Cache::forget("{$prefix}:{$service}:success_count");
        \Cache::forget("{$prefix}:{$service}:last_failure");

        return response()->json([
            'message' => "Circuit breaker reset for service: {$service}",
            'service' => $service,
        ]);
    }
}


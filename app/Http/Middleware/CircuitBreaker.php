<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CircuitBreaker
{
    /**
     * Circuit breaker states
     */
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';

    /**
     * Configuration
     */
    protected int $failureThreshold;
    protected int $successThreshold;
    protected int $timeout;
    protected string $cachePrefix;

    public function __construct()
    {
        $this->failureThreshold = config('circuit_breaker.failure_threshold', 5);
        $this->successThreshold = config('circuit_breaker.success_threshold', 2);
        $this->timeout = config('circuit_breaker.timeout', 60);
        $this->cachePrefix = config('circuit_breaker.cache_prefix', 'circuit_breaker');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $service = null): Response
    {
        $service = $service ?? $this->getServiceName($request);
        $state = $this->getState($service);

        // If circuit is open, check if timeout has passed
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset($service)) {
                $this->setState($service, self::STATE_HALF_OPEN);
                $state = self::STATE_HALF_OPEN;
            } else {
                return $this->handleOpenCircuit($request, $service);
            }
        }

        try {
            $response = $next($request);

            // Check if request was successful
            if ($this->isSuccessful($response)) {
                $this->handleSuccess($service, $state);
            } else {
                $this->handleFailure($service, $state);
            }

            return $response;
        } catch (\Throwable $e) {
            $this->handleFailure($service, $state);
            
            Log::error('Circuit breaker: Request failed', [
                'service' => $service,
                'error' => $e->getMessage(),
                'state' => $state,
            ]);

            throw $e;
        }
    }

    /**
     * Get service name from request
     */
    protected function getServiceName(Request $request): string
    {
        return $request->route()?->getName() 
            ?? $request->path() 
            ?? 'default';
    }

    /**
     * Get current circuit state
     */
    protected function getState(string $service): string
    {
        return Cache::get("{$this->cachePrefix}:{$service}:state", self::STATE_CLOSED);
    }

    /**
     * Set circuit state
     */
    protected function setState(string $service, string $state): void
    {
        Cache::put("{$this->cachePrefix}:{$service}:state", $state, $this->timeout * 2);
        
        Log::info("Circuit breaker state changed", [
            'service' => $service,
            'state' => $state,
        ]);
    }

    /**
     * Check if circuit should attempt reset
     */
    protected function shouldAttemptReset(string $service): bool
    {
        $lastFailure = Cache::get("{$this->cachePrefix}:{$service}:last_failure");
        
        if (!$lastFailure) {
            return true;
        }

        return now()->diffInSeconds($lastFailure) >= $this->timeout;
    }

    /**
     * Handle successful request
     */
    protected function handleSuccess(string $service, string $state): void
    {
        if ($state === self::STATE_HALF_OPEN) {
            $successCount = Cache::increment("{$this->cachePrefix}:{$service}:success_count", 1);
            
            if ($successCount >= $this->successThreshold) {
                $this->resetCircuit($service);
                Log::info("Circuit breaker closed after successful requests", [
                    'service' => $service,
                    'success_count' => $successCount,
                ]);
            }
        } else {
            // Reset failure count on success
            Cache::forget("{$this->cachePrefix}:{$service}:failure_count");
        }
    }

    /**
     * Handle failed request
     */
    protected function handleFailure(string $service, string $state): void
    {
        $failureCount = Cache::increment("{$this->cachePrefix}:{$service}:failure_count", 1);
        Cache::put("{$this->cachePrefix}:{$service}:last_failure", now(), $this->timeout * 2);

        if ($state === self::STATE_HALF_OPEN) {
            // Immediately open circuit on failure in half-open state
            $this->setState($service, self::STATE_OPEN);
            Cache::forget("{$this->cachePrefix}:{$service}:success_count");
            
            Log::warning("Circuit breaker opened from half-open state", [
                'service' => $service,
            ]);
        } elseif ($failureCount >= $this->failureThreshold) {
            $this->setState($service, self::STATE_OPEN);
            
            Log::error("Circuit breaker opened due to failure threshold", [
                'service' => $service,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        }
    }

    /**
     * Handle open circuit
     */
    protected function handleOpenCircuit(Request $request, string $service): Response
    {
        Log::warning("Circuit breaker: Request blocked (circuit open)", [
            'service' => $service,
            'path' => $request->path(),
        ]);

        return response()->json([
            'error' => 'Service temporarily unavailable',
            'message' => 'The service is currently experiencing issues. Please try again later.',
            'service' => $service,
        ], 503);
    }

    /**
     * Check if response is successful
     */
    protected function isSuccessful(Response $response): bool
    {
        return $response->getStatusCode() < 500;
    }

    /**
     * Reset circuit to closed state
     */
    protected function resetCircuit(string $service): void
    {
        $this->setState($service, self::STATE_CLOSED);
        Cache::forget("{$this->cachePrefix}:{$service}:failure_count");
        Cache::forget("{$this->cachePrefix}:{$service}:success_count");
        Cache::forget("{$this->cachePrefix}:{$service}:last_failure");
    }

    /**
     * Get circuit breaker metrics
     */
    public static function getMetrics(string $service): array
    {
        $prefix = config('circuit_breaker.cache_prefix', 'circuit_breaker');
        
        return [
            'state' => Cache::get("{$prefix}:{$service}:state", self::STATE_CLOSED),
            'failure_count' => Cache::get("{$prefix}:{$service}:failure_count", 0),
            'success_count' => Cache::get("{$prefix}:{$service}:success_count", 0),
            'last_failure' => Cache::get("{$prefix}:{$service}:last_failure"),
        ];
    }
}

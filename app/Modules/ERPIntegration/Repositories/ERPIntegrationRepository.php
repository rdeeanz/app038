<?php

namespace App\Modules\ERPIntegration\Repositories;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ERPIntegrationRepository implements ERPIntegrationRepositoryInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('erp_integration.base_url', '');
        $this->apiKey = config('erp_integration.api_key');
    }

    /**
     * List all integrations
     */
    public function list(array $filters = []): array
    {
        // Implement listing logic
        return [];
    }

    /**
     * Test connection to ERP system
     */
    public function testConnection(): bool
    {
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            Log::warning('ERP connection test skipped: API key or base URL not configured');
            return false;
        }

        try {
            $headers = [];
            if ($this->apiKey) {
                $headers['Authorization'] = "Bearer {$this->apiKey}";
            }

            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->get("{$this->baseUrl}/health");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ERP connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fetch data from ERP system
     */
    public function fetchData(string $endpoint, array $params = []): array
    {
        if (empty($this->baseUrl)) {
            throw new \Exception('ERP base URL is not configured');
        }

        try {
            $headers = [];
            if ($this->apiKey) {
                $headers['Authorization'] = "Bearer {$this->apiKey}";
            }

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to fetch data: {$response->status()}");
        } catch (\Exception $e) {
            Log::error('ERP data fetch failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send data to ERP system
     */
    public function sendData(string $endpoint, array $data): bool
    {
        if (empty($this->baseUrl)) {
            Log::error('ERP data send failed: base URL is not configured');
            return false;
        }

        try {
            $headers = [];
            if ($this->apiKey) {
                $headers['Authorization'] = "Bearer {$this->apiKey}";
            }

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post("{$this->baseUrl}/{$endpoint}", $data);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ERP data send failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}


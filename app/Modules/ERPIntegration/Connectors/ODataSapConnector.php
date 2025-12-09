<?php

namespace App\Modules\ERPIntegration\Connectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ODataSapConnector implements SapConnectorInterface
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('sap.odata.base_url', '');
        $this->username = config('sap.odata.username', '');
        $this->password = config('sap.odata.password', '');
        $this->timeout = config('sap.odata.timeout', 30);
    }

    /**
     * Test connection to SAP system
     */
    public function testConnection(): bool
    {
        // Return false if configuration is missing
        if (empty($this->baseUrl) || empty($this->username)) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/\$metadata");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SAP OData connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute OData query
     */
    public function odataQuery(string $entitySet, array $filters = [], array $options = []): array
    {
        try {
            $url = "{$this->baseUrl}/{$entitySet}";

            // Build OData query string
            $queryParams = [];

            if (!empty($filters)) {
                $queryParams['$filter'] = $this->buildFilter($filters);
            }

            if (isset($options['select'])) {
                $queryParams['$select'] = implode(',', $options['select']);
            }

            if (isset($options['expand'])) {
                $queryParams['$expand'] = implode(',', $options['expand']);
            }

            if (isset($options['orderby'])) {
                $queryParams['$orderby'] = is_array($options['orderby'])
                    ? implode(',', $options['orderby'])
                    : $options['orderby'];
            }

            if (isset($options['top'])) {
                $queryParams['$top'] = $options['top'];
            }

            if (isset($options['skip'])) {
                $queryParams['$skip'] = $options['skip'];
            }

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("OData query failed: {$response->status()}");
        } catch (\Exception $e) {
            Log::error('SAP OData query failed', [
                'entity_set' => $entitySet,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create entity via OData
     */
    public function odataCreate(string $entitySet, array $data): array
    {
        try {
            $url = "{$this->baseUrl}/{$entitySet}";

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("OData create failed: {$response->status()}");
        } catch (\Exception $e) {
            Log::error('SAP OData create failed', [
                'entity_set' => $entitySet,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update entity via OData
     */
    public function odataUpdate(string $entitySet, string $key, array $data): array
    {
        try {
            $url = "{$this->baseUrl}/{$entitySet}({$key})";

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->patch($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("OData update failed: {$response->status()}");
        } catch (\Exception $e) {
            Log::error('SAP OData update failed', [
                'entity_set' => $entitySet,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete entity via OData
     */
    public function odataDelete(string $entitySet, string $key): bool
    {
        try {
            $url = "{$this->baseUrl}/{$entitySet}({$key})";

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->delete($url);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SAP OData delete failed', [
                'entity_set' => $entitySet,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build OData filter string
     */
    protected function buildFilter(array $filters): string
    {
        $conditions = [];

        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                // Handle operators: eq, ne, gt, ge, lt, le, contains, startswith
                $operator = $value['operator'] ?? 'eq';
                $filterValue = $value['value'] ?? $value;
                $conditions[] = "{$field} {$operator} '{$filterValue}'";
            } else {
                $conditions[] = "{$field} eq '{$value}'";
            }
        }

        return implode(' and ', $conditions);
    }

    /**
     * RFC/BAPI methods - not implemented for OData connector
     */
    public function rfcCall(string $functionName, array $parameters = []): array
    {
        throw new \BadMethodCallException('RFC calls are not supported by OData connector');
    }

    public function bapiCall(string $bapiName, array $parameters = []): array
    {
        throw new \BadMethodCallException('BAPI calls are not supported by OData connector');
    }

    public function bapiCommit(): array
    {
        throw new \BadMethodCallException('BAPI commit is not supported by OData connector');
    }

    public function bapiRollback(): bool
    {
        throw new \BadMethodCallException('BAPI rollback is not supported by OData connector');
    }

    /**
     * IDoc methods - not implemented for OData connector
     */
    public function idocSend(string $idocType, array $idocData): array
    {
        throw new \BadMethodCallException('IDoc operations are not supported by OData connector');
    }

    public function idocReceive(string $idocNumber): array
    {
        throw new \BadMethodCallException('IDoc operations are not supported by OData connector');
    }

    public function idocStatus(string $idocNumber): array
    {
        throw new \BadMethodCallException('IDoc operations are not supported by OData connector');
    }
}


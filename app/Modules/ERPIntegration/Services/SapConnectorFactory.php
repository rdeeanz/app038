<?php

namespace App\Modules\ERPIntegration\Services;

use App\Modules\ERPIntegration\Connectors\IdocSapConnector;
use App\Modules\ERPIntegration\Connectors\ODataSapConnector;
use App\Modules\ERPIntegration\Connectors\RfcBapiSapConnector;
use App\Modules\ERPIntegration\Connectors\SapConnectorInterface;
use Illuminate\Support\Facades\Log;

class SapConnectorFactory
{
    /**
     * Create SAP connector instance based on type
     *
     * @param string|null $type Connector type (odata, rfc, idoc)
     * @return SapConnectorInterface
     */
    public static function create(?string $type = null): SapConnectorInterface
    {
        $type = $type ?? config('sap.default', 'odata');

        return match (strtolower($type)) {
            'odata' => new ODataSapConnector(),
            'rfc', 'bapi' => new RfcBapiSapConnector(),
            'idoc' => new IdocSapConnector(),
            default => throw new \InvalidArgumentException("Unknown SAP connector type: {$type}"),
        };
    }

    /**
     * Test connection for all connector types
     *
     * @return array Connection test results
     */
    public static function testAllConnections(): array
    {
        $results = [];

        foreach (['odata', 'rfc', 'idoc'] as $type) {
            try {
                $connector = self::create($type);
                $connected = $connector->testConnection();
                $results[$type] = [
                    'connected' => $connected,
                    'message' => $connected ? 'Connection successful' : 'Configuration missing or connection failed',
                ];
            } catch (\TypeError $e) {
                // Handle type errors (null assignment to non-nullable properties)
                $results[$type] = [
                    'connected' => false,
                    'message' => 'Configuration not set',
                ];
            } catch (\Exception $e) {
                $results[$type] = [
                    'connected' => false,
                    'message' => $e->getMessage(),
                ];

                Log::error("SAP {$type} connection test failed", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}


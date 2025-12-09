<?php

namespace App\Modules\ERPIntegration\Jobs;

use App\Modules\ERPIntegration\Connectors\SapConnectorInterface;
use App\Modules\ERPIntegration\Services\YamlMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $orderId,
        public string $connectorType = 'odata', // odata, rfc, idoc
        public string $mappingFile = 'order-to-sap.yaml'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        SapConnectorInterface $sapConnector,
        YamlMappingService $mappingService
    ): void {
        try {
            Log::info('Starting order sync to SAP', [
                'order_id' => $this->orderId,
                'connector_type' => $this->connectorType,
                'mapping_file' => $this->mappingFile,
            ]);

            // Fetch order data (in real implementation, get from database)
            $orderData = $this->getOrderData($this->orderId);

            // Transform order data using YAML mapping
            $sapData = $mappingService->transform($orderData, $this->mappingFile);

            // Send to SAP based on connector type
            $result = $this->sendToSap($sapConnector, $sapData);

            Log::info('Order synced to SAP successfully', [
                'order_id' => $this->orderId,
                'sap_result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Order sync to SAP failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get order data from database
     */
    protected function getOrderData(string $orderId): array
    {
        // In real implementation, fetch from database
        // This is a placeholder structure
        return [
            'id' => $orderId,
            'order_number' => 'ORD-' . $orderId,
            'customer_id' => 'CUST001',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'order_date' => '2024-01-15',
            'total_amount' => 1250.50,
            'currency' => 'USD',
            'items' => [
                [
                    'product_id' => 'PROD001',
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'unit_price' => 500.00,
                    'line_total' => 1000.00,
                ],
                [
                    'product_id' => 'PROD002',
                    'product_name' => 'Product 2',
                    'quantity' => 1,
                    'unit_price' => 250.50,
                    'line_total' => 250.50,
                ],
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ];
    }

    /**
     * Send data to SAP based on connector type
     */
    protected function sendToSap(SapConnectorInterface $connector, array $data): array
    {
        return match ($this->connectorType) {
            'odata' => $this->sendViaOData($connector, $data),
            'rfc', 'bapi' => $this->sendViaBapi($connector, $data),
            'idoc' => $this->sendViaIdoc($connector, $data),
            default => throw new \InvalidArgumentException("Unknown connector type: {$this->connectorType}"),
        };
    }

    /**
     * Send order via OData
     */
    protected function sendViaOData(SapConnectorInterface $connector, array $data): array
    {
        return $connector->odataCreate('SalesOrders', $data);
    }

    /**
     * Send order via BAPI
     */
    protected function sendViaBapi(SapConnectorInterface $connector, array $data): array
    {
        $result = $connector->bapiCall('BAPI_SALESORDER_CREATEFROMDAT2', [
            'ORDER_HEADER_IN' => [
                'DOC_TYPE' => $data['document_type'] ?? 'OR',
                'SALES_ORG' => $data['sales_organization'] ?? '1000',
                'DISTR_CHAN' => $data['distribution_channel'] ?? '10',
                'DIVISION' => $data['division'] ?? '00',
            ],
            'ORDER_ITEMS_IN' => $this->mapOrderItems($data['items'] ?? []),
            'ORDER_PARTNERS' => $this->mapOrderPartners($data),
        ]);

        if ($result['success']) {
            $connector->bapiCommit();
        } else {
            $connector->bapiRollback();
        }

        return $result;
    }

    /**
     * Send order via IDoc
     */
    protected function sendViaIdoc(SapConnectorInterface $connector, array $data): array
    {
        $idocData = [
            'E1EDK01' => [ // Control record
                'CURCY' => $data['currency'] ?? 'USD',
                'HWAER' => $data['currency'] ?? 'USD',
                'WKURS' => '1.0',
                'ZTERM' => $data['payment_terms'] ?? '0001',
            ],
            'E1EDKA1' => [ // Partner data
                'PARVW' => 'AG', // Sold-to party
                'PARTN' => $data['customer_id'] ?? '',
            ],
            'E1EDP01' => $this->mapIdocItems($data['items'] ?? []),
        ];

        return $connector->idocSend('ORDERS05', $idocData);
    }

    /**
     * Map order items for BAPI
     */
    protected function mapOrderItems(array $items): array
    {
        $bapiItems = [];
        $itemNumber = 10;

        foreach ($items as $item) {
            $bapiItems[] = [
                'ITM_NUMBER' => (string) $itemNumber,
                'MATERIAL' => $item['product_id'] ?? $item['Material'] ?? '',
                'PLANT' => $item['plant'] ?? $item['Plant'] ?? '1000',
                'TARGET_QTY' => (string) ($item['quantity'] ?? $item['Quantity'] ?? 0),
                'TARGET_QU' => $item['unit'] ?? $item['Unit'] ?? 'PC',
            ];

            $itemNumber += 10;
        }

        return $bapiItems;
    }

    /**
     * Map order partners for BAPI
     */
    protected function mapOrderPartners(array $data): array
    {
        return [
            [
                'PARTN_ROLE' => 'AG', // Sold-to party
                'PARTN_NUMB' => $data['customer_id'] ?? '',
            ],
        ];
    }

    /**
     * Map items for IDoc
     */
    protected function mapIdocItems(array $items): array
    {
        $idocItems = [];
        $itemNumber = 1;

        foreach ($items as $item) {
            $idocItems[] = [
                'POSEX' => (string) $itemNumber,
                'MATNR' => $item['product_id'] ?? '',
                'MENGE' => (string) ($item['quantity'] ?? 0),
                'MEINS' => $item['unit'] ?? 'PC',
                'NETPR' => (string) ($item['unit_price'] ?? 0),
            ];

            $itemNumber++;
        }

        return $idocItems;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order sync job failed permanently', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}


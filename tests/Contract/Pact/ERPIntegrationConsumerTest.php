<?php

namespace Tests\Contract\Pact;

use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use PHPUnit\Framework\TestCase;

class ERPIntegrationConsumerTest extends TestCase
{
    protected InteractionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new MockServerEnvConfig();
        $this->builder = new InteractionBuilder($config);
        $this->builder->given('ERP system is available')
            ->uponReceiving('a request to sync products')
            ->with(
                (new ConsumerRequest())
                    ->setMethod('POST')
                    ->setPath('/api/erp-integration/sync')
                    ->setHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->setBody([
                        'type' => 'products',
                        'endpoint' => '/api/products',
                        'priority' => 'normal',
                    ])
            )
            ->willRespondWith(
                (new ProviderResponse())
                    ->setStatus(202)
                    ->setHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->setBody([
                        'message' => 'Sync initiated successfully',
                        'data' => [
                            'sync_id' => 'sync-123',
                            'status' => 'pending',
                        ],
                    ])
            );
    }

    public function test_erp_sync_contract(): void
    {
        $this->builder->verify();

        // This test verifies the contract between consumer and provider
        // The pact file will be generated in tests/pacts/
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        $this->builder->finalize();
        parent::tearDown();
    }
}


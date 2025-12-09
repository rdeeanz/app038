<?php

namespace Tests\Contract\Pact;

use PhpPact\Provider\Verifier;
use PhpPact\Standalone\ProviderVerifier\Model\VerifierConfig;
use PHPUnit\Framework\TestCase;

class ERPIntegrationProviderTest extends TestCase
{
    protected Verifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new VerifierConfig();
        $config->setProviderName('ERPIntegrationAPI')
            ->setProviderBaseUrl('http://localhost:8000')
            ->setProviderVersion('1.0.0')
            ->setPublishResults(true)
            ->setBrokerUri('http://localhost:9292')
            ->setBrokerUsername('pact')
            ->setBrokerPassword('pact');

        $this->verifier = new Verifier($config);
    }

    public function test_verify_contracts(): void
    {
        $this->verifier->verifyFiles([
            __DIR__ . '/../../pacts/app038-erpintegrationapi.json',
        ]);

        $this->assertTrue(true);
    }
}


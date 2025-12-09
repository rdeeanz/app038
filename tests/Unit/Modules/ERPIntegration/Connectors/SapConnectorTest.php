<?php

namespace Tests\Unit\Modules\ERPIntegration\Connectors;

use App\Modules\ERPIntegration\Connectors\SapConnectorInterface;
use App\Modules\ERPIntegration\Services\SapConnectorFactory;
use Tests\TestCase;

class SapConnectorTest extends TestCase
{
    public function test_can_create_odata_connector(): void
    {
        $connector = SapConnectorFactory::create('odata');

        $this->assertInstanceOf(SapConnectorInterface::class, $connector);
    }

    public function test_can_create_rfc_bapi_connector(): void
    {
        $connector = SapConnectorFactory::create('rfc-bapi');

        $this->assertInstanceOf(SapConnectorInterface::class, $connector);
    }

    public function test_can_create_idoc_connector(): void
    {
        $connector = SapConnectorFactory::create('idoc');

        $this->assertInstanceOf(SapConnectorInterface::class, $connector);
    }

    public function test_factory_throws_exception_for_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SapConnectorFactory::create('invalid-type');
    }
}


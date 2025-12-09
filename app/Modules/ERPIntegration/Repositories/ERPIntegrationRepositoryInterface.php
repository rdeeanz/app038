<?php

namespace App\Modules\ERPIntegration\Repositories;

interface ERPIntegrationRepositoryInterface
{
    /**
     * List all integrations
     */
    public function list(array $filters = []): array;

    /**
     * Test connection to ERP system
     */
    public function testConnection(): bool;

    /**
     * Fetch data from ERP system
     */
    public function fetchData(string $endpoint, array $params = []): array;

    /**
     * Send data to ERP system
     */
    public function sendData(string $endpoint, array $data): bool;
}


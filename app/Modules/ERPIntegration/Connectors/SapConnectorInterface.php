<?php

namespace App\Modules\ERPIntegration\Connectors;

interface SapConnectorInterface
{
    /**
     * Test connection to SAP system
     */
    public function testConnection(): bool;

    /**
     * OData Methods
     */

    /**
     * Execute OData query
     *
     * @param string $entitySet Entity set name (e.g., 'SalesOrders')
     * @param array $filters OData filter parameters
     * @param array $options Additional options (select, expand, orderby, etc.)
     * @return array
     */
    public function odataQuery(string $entitySet, array $filters = [], array $options = []): array;

    /**
     * Create entity via OData
     *
     * @param string $entitySet Entity set name
     * @param array $data Entity data
     * @return array Created entity with response
     */
    public function odataCreate(string $entitySet, array $data): array;

    /**
     * Update entity via OData
     *
     * @param string $entitySet Entity set name
     * @param string $key Entity key
     * @param array $data Entity data
     * @return array Updated entity
     */
    public function odataUpdate(string $entitySet, string $key, array $data): array;

    /**
     * Delete entity via OData
     *
     * @param string $entitySet Entity set name
     * @param string $key Entity key
     * @return bool Success status
     */
    public function odataDelete(string $entitySet, string $key): bool;

    /**
     * RFC/BAPI Methods
     */

    /**
     * Call RFC function
     *
     * @param string $functionName RFC function name
     * @param array $parameters Input parameters
     * @return array Function result
     */
    public function rfcCall(string $functionName, array $parameters = []): array;

    /**
     * Call BAPI function
     *
     * @param string $bapiName BAPI name (e.g., 'BAPI_SALESORDER_CREATEFROMDAT2')
     * @param array $parameters Input parameters
     * @return array BAPI result with return messages
     */
    public function bapiCall(string $bapiName, array $parameters = []): array;

    /**
     * Commit BAPI transaction
     *
     * @return array Commit result
     */
    public function bapiCommit(): array;

    /**
     * Rollback BAPI transaction
     *
     * @return bool Success status
     */
    public function bapiRollback(): bool;

    /**
     * IDoc Methods
     */

    /**
     * Send IDoc to SAP
     *
     * @param string $idocType IDoc type (e.g., 'ORDERS05')
     * @param array $idocData IDoc data structure
     * @return array IDoc response with IDoc number
     */
    public function idocSend(string $idocType, array $idocData): array;

    /**
     * Receive IDoc from SAP
     *
     * @param string $idocNumber IDoc number
     * @return array IDoc data
     */
    public function idocReceive(string $idocNumber): array;

    /**
     * Get IDoc status
     *
     * @param string $idocNumber IDoc number
     * @return array IDoc status information
     */
    public function idocStatus(string $idocNumber): array;
}


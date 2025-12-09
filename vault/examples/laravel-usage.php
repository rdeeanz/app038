<?php

/**
 * Laravel Vault Integration Examples
 * 
 * This file demonstrates how to use VaultService in Laravel
 */

use App\Services\VaultService;

// Get Vault service instance
$vault = app(VaultService::class);

// Example 1: Get a single secret value
$dbPassword = $vault->getSecret('database', 'password');
echo "Database password: {$dbPassword}\n";

// Example 2: Get all secrets from a path
$databaseSecrets = $vault->getSecret('database');
print_r($databaseSecrets);
// Output:
// Array (
//     [username] => app038_user
//     [password] => secure_password
//     [host] => postgres.example.com
// )

// Example 3: Store secrets
$vault->putSecret('api-keys', [
    'stripe' => 'sk_live_...',
    'sap' => 'api_key_...',
    'aws' => 'AKIA...',
]);

// Example 4: Get dynamic database credentials
$dbCreds = $vault->getDatabaseCredentials('app038-readwrite');
// These credentials are automatically rotated by Vault
// Use them to connect to the database

// Example 5: Encrypt sensitive data
$sensitiveData = 'credit-card-number-1234-5678-9012-3456';
$encrypted = $vault->encrypt($sensitiveData);
// Store $encrypted in database

// Example 6: Decrypt data
$decrypted = $vault->decrypt($encrypted);
echo "Decrypted: {$decrypted}\n";

// Example 7: Use in Laravel configuration
// In config/database.php or config/services.php:
$sapApiKey = $vault->getSecret('sap', 'api_key');
config(['services.sap.api_key' => $sapApiKey]);

// Example 8: Use in Service class
class ERPIntegrationService
{
    protected VaultService $vault;

    public function __construct(VaultService $vault)
    {
        $this->vault = $vault;
    }

    public function connectToSAP(): void
    {
        $credentials = $this->vault->getSecret('sap/odata');
        
        // Use credentials to connect
        $client = new SAPClient([
            'base_url' => $credentials['base_url'],
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);
    }
}

// Example 9: Cache secrets (automatic with VaultService)
// Secrets are automatically cached for 1 hour (configurable)
$secret = $vault->getSecret('api-keys', 'stripe');
// First call: fetches from Vault
// Subsequent calls: uses cache

// Example 10: Delete secret
$vault->deleteSecret('old-api-keys');

// Example 11: Renew token (if using token auth)
$vault->renewToken();

// Example 12: Use in .env file (via VaultServiceProvider)
// VaultServiceProvider automatically loads database credentials
// when vault.database.enabled is true in config/vault.php


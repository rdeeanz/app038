<?php

namespace App\Console\Commands;

use App\Services\VaultService;
use Illuminate\Console\Command;
use Exception;

class VaultTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vault:test
                            {--secret=test : Secret path to test}
                            {--key= : Specific key to retrieve}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Vault connection and retrieve secrets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $vault = app(VaultService::class);

            $this->info('Testing Vault connection...');

            // Test secret retrieval
            $secretPath = $this->option('secret');
            $key = $this->option('key');

            if ($key) {
                $value = $vault->getSecret($secretPath, $key);
                $this->info("Secret '{$secretPath}' key '{$key}': {$value}");
            } else {
                $secrets = $vault->getSecret($secretPath);
                $this->info("Secret '{$secretPath}':");
                $this->table(
                    ['Key', 'Value'],
                    collect($secrets)->map(fn ($value, $key) => [$key, $this->maskValue($value)])->toArray()
                );
            }

            // Test database credentials
            $this->info("\nTesting database credentials...");
            try {
                $dbCreds = $vault->getDatabaseCredentials();
                $this->info("Database credentials retrieved successfully");
                $this->table(
                    ['Key', 'Value'],
                    [
                        ['username', $dbCreds['username'] ?? 'N/A'],
                        ['password', $this->maskValue($dbCreds['password'] ?? 'N/A')],
                    ]
                );
            } catch (Exception $e) {
                $this->warn("Database credentials not available: " . $e->getMessage());
            }

            // Test encryption
            $this->info("\nTesting encryption...");
            $plaintext = 'test-data-' . time();
            $encrypted = $vault->encrypt($plaintext);
            $this->info("Encrypted: {$encrypted}");

            $decrypted = $vault->decrypt($encrypted);
            $this->info("Decrypted: {$decrypted}");

            if ($plaintext === $decrypted) {
                $this->info('✓ Encryption/Decryption test passed');
            } else {
                $this->error('✗ Encryption/Decryption test failed');
                return Command::FAILURE;
            }

            $this->info("\n✓ Vault connection test successful!");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('✗ Vault test failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Mask sensitive values for display
     */
    protected function maskValue(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }
}


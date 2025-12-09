<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VaultService
{
    protected string $address;
    protected ?string $token = null;
    protected string $kvPath;
    protected int $timeout;
    protected bool $cacheEnabled;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->address = config('vault.address');
        $this->kvPath = config('vault.secrets.kv_path');
        $this->timeout = config('vault.timeout');
        $this->cacheEnabled = config('vault.cache.enabled');
        $this->cacheTtl = config('vault.cache.ttl');

        $this->authenticate();
    }

    /**
     * Authenticate with Vault
     */
    protected function authenticate(): void
    {
        $authMethod = config('vault.auth_method');

        switch ($authMethod) {
            case 'kubernetes':
                $this->authenticateKubernetes();
                break;
            case 'approle':
                $this->authenticateAppRole();
                break;
            case 'token':
            default:
                $this->token = config('vault.token');
                break;
        }

        if (empty($this->token)) {
            throw new Exception('Failed to authenticate with Vault');
        }
    }

    /**
     * Authenticate using Kubernetes service account
     */
    protected function authenticateKubernetes(): void
    {
        $k8sConfig = config('vault.kubernetes');
        $tokenPath = $k8sConfig['service_account_token_path'];

        if (!file_exists($tokenPath)) {
            throw new Exception("Kubernetes service account token not found at {$tokenPath}");
        }

        $jwt = file_get_contents($tokenPath);

        $response = Http::timeout($this->timeout)
            ->post("{$this->address}/v1/auth/{$k8sConfig['mount_path']}/login", [
                'role' => $k8sConfig['role'],
                'jwt' => $jwt,
            ]);

        if ($response->successful()) {
            $this->token = $response->json('auth.client_token');
        } else {
            throw new Exception('Kubernetes authentication failed: ' . $response->body());
        }
    }

    /**
     * Authenticate using AppRole
     */
    protected function authenticateAppRole(): void
    {
        $approleConfig = config('vault.approle');

        $response = Http::timeout($this->timeout)
            ->post("{$this->address}/v1/auth/{$approleConfig['mount_path']}/login", [
                'role_id' => $approleConfig['role_id'],
                'secret_id' => $approleConfig['secret_id'],
            ]);

        if ($response->successful()) {
            $this->token = $response->json('auth.client_token');
        } else {
            throw new Exception('AppRole authentication failed: ' . $response->body());
        }
    }

    /**
     * Get secret from Vault
     */
    public function getSecret(string $path, ?string $key = null): mixed
    {
        $cacheKey = "vault:secret:{$path}";

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);
            return $key ? ($data[$key] ?? null) : $data;
        }

        $fullPath = "{$this->kvPath}/{$path}";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->get("{$this->address}/v1/{$fullPath}");

        if (!$response->successful()) {
            Log::error("Failed to read secret from Vault: {$path}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception("Failed to read secret from Vault: {$path}");
        }

        $data = $response->json('data.data', []);

        if ($this->cacheEnabled) {
            Cache::put($cacheKey, $data, $this->cacheTtl);
        }

        return $key ? ($data[$key] ?? null) : $data;
    }

    /**
     * Write secret to Vault
     */
    public function putSecret(string $path, array $data): bool
    {
        $fullPath = "{$this->kvPath}/{$path}";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->post("{$this->address}/v1/{$fullPath}", [
                'data' => $data,
            ]);

        if (!$response->successful()) {
            Log::error("Failed to write secret to Vault: {$path}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        // Invalidate cache
        if ($this->cacheEnabled) {
            Cache::forget("vault:secret:{$path}");
        }

        return true;
    }

    /**
     * Delete secret from Vault
     */
    public function deleteSecret(string $path): bool
    {
        $fullPath = "{$this->kvPath}/{$path}";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->delete("{$this->address}/v1/{$fullPath}");

        if (!$response->successful()) {
            Log::error("Failed to delete secret from Vault: {$path}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        // Invalidate cache
        if ($this->cacheEnabled) {
            Cache::forget("vault:secret:{$path}");
        }

        return true;
    }

    /**
     * Get database credentials from Vault
     */
    public function getDatabaseCredentials(string $role = 'app038-readwrite'): array
    {
        $cacheKey = "vault:db:{$role}";

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $dbPath = config('vault.secrets.database_path');
        $dbPath = str_replace('app038-readwrite', $role, $dbPath);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->get("{$this->address}/v1/{$dbPath}");

        if (!$response->successful()) {
            throw new Exception("Failed to get database credentials: {$role}");
        }

        $data = $response->json('data', []);

        if ($this->cacheEnabled) {
            // Cache for shorter time (credentials have TTL)
            Cache::put($cacheKey, $data, 3600); // 1 hour
        }

        return $data;
    }

    /**
     * Encrypt data using Transit engine
     */
    public function encrypt(string $plaintext): string
    {
        $transitPath = config('vault.secrets.transit_path');
        $key = config('vault.secrets.transit_key');

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->post("{$this->address}/v1/{$transitPath}/encrypt/{$key}", [
                'plaintext' => base64_encode($plaintext),
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to encrypt data');
        }

        return $response->json('data.ciphertext');
    }

    /**
     * Decrypt data using Transit engine
     */
    public function decrypt(string $ciphertext): string
    {
        $transitPath = config('vault.secrets.transit_path');
        $key = config('vault.secrets.transit_key');

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->post("{$this->address}/v1/{$transitPath}/decrypt/{$key}", [
                'ciphertext' => $ciphertext,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to decrypt data');
        }

        return base64_decode($response->json('data.plaintext'));
    }

    /**
     * Renew token
     */
    public function renewToken(): bool
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-Vault-Token' => $this->token,
            ])
            ->post("{$this->address}/v1/auth/token/renew-self");

        return $response->successful();
    }
}


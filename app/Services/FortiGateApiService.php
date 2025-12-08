<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FortiGateApiService
{
    protected string $host;
    protected string $apiKey;
    protected int $timeout;

    /**
     * Initialize the FortiGate API service.
     *
     * @param string $host FortiGate hostname or IP
     * @param string $apiKey API key for authentication
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(string $host, string $apiKey, int $timeout = 10)
    {
        $this->host = rtrim($host, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Fetch current WiFi clients from FortiGate.
     * Polls the /api/v2/monitor/wifi/client endpoint.
     *
     * @return array|null Array of client data or null on failure
     */
    public function getWifiClients(): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->accept('application/json')
                ->get("{$this->host}/api/v2/monitor/wifi/client");

            if (!$response->successful()) {
                Log::warning('FortiGate API request failed', [
                    'host' => $this->host,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            // FortiGate typically returns data in 'results' key
            return $data['results'] ?? $data;

        } catch (\Exception $e) {
            Log::error('FortiGate API exception', [
                'host' => $this->host,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Test the connection to FortiGate API.
     *
     * @return bool True if connection successful
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->accept('application/json')
                ->get("{$this->host}/api/v2/monitor/system/status");

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('FortiGate connection test failed', [
                'host' => $this->host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create an instance from an array of credentials.
     *
     * @param array $credentials ['host' => '...', 'api_key' => '...']
     * @return static
     */
    public static function fromCredentials(array $credentials): self
    {
        return new self(
            $credentials['host'] ?? '',
            $credentials['api_key'] ?? '',
            $credentials['timeout'] ?? 10
        );
    }
}

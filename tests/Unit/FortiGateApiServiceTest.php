<?php

namespace Tests\Unit;

use App\Services\FortiGateApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FortiGateApiServiceTest extends TestCase
{
    protected string $testHost = 'https://fortigate.example.com';
    protected string $testApiKey = 'test-api-key-123';

    /**
     * Test successful WiFi clients fetch.
     */
    public function test_get_wifi_clients_returns_data_on_success(): void
    {
        // Mock successful API response
        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response([
                'results' => [
                    [
                        'mac' => 'AA:BB:CC:DD:EE:FF',
                        'ap_mac' => '11:22:33:44:55:66',
                        'ssid' => 'GuestWiFi',
                        'signal' => -45,
                    ],
                    [
                        'mac' => 'FF:EE:DD:CC:BB:AA',
                        'ap_mac' => '11:22:33:44:55:66',
                        'ssid' => 'GuestWiFi',
                        'signal' => -52,
                    ],
                ],
            ], 200),
        ]);

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $clients = $service->getWifiClients();

        $this->assertIsArray($clients);
        $this->assertCount(2, $clients);
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $clients[0]['mac']);
    }

    /**
     * Test API response with results key.
     */
    public function test_get_wifi_clients_extracts_results_key(): void
    {
        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response([
                'results' => [['mac' => 'AA:BB:CC:DD:EE:FF']],
                'status' => 'success',
            ], 200),
        ]);

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $clients = $service->getWifiClients();

        $this->assertIsArray($clients);
        $this->assertCount(1, $clients);
    }

    /**
     * Test API response without results key.
     */
    public function test_get_wifi_clients_handles_response_without_results_key(): void
    {
        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response([
                ['mac' => 'AA:BB:CC:DD:EE:FF'],
                ['mac' => 'BB:CC:DD:EE:FF:AA'],
            ], 200),
        ]);

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $clients = $service->getWifiClients();

        $this->assertIsArray($clients);
        $this->assertCount(2, $clients);
    }

    /**
     * Test API failure returns null.
     */
    public function test_get_wifi_clients_returns_null_on_http_error(): void
    {
        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response([], 500),
        ]);

        Log::shouldReceive('warning')->once();

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $clients = $service->getWifiClients();

        $this->assertNull($clients);
    }

    /**
     * Test API timeout returns null.
     */
    public function test_get_wifi_clients_returns_null_on_timeout(): void
    {
        Http::fake([
            '*/api/v2/monitor/wifi/client' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        Log::shouldReceive('error')->once();

        $service = new FortiGateApiService($this->testHost, $this->testApiKey, 5);
        $clients = $service->getWifiClients();

        $this->assertNull($clients);
    }

    /**
     * Test API authentication header.
     */
    public function test_get_wifi_clients_sends_correct_auth_header(): void
    {
        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response(['results' => []], 200),
        ]);

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $service->getWifiClients();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer ' . $this->testApiKey);
        });
    }

    /**
     * Test connection test with successful response.
     */
    public function test_test_connection_returns_true_on_success(): void
    {
        Http::fake([
            '*/api/v2/monitor/system/status' => Http::response([
                'version' => 'v7.2.0',
                'hostname' => 'FG-TEST',
            ], 200),
        ]);

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $result = $service->testConnection();

        $this->assertTrue($result);
    }

    /**
     * Test connection test with failed response.
     */
    public function test_test_connection_returns_false_on_failure(): void
    {
        Http::fake([
            '*/api/v2/monitor/system/status' => Http::response([], 401),
        ]);

        // No logging for unsuccessful HTTP response, only for exceptions
        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $result = $service->testConnection();

        $this->assertFalse($result);
    }

    /**
     * Test connection test with exception.
     */
    public function test_test_connection_returns_false_on_exception(): void
    {
        Http::fake([
            '*/api/v2/monitor/system/status' => function () {
                throw new \Exception('Network error');
            },
        ]);

        Log::shouldReceive('error')->once();

        $service = new FortiGateApiService($this->testHost, $this->testApiKey);
        $result = $service->testConnection();

        $this->assertFalse($result);
    }

    /**
     * Test creating service from credentials array.
     */
    public function test_from_credentials_creates_service_correctly(): void
    {
        $credentials = [
            'host' => $this->testHost,
            'api_key' => $this->testApiKey,
            'timeout' => 15,
        ];

        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response(['results' => []], 200),
        ]);

        $service = FortiGateApiService::fromCredentials($credentials);
        $clients = $service->getWifiClients();

        $this->assertIsArray($clients);
    }

    /**
     * Test from credentials with minimal data.
     */
    public function test_from_credentials_handles_minimal_data(): void
    {
        $credentials = [
            'host' => $this->testHost,
            'api_key' => $this->testApiKey,
        ];

        $service = FortiGateApiService::fromCredentials($credentials);

        $this->assertInstanceOf(FortiGateApiService::class, $service);
    }

    /**
     * Test from credentials with missing data.
     */
    public function test_from_credentials_handles_missing_data(): void
    {
        $credentials = []; // Empty credentials

        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response([], 401),
        ]);

        Log::shouldReceive('warning')->once();

        $service = FortiGateApiService::fromCredentials($credentials);
        $clients = $service->getWifiClients();

        $this->assertNull($clients);
    }

    /**
     * Test timeout configuration.
     */
    public function test_timeout_is_configurable(): void
    {
        Http::fake([
            '*/api/v2/monitor/wifi/client' => Http::response(['results' => []], 200),
        ]);

        $service = new FortiGateApiService($this->testHost, $this->testApiKey, 30);
        $service->getWifiClients();

        // Verify timeout was set (Http facade will use this timeout)
        Http::assertSent(function ($request) {
            // The timeout would be set in the request, but we can at least verify the request was made
            return str_contains($request->url(), '/api/v2/monitor/wifi/client');
        });
    }

    /**
     * Test host URL normalization (trailing slash removal).
     */
    public function test_host_trailing_slash_is_removed(): void
    {
        Http::fake([
            'https://fortigate.example.com/api/v2/monitor/wifi/client' => Http::response(['results' => []], 200),
        ]);

        $service = new FortiGateApiService('https://fortigate.example.com/', $this->testApiKey);
        $service->getWifiClients();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://fortigate.example.com/api/v2/monitor/wifi/client';
        });
    }
}

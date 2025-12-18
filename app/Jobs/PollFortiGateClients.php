<?php

namespace App\Jobs;

use App\Models\AccessPoint;
use App\Models\ClientSession;
use App\Models\Tenant;
use App\Services\FortiGateApiService;
use App\Services\MacAddressHasher;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PollFortiGateClients implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    protected Tenant $tenant;

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     * Polls FortiGate devices for current WiFi clients and updates session records.
     */
    public function handle(): void
    {
        // Check if tenant is active
        if (!$this->tenant->isActive()) {
            Log::info('Skipping poll for inactive tenant', ['tenant_id' => $this->tenant->id]);
            return;
        }

        $credentials = $this->tenant->fortigate_credentials;

        if (empty($credentials)) {
            Log::warning('No FortiGate credentials configured', ['tenant_id' => $this->tenant->id]);
            return;
        }

        // Initialize tenant context
        tenancy()->initialize($this->tenant);

        try {
            foreach ($credentials as $index => $credential) {
                $this->pollFortiGateDevice($credential, $index);
            }
        } finally {
            // Always end tenancy context
            tenancy()->end();
        }
    }

    /**
     * Poll a single FortiGate device for WiFi clients.
     *
     * @param array $credential FortiGate device credentials
     * @param int $deviceIndex Index of the device (for logging)
     */
    protected function pollFortiGateDevice(array $credential, int $deviceIndex): void
    {
        $host = $credential['host'] ?? null;
        $apiKey = $credential['api_key'] ?? null;

        if (!$host || !$apiKey) {
            Log::warning('Invalid FortiGate credentials', [
                'tenant_id' => $this->tenant->id,
                'device_index' => $deviceIndex,
            ]);
            return;
        }

        Log::info('Polling FortiGate device', [
            'tenant_id' => $this->tenant->id,
            'host' => $host,
            'device_index' => $deviceIndex,
        ]);

        $service = FortiGateApiService::fromCredentials($credential);
        $clients = $service->getWifiClients();

        if ($clients === null) {
            Log::error('Failed to fetch WiFi clients', [
                'tenant_id' => $this->tenant->id,
                'host' => $host,
            ]);
            return;
        }

        $this->processWifiClients($clients, $deviceIndex);
    }

    /**
     * Process WiFi clients and update session records.
     *
     * @param array $clients Array of client data from FortiGate
     * @param int $deviceIndex Device index for cache key
     */
    protected function processWifiClients(array $clients, int $deviceIndex): void
    {
        $now = Carbon::now();
        $cacheKey = "fortigate_clients_{$this->tenant->id}_{$deviceIndex}";

        // Get previous poll data from cache
        $previousClients = Cache::get($cacheKey, []);
        $currentMacs = [];

        foreach ($clients as $client) {
            $macAddress = $client['mac'] ?? $client['mac_address'] ?? null;
            $apMac = $client['ap_mac'] ?? $client['vap'] ?? null;
            $signalStrength = $client['signal'] ?? $client['rssi'] ?? null;

            if (!$macAddress || !MacAddressHasher::isValid($macAddress)) {
                continue;
            }

            $macHash = MacAddressHasher::hash($macAddress);
            $currentMacs[] = $macHash;

            // Find or create access point
            $accessPoint = $this->findOrCreateAccessPoint($apMac, $client);

            if (!$accessPoint) {
                continue;
            }

            // Check if this is a new connection
            if (!in_array($macHash, $previousClients)) {
                $this->handleNewConnection($macHash, $accessPoint, $signalStrength, $now);
            } else {
                $this->handleExistingConnection($macHash, $accessPoint, $signalStrength, $now);
            }
        }

        // Detect disconnections
        $disconnectedMacs = array_diff($previousClients, $currentMacs);
        foreach ($disconnectedMacs as $macHash) {
            $this->handleDisconnection($macHash, $now);
        }

        // Store current clients in cache for next poll
        Cache::put($cacheKey, $currentMacs, 300); // 5 minutes TTL
    }

    /**
     * Find or create an access point record.
     *
     * @param string|null $apMac Access point MAC address
     * @param array $clientData Client data from FortiGate
     * @return AccessPoint|null
     */
    protected function findOrCreateAccessPoint(?string $apMac, array $clientData): ?AccessPoint
    {
        if (!$apMac || !MacAddressHasher::isValid($apMac)) {
            // If no valid AP MAC, try to find a default AP or return null
            return AccessPoint::first();
        }

        $apName = $clientData['ap_name'] ?? $clientData['ssid'] ?? 'Unknown AP';

        return AccessPoint::firstOrCreate(
            ['mac_address' => MacAddressHasher::normalize($apMac)],
            [
                'ap_name' => $apName,
                'status' => 'online',
            ]
        );
    }

    /**
     * Handle a new client connection.
     *
     * @param string $macHash Hashed MAC address
     * @param AccessPoint $accessPoint Access point
     * @param int|null $signalStrength Signal strength
     * @param Carbon $now Current timestamp
     */
    protected function handleNewConnection(
        string $macHash,
        AccessPoint $accessPoint,
        ?int $signalStrength,
        Carbon $now
    ): void {
        // Check if this is a returning visitor (seen in last 30 days)
        $previousSession = ClientSession::where('mac_address_hash', $macHash)
            ->where('connected_at', '>=', $now->copy()->subDays(30))
            ->first();

        $firstSeenAt = $previousSession ? $previousSession->first_seen_at : $now;
        $isReturning = (bool) $previousSession;

        ClientSession::create([
            'mac_address_hash' => $macHash,
            'access_point_id' => $accessPoint->id,
            'connected_at' => $now,
            'signal_strength' => $signalStrength,
            'is_returning_visitor' => $isReturning,
            'first_seen_at' => $firstSeenAt,
        ]);

        Log::debug('New client connection', [
            'tenant_id' => $this->tenant->id,
            'access_point' => $accessPoint->ap_name,
            'is_returning' => $isReturning,
        ]);
    }

    /**
     * Handle an existing client connection (update if needed).
     *
     * @param string $macHash Hashed MAC address
     * @param AccessPoint $accessPoint Access point
     * @param int|null $signalStrength Signal strength
     * @param Carbon $now Current timestamp
     */
    protected function handleExistingConnection(
        string $macHash,
        AccessPoint $accessPoint,
        ?int $signalStrength,
        Carbon $now
    ): void {
        // Update signal strength if changed significantly
        $activeSession = ClientSession::where('mac_address_hash', $macHash)
            ->whereNull('disconnected_at')
            ->where('access_point_id', $accessPoint->id)
            ->first();

        if ($activeSession && $signalStrength !== null) {
            $activeSession->update(['signal_strength' => $signalStrength]);
        }
    }

    /**
     * Handle a client disconnection.
     *
     * @param string $macHash Hashed MAC address
     * @param Carbon $now Current timestamp
     */
    protected function handleDisconnection(string $macHash, Carbon $now): void
    {
        $activeSession = ClientSession::where('mac_address_hash', $macHash)
            ->whereNull('disconnected_at')
            ->first();

        if ($activeSession) {
            $duration = $now->diffInSeconds($activeSession->connected_at);

            $activeSession->update([
                'disconnected_at' => $now,
                'session_duration_seconds' => $duration,
            ]);

            Log::debug('Client disconnection', [
                'tenant_id' => $this->tenant->id,
                'duration_seconds' => $duration,
            ]);
        }
    }
}

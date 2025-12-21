#!/bin/bash
# Create Test Tenant with Sample Data
# Run this AFTER setup-local.sh completes successfully

set -e

echo "=========================================="
echo "Creating Test Tenant"
echo "=========================================="
echo ""

# Create tenant via Tinker
echo "📝 Creating tenant 'demo'..."
php artisan tinker --execute="
use App\Models\Tenant;

\$tenant = Tenant::create([
    'id' => 'demo',
    'name' => 'Demo Company',
]);

echo 'Tenant created: ' . \$tenant->id . PHP_EOL;
echo 'Tenant name: ' . \$tenant->name . PHP_EOL;
"

echo ""
echo "✅ Tenant created!"
echo ""
echo "=========================================="
echo "Next: Run tenant migrations"
echo "=========================================="
echo ""

# Run tenant migrations
echo "🗄️  Running tenant migrations..."
php artisan tenants:migrate --tenants=demo

echo ""
echo "✅ Tenant database ready!"
echo ""
echo "=========================================="
echo "Creating Sample Data (Optional)"
echo "=========================================="
echo ""

# Create sample access points and sessions
php artisan tinker --execute="
use App\Models\Tenant;
use App\Models\AccessPoint;
use App\Models\ClientSession;
use Illuminate\Support\Facades\Hash;

\$tenant = Tenant::find('demo');

tenancy()->initialize(\$tenant);

// Create 3 sample access points
\$ap1 = AccessPoint::create([
    'ap_mac_address' => '00:11:22:33:44:55',
    'ap_name' => 'Lobby AP',
    'zone' => 'lobby',
    'location_description' => 'Main Lobby Area',
    'status' => 'online',
]);

\$ap2 = AccessPoint::create([
    'ap_mac_address' => '00:11:22:33:44:66',
    'ap_name' => 'Cafe AP',
    'zone' => 'cafe',
    'location_description' => 'Cafe & Lounge',
    'status' => 'online',
]);

\$ap3 = AccessPoint::create([
    'ap_mac_address' => '00:11:22:33:44:77',
    'ap_name' => 'Conference AP',
    'zone' => 'conference',
    'location_description' => 'Conference Room',
    'status' => 'offline',
]);

echo 'Created ' . AccessPoint::count() . ' access points' . PHP_EOL;

// Create some active client sessions
\$now = now();

ClientSession::create([
    'access_point_id' => \$ap1->id,
    'mac_address_hash' => hash('sha256', 'AA:BB:CC:DD:EE:01' . config('app.key')),
    'connected_at' => \$now->copy()->subMinutes(45),
    'disconnected_at' => null,
    'signal_strength' => -42,
    'ssid' => 'GuestWiFi',
    'is_returning_visitor' => false,
]);

ClientSession::create([
    'access_point_id' => \$ap2->id,
    'mac_address_hash' => hash('sha256', 'AA:BB:CC:DD:EE:02' . config('app.key')),
    'connected_at' => \$now->copy()->subMinutes(20),
    'disconnected_at' => null,
    'signal_strength' => -55,
    'ssid' => 'GuestWiFi',
    'is_returning_visitor' => true,
]);

ClientSession::create([
    'access_point_id' => \$ap1->id,
    'mac_address_hash' => hash('sha256', 'AA:BB:CC:DD:EE:03' . config('app.key')),
    'connected_at' => \$now->copy()->subMinutes(5),
    'disconnected_at' => null,
    'signal_strength' => -48,
    'ssid' => 'GuestWiFi',
    'is_returning_visitor' => false,
]);

// Create some disconnected sessions from earlier today
ClientSession::create([
    'access_point_id' => \$ap2->id,
    'mac_address_hash' => hash('sha256', 'AA:BB:CC:DD:EE:04' . config('app.key')),
    'connected_at' => \$now->copy()->subHours(3),
    'disconnected_at' => \$now->copy()->subHours(2)->subMinutes(30),
    'dwell_time_minutes' => 30,
    'signal_strength' => -50,
    'ssid' => 'GuestWiFi',
    'is_returning_visitor' => true,
]);

ClientSession::create([
    'access_point_id' => \$ap1->id,
    'mac_address_hash' => hash('sha256', 'AA:BB:CC:DD:EE:05' . config('app.key')),
    'connected_at' => \$now->copy()->subHours(4),
    'disconnected_at' => \$now->copy()->subHours(3)->subMinutes(45),
    'dwell_time_minutes' => 15,
    'signal_strength' => -60,
    'ssid' => 'GuestWiFi',
    'is_returning_visitor' => false,
]);

\$activeSessions = ClientSession::active()->count();
\$totalSessions = ClientSession::count();

echo 'Created ' . \$totalSessions . ' client sessions' . PHP_EOL;
echo 'Active sessions: ' . \$activeSessions . PHP_EOL;
echo 'Today visitors: ' . ClientSession::whereDate('connected_at', today())->distinct('mac_address_hash')->count() . PHP_EOL;

tenancy()->end();
"

echo ""
echo "=========================================="
echo "✅ Test Tenant Ready!"
echo "=========================================="
echo ""
echo "Sample data created:"
echo "  - 3 Access Points (2 online, 1 offline)"
echo "  - 3 Active client sessions"
echo "  - 2 Disconnected sessions from today"
echo ""
echo "Next: Start the dev server"
echo "  php artisan serve"
echo ""
echo "Then access: http://localhost:8000"
echo "=========================================="

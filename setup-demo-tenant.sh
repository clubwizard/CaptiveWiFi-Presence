#!/bin/bash
# Complete tenant setup - all in one script

set -e

echo "=========================================="
echo "Setting up Demo Tenant"
echo "=========================================="
echo ""

# Step 1: Create tenant and add domain
echo "📝 Step 1: Creating tenant and domain..."
php artisan tinker --execute="
use App\Models\Tenant;

\$tenant = Tenant::create([
    'id' => 'demo',
    'name' => 'Demo Company'
]);
\$tenant->domains()->create(['domain' => 'demo.localhost']);

echo 'Tenant: ' . \$tenant->id . PHP_EOL;
echo 'Name: ' . \$tenant->name . PHP_EOL;
echo 'Domain: demo.localhost' . PHP_EOL;
"

echo ""
echo "=========================================="

# Step 2: Run tenant migrations
echo "🗄️  Step 2: Running tenant migrations..."
php artisan tenants:migrate --tenants=demo

echo ""
echo "=========================================="

# Step 3: Create sample data
echo "📊 Step 3: Creating sample data..."
php artisan tinker --execute="
use App\Models\Tenant;
use App\Models\AccessPoint;
use App\Models\ClientSession;

\$tenant = Tenant::find('demo');
tenancy()->initialize(\$tenant);

// Create 3 access points
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

// Create active client sessions
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

// Create disconnected sessions
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
\$todayVisitors = ClientSession::whereDate('connected_at', today())->distinct('mac_address_hash')->count();

echo 'Total sessions: ' . \$totalSessions . PHP_EOL;
echo 'Active sessions: ' . \$activeSessions . PHP_EOL;
echo 'Today visitors: ' . \$todayVisitors . PHP_EOL;

tenancy()->end();
"

echo ""
echo "=========================================="
echo "✅ Demo Tenant Ready!"
echo "=========================================="
echo ""
echo "Dashboard Stats:"
echo "  - 3 Access Points (Lobby, Cafe, Conference)"
echo "  - 3 Active client sessions"
echo "  - 5 Total sessions today"
echo ""
echo "Access the dashboard at:"
echo "  http://demo.localhost:8000"
echo ""
echo "=========================================="

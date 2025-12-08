# CaptiveWiFi Analytics Platform

A multi-tenant SaaS analytics platform that polls FortiGate REST APIs to provide hospitality venues with WiFi presence analytics, footfall tracking, and dwell time analysis.

## Technology Stack

- **Framework**: Laravel 11.x
- **Multi-Tenancy**: stancl/tenancy v3.9 (database-per-tenant isolation)
- **Database**: PostgreSQL (separate databases per tenant)
- **Cache/Queue**: Redis
- **Frontend**: Laravel Livewire + Alpine.js (to be implemented)
- **Charts**: ApexCharts (to be implemented)
- **Deployment**: DigitalOcean App Platform ready

## Project Status: Phase 1 Complete ✅

### Completed Features

#### Core Infrastructure
- ✅ Laravel 11.x fresh installation with all dependencies
- ✅ Multi-tenancy package (stancl/tenancy) installed and configured
- ✅ PostgreSQL configuration for landlord database
- ✅ Redis configuration for caching, sessions, and queues
- ✅ Database-per-tenant architecture configured

#### Database Schema

**Landlord Database (Central)**
- `tenants` - Stores tenant information with encrypted FortiGate credentials
- `domains` - Maps subdomains to tenants

**Tenant Databases (Per Venue)**
- `users` - Venue staff with role-based access (admin/viewer)
- `access_points` - FortiGate WiFi access points
- `client_sessions` - WiFi client connection sessions (MAC hashed for GDPR)
- `analytics_snapshots` - Pre-calculated hourly/daily analytics

#### Eloquent Models
- ✅ `Tenant` - Custom tenant model with encrypted credentials
- ✅ `AccessPoint` - Access point management
- ✅ `ClientSession` - Client connection tracking with relationships
- ✅ `AnalyticsSnapshot` - Analytics data storage with scopes
- ✅ `User` - Tenant user with role-based methods

#### Core Services
- ✅ `FortiGateApiService` - REST API client for FortiGate 7.2+
  - WiFi client polling
  - Connection testing
  - Error handling and retry logic
- ✅ `MacAddressHasher` - GDPR-compliant MAC address hashing (SHA256)
- ✅ `AnalyticsCalculator` - Analytics aggregation engine
  - Hourly snapshots
  - Daily snapshots
  - Peak connected count calculation
  - Retention policy enforcement

#### Application Structure
- ✅ Controllers organized by context (Tenant/Landlord)
- ✅ Service layer for business logic
- ✅ Jobs directory prepared for background processing
- ✅ Health check endpoint for DigitalOcean App Platform

#### Configuration
- ✅ PostgreSQL central connection configured
- ✅ Redis tenancy bootstrapper enabled
- ✅ Environment variables documented in .env.example
- ✅ Custom tenant model registered

## Installation

### Prerequisites
- PHP 8.2+
- PostgreSQL 15+
- Redis 7+
- Composer 2.x

### Setup Instructions

1. **Clone the repository**
```bash
git clone <repository-url>
cd CaptiveWiFi-Presence
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update database credentials in .env**
```env
DB_CONNECTION=central
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=captivewifi_landlord
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

5. **Configure Redis**
```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

6. **Run migrations**
```bash
# Create landlord database tables
php artisan migrate

# Tenant migrations will run automatically when tenants are created
```

## Database Architecture

### Multi-Tenancy Model
- **Isolation**: Each tenant gets a separate PostgreSQL database
- **Prefix**: `tenant{uuid}` (e.g., `tenant123e4567-e89b-12d3-a456-426614174000`)
- **Security**: FortiGate credentials encrypted using Laravel's Crypt facade
- **Privacy**: MAC addresses hashed with SHA256 + application key

### Landlord Database

#### Tenants Table
```sql
- id (uuid, primary key)
- name (venue name)
- subdomain (unique subdomain)
- fortigate_credentials (encrypted JSON array)
- plan (free/pro/enterprise)
- status (active/suspended/trial)
- trial_ends_at (timestamp)
- timestamps
```

### Tenant Database

#### Access Points
```sql
- id
- ap_name
- mac_address (unique)
- location_description
- floor
- zone (bar/restaurant/patio)
- status (online/offline/unknown)
- timestamps
```

#### Client Sessions
```sql
- id
- mac_address_hash (SHA256, indexed)
- access_point_id (foreign key)
- connected_at (indexed)
- disconnected_at (indexed)
- session_duration_seconds
- signal_strength
- is_returning_visitor (indexed)
- first_seen_at (indexed)
- timestamps
```

#### Analytics Snapshots
```sql
- id
- snapshot_date (indexed)
- snapshot_hour (0-23, null for daily)
- unique_visitors
- total_sessions
- avg_dwell_time_minutes
- peak_connected_count
- returning_visitor_percentage
- new_visitor_count
- timestamps
- unique(snapshot_date, snapshot_hour)
```

## API Endpoints

### Health Check
```
GET /health
```
Returns system health status including database and Redis connectivity.

## Services Documentation

### FortiGateApiService

Handles communication with FortiGate REST API:

```php
$service = new FortiGateApiService($host, $apiKey, $timeout);

// Get current WiFi clients
$clients = $service->getWifiClients();

// Test connection
$isConnected = $service->testConnection();

// Create from credentials array
$service = FortiGateApiService::fromCredentials([
    'host' => 'https://fortigate.example.com',
    'api_key' => 'your-api-key',
    'timeout' => 10
]);
```

### MacAddressHasher

GDPR-compliant MAC address handling:

```php
// Hash MAC address
$hash = MacAddressHasher::hash('AA:BB:CC:DD:EE:FF');

// Normalize MAC format
$normalized = MacAddressHasher::normalize('aa-bb-cc-dd-ee-ff');

// Validate MAC address
$isValid = MacAddressHasher::isValid('AA:BB:CC:DD:EE:FF');
```

### AnalyticsCalculator

Calculate and store analytics snapshots:

```php
$calculator = new AnalyticsCalculator();

// Calculate hourly snapshot
$snapshot = $calculator->calculateHourlySnapshot(now());

// Calculate daily snapshot
$snapshot = $calculator->calculateDailySnapshot(today());

// Clean up old sessions
$deletedCount = $calculator->cleanupOldSessions(90);
```

## Models

### Tenant Model

```php
$tenant = Tenant::create([
    'name' => 'Acme Hotel',
    'subdomain' => 'acme',
    'fortigate_credentials' => [
        ['host' => 'https://fg1.acme.com', 'api_key' => 'key1'],
        ['host' => 'https://fg2.acme.com', 'api_key' => 'key2'],
    ],
    'plan' => 'pro',
    'status' => 'trial',
    'trial_ends_at' => now()->addDays(30),
]);

// Check status
$tenant->isActive();
$tenant->isOnTrial();
$tenant->trialExpired();
```

### ClientSession Model

```php
// Query active sessions
$active = ClientSession::active()->get();

// Query by date range
$sessions = ClientSession::dateRange($start, $end)->get();

// Get dwell time
$session->dwell_time_minutes; // Accessor
```

### AnalyticsSnapshot Model

```php
// Get daily snapshots
$daily = AnalyticsSnapshot::daily()->get();

// Get hourly snapshots
$hourly = AnalyticsSnapshot::hourly()->get();

// Query specific date
$snapshots = AnalyticsSnapshot::forDate('2025-12-08')->get();

// Query date range
$range = AnalyticsSnapshot::dateRange($start, $end)->get();
```

## Security & Privacy

### GDPR Compliance
- ✅ MAC addresses are NEVER stored in plain text
- ✅ All MAC addresses hashed using SHA256 + application key
- ✅ Data retention policies enforced (default: 90 days)
- ✅ Tenant data completely isolated by database
- ✅ FortiGate credentials encrypted at rest

### Best Practices Implemented
- PSR-12 coding standards
- Type hints and return types throughout
- Comprehensive inline documentation
- Service layer for business logic separation
- Eloquent scopes for reusable queries
- Database transactions where appropriate

## Next Steps (Phase 2-5)

### Phase 2: FortiGate Integration
- [ ] Create PollFortiGateClients job
- [ ] Implement session detection logic
- [ ] Handle connection/disconnection events
- [ ] Queue job scheduling

### Phase 3: Analytics Engine
- [ ] Create CalculateAnalytics job
- [ ] Create GenerateDailyReport job
- [ ] Implement data aggregation
- [ ] Add retention policy enforcement

### Phase 4: Dashboard UI
- [ ] Livewire components for real-time dashboard
- [ ] ApexCharts integration
- [ ] Access point management UI
- [ ] Analytics visualizations

### Phase 5: Production Ready
- [ ] Authentication scaffolding
- [ ] API endpoints for CaptiveWiFi.com integration
- [ ] Comprehensive testing suite
- [ ] DigitalOcean deployment configuration
- [ ] Documentation and user guides

## Development Commands

```bash
# Create new tenant manually (via Tinker)
php artisan tinker
$tenant = \App\Models\Tenant::create([...]);

# Run migrations for a specific tenant
php artisan tenants:migrate --tenants=<tenant-id>

# Run migrations for all tenants
php artisan tenants:migrate

# Clear tenant cache
php artisan tenants:run cache:clear

# List all tenants
php artisan tenants:list
```

## Testing

Testing suite will be implemented in Phase 5:
- Unit tests for services (MAC hashing, analytics calculations)
- Feature tests for tenant isolation
- Integration tests for FortiGate API
- Browser tests for dashboard

## License

Proprietary - CaptiveWiFi.com

## Contributors

Built with Claude Code for CaptiveWiFi.com ecosystem.

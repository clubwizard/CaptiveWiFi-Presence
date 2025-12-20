<?php

namespace Tests\Unit;

use App\Models\AccessPoint;
use App\Models\ClientSession;
use App\Models\AnalyticsSnapshot;
use App\Services\AnalyticsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AnalyticsCalculator();

        // Run migrations for testing
        $this->artisan('migrate');
    }

    /**
     * Test hourly snapshot calculation with no sessions.
     */
    public function test_hourly_snapshot_with_no_sessions_creates_empty_snapshot(): void
    {
        $dateTime = Carbon::parse('2025-12-08 14:00:00');

        $snapshot = $this->calculator->calculateHourlySnapshot($dateTime);

        $this->assertNotNull($snapshot);
        $this->assertEquals('2025-12-08', $snapshot->snapshot_date->toDateString());
        $this->assertEquals(14, $snapshot->snapshot_hour);
        $this->assertEquals(0, $snapshot->unique_visitors);
        $this->assertEquals(0, $snapshot->total_sessions);
        $this->assertEquals(0, $snapshot->avg_dwell_time_minutes);
    }

    /**
     * Test hourly snapshot calculation with sessions.
     */
    public function test_hourly_snapshot_calculates_correctly_with_sessions(): void
    {
        $ap = AccessPoint::create([
            'ap_name' => 'Test AP',
            'mac_address' => 'aabbccddeeff',
            'status' => 'online',
        ]);

        $dateTime = Carbon::parse('2025-12-08 14:00:00');

        // Create 3 sessions within the hour
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac1'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(5),
            'disconnected_at' => $dateTime->copy()->addMinutes(35),
            'session_duration_seconds' => 1800, // 30 minutes
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy()->addMinutes(5),
        ]);

        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac2'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(10),
            'disconnected_at' => $dateTime->copy()->addMinutes(40),
            'session_duration_seconds' => 1800, // 30 minutes
            'is_returning_visitor' => true,
            'first_seen_at' => $dateTime->copy()->subDays(5),
        ]);

        // Same MAC as first (not unique)
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac1'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(45),
            'disconnected_at' => $dateTime->copy()->addMinutes(55),
            'session_duration_seconds' => 600, // 10 minutes
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy()->addMinutes(5),
        ]);

        $snapshot = $this->calculator->calculateHourlySnapshot($dateTime);

        $this->assertNotNull($snapshot);
        $this->assertEquals(2, $snapshot->unique_visitors); // mac1 and mac2
        $this->assertEquals(3, $snapshot->total_sessions);
        $this->assertEquals(23.33, $snapshot->avg_dwell_time_minutes); // (30+30+10)/3 = 23.33
        $this->assertEquals(1, $snapshot->new_visitor_count); // Only mac1 is new
    }

    /**
     * Test daily snapshot calculation.
     */
    public function test_daily_snapshot_calculates_for_entire_day(): void
    {
        $ap = AccessPoint::create([
            'ap_name' => 'Test AP',
            'mac_address' => 'aabbccddeeff',
            'status' => 'online',
        ]);

        $date = Carbon::parse('2025-12-08');

        // Create sessions throughout the day
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac1'),
            'access_point_id' => $ap->id,
            'connected_at' => $date->copy()->setTime(9, 0),
            'disconnected_at' => $date->copy()->setTime(9, 30),
            'session_duration_seconds' => 1800,
            'is_returning_visitor' => false,
            'first_seen_at' => $date->copy()->setTime(9, 0),
        ]);

        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac2'),
            'access_point_id' => $ap->id,
            'connected_at' => $date->copy()->setTime(14, 0),
            'disconnected_at' => $date->copy()->setTime(15, 0),
            'session_duration_seconds' => 3600,
            'is_returning_visitor' => true,
            'first_seen_at' => $date->copy()->subDays(10),
        ]);

        $snapshot = $this->calculator->calculateDailySnapshot($date);

        $this->assertNotNull($snapshot);
        $this->assertEquals('2025-12-08', $snapshot->snapshot_date->toDateString());
        $this->assertNull($snapshot->snapshot_hour); // Daily snapshots have null hour
        $this->assertEquals(2, $snapshot->unique_visitors);
        $this->assertEquals(2, $snapshot->total_sessions);
    }

    /**
     * Test returning visitor percentage calculation.
     */
    public function test_returning_visitor_percentage_calculated_correctly(): void
    {
        $ap = AccessPoint::create([
            'ap_name' => 'Test AP',
            'mac_address' => 'aabbccddeeff',
            'status' => 'online',
        ]);

        $dateTime = Carbon::parse('2025-12-08 14:00:00');

        // 2 new visitors
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'new1'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy(),
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy(),
        ]);

        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'new2'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(10),
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy()->addMinutes(10),
        ]);

        // 1 returning visitor
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'returning1'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(20),
            'is_returning_visitor' => true,
            'first_seen_at' => $dateTime->copy()->subDays(5),
        ]);

        $snapshot = $this->calculator->calculateHourlySnapshot($dateTime);

        // 1 returning out of 3 unique = 33.33%
        $this->assertEquals(33.33, $snapshot->returning_visitor_percentage);
    }

    /**
     * Test that snapshots are updated if calculated again.
     */
    public function test_snapshot_updates_on_recalculation(): void
    {
        $ap = AccessPoint::create([
            'ap_name' => 'Test AP',
            'mac_address' => 'aabbccddeeff',
            'status' => 'online',
        ]);

        $dateTime = Carbon::parse('2025-12-08 14:00:00');

        // First calculation
        $snapshot1 = $this->calculator->calculateHourlySnapshot($dateTime);
        $this->assertEquals(0, $snapshot1->unique_visitors);

        // Add a session
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac1'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(5),
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy()->addMinutes(5),
        ]);

        // Recalculate
        $snapshot2 = $this->calculator->calculateHourlySnapshot($dateTime);

        // Should update the same record
        $this->assertEquals($snapshot1->id, $snapshot2->id);
        $this->assertEquals(1, $snapshot2->unique_visitors);
    }

    /**
     * Test data cleanup based on retention policy.
     */
    public function test_cleanup_removes_old_sessions(): void
    {
        $ap = AccessPoint::create([
            'ap_name' => 'Test AP',
            'mac_address' => 'aabbccddeeff',
            'status' => 'online',
        ]);

        $now = Carbon::now();

        // Create old session (100 days ago)
        $oldSession = ClientSession::create([
            'mac_address_hash' => hash('sha256', 'old'),
            'access_point_id' => $ap->id,
            'connected_at' => $now->copy()->subDays(100),
            'is_returning_visitor' => false,
            'first_seen_at' => $now->copy()->subDays(100),
        ]);

        // Create recent session (50 days ago)
        $recentSession = ClientSession::create([
            'mac_address_hash' => hash('sha256', 'recent'),
            'access_point_id' => $ap->id,
            'connected_at' => $now->copy()->subDays(50),
            'is_returning_visitor' => false,
            'first_seen_at' => $now->copy()->subDays(50),
        ]);

        // Clean up with 90-day retention
        $deletedCount = $this->calculator->cleanupOldSessions(90);

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('client_sessions', ['id' => $oldSession->id]);
        $this->assertDatabaseHas('client_sessions', ['id' => $recentSession->id]);
    }

    /**
     * Test that sessions with null duration are handled.
     */
    public function test_handles_sessions_without_duration(): void
    {
        $ap = AccessPoint::create([
            'ap_name' => 'Test AP',
            'mac_address' => 'aabbccddeeff',
            'status' => 'online',
        ]);

        $dateTime = Carbon::parse('2025-12-08 14:00:00');

        // Session with duration
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac1'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy(),
            'session_duration_seconds' => 1800,
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy(),
        ]);

        // Active session (no disconnection yet)
        ClientSession::create([
            'mac_address_hash' => hash('sha256', 'mac2'),
            'access_point_id' => $ap->id,
            'connected_at' => $dateTime->copy()->addMinutes(30),
            'session_duration_seconds' => null,
            'disconnected_at' => null,
            'is_returning_visitor' => false,
            'first_seen_at' => $dateTime->copy()->addMinutes(30),
        ]);

        $snapshot = $this->calculator->calculateHourlySnapshot($dateTime);

        // Should calculate avg from only completed sessions
        $this->assertEquals(30.0, $snapshot->avg_dwell_time_minutes);
        $this->assertEquals(2, $snapshot->total_sessions);
    }

    /**
     * Test unique constraint on snapshot date and hour.
     */
    public function test_unique_constraint_on_snapshot_date_and_hour(): void
    {
        $dateTime = Carbon::parse('2025-12-08 14:00:00');

        // Create first snapshot
        $snapshot1 = $this->calculator->calculateHourlySnapshot($dateTime);

        // Try to create duplicate (should update instead)
        $snapshot2 = $this->calculator->calculateHourlySnapshot($dateTime);

        // Should be the same record
        $this->assertEquals($snapshot1->id, $snapshot2->id);

        // Verify only one record exists
        $count = AnalyticsSnapshot::where('snapshot_date', '2025-12-08')
            ->where('snapshot_hour', 14)
            ->count();

        $this->assertEquals(1, $count);
    }
}

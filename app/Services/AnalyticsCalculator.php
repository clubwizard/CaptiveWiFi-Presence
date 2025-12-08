<?php

namespace App\Services;

use App\Models\ClientSession;
use App\Models\AnalyticsSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsCalculator
{
    /**
     * Calculate and store hourly analytics snapshot.
     *
     * @param Carbon $dateTime
     * @return AnalyticsSnapshot|null
     */
    public function calculateHourlySnapshot(Carbon $dateTime): ?AnalyticsSnapshot
    {
        $date = $dateTime->toDateString();
        $hour = $dateTime->hour;

        $startOfHour = $dateTime->copy()->startOfHour();
        $endOfHour = $dateTime->copy()->endOfHour();

        return $this->calculateSnapshot($date, $hour, $startOfHour, $endOfHour);
    }

    /**
     * Calculate and store daily analytics snapshot.
     *
     * @param Carbon $date
     * @return AnalyticsSnapshot|null
     */
    public function calculateDailySnapshot(Carbon $date): ?AnalyticsSnapshot
    {
        $dateString = $date->toDateString();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return $this->calculateSnapshot($dateString, null, $startOfDay, $endOfDay);
    }

    /**
     * Calculate analytics for a given time range.
     *
     * @param string $date
     * @param int|null $hour
     * @param Carbon $start
     * @param Carbon $end
     * @return AnalyticsSnapshot|null
     */
    protected function calculateSnapshot(
        string $date,
        ?int $hour,
        Carbon $start,
        Carbon $end
    ): ?AnalyticsSnapshot {
        $sessions = ClientSession::whereBetween('connected_at', [$start, $end])->get();

        if ($sessions->isEmpty()) {
            return $this->createOrUpdateSnapshot($date, $hour, [
                'unique_visitors' => 0,
                'total_sessions' => 0,
                'avg_dwell_time_minutes' => 0,
                'peak_connected_count' => 0,
                'returning_visitor_percentage' => 0,
                'new_visitor_count' => 0,
            ]);
        }

        $uniqueVisitors = $sessions->unique('mac_address_hash')->count();
        $totalSessions = $sessions->count();
        $returningVisitors = $sessions->where('is_returning_visitor', true)->count();
        $newVisitors = $sessions->where('is_returning_visitor', false)->count();

        // Calculate average dwell time
        $sessionsWithDuration = $sessions->whereNotNull('session_duration_seconds');
        $avgDwellTimeMinutes = $sessionsWithDuration->isNotEmpty()
            ? round($sessionsWithDuration->avg('session_duration_seconds') / 60, 2)
            : 0;

        // Calculate peak connected count
        $peakConnectedCount = $this->calculatePeakConnectedCount($start, $end);

        // Calculate returning visitor percentage
        $returningVisitorPercentage = $uniqueVisitors > 0
            ? round(($returningVisitors / $uniqueVisitors) * 100, 2)
            : 0;

        return $this->createOrUpdateSnapshot($date, $hour, [
            'unique_visitors' => $uniqueVisitors,
            'total_sessions' => $totalSessions,
            'avg_dwell_time_minutes' => $avgDwellTimeMinutes,
            'peak_connected_count' => $peakConnectedCount,
            'returning_visitor_percentage' => $returningVisitorPercentage,
            'new_visitor_count' => $newVisitors,
        ]);
    }

    /**
     * Calculate peak connected count during time range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    protected function calculatePeakConnectedCount(Carbon $start, Carbon $end): int
    {
        // Sample at 5-minute intervals to find peak
        $current = $start->copy();
        $peak = 0;

        while ($current <= $end) {
            $count = ClientSession::where('connected_at', '<=', $current)
                ->where(function ($query) use ($current) {
                    $query->whereNull('disconnected_at')
                        ->orWhere('disconnected_at', '>=', $current);
                })
                ->count();

            $peak = max($peak, $count);
            $current->addMinutes(5);
        }

        return $peak;
    }

    /**
     * Create or update analytics snapshot.
     *
     * @param string $date
     * @param int|null $hour
     * @param array $data
     * @return AnalyticsSnapshot
     */
    protected function createOrUpdateSnapshot(string $date, ?int $hour, array $data): AnalyticsSnapshot
    {
        return AnalyticsSnapshot::updateOrCreate(
            [
                'snapshot_date' => $date,
                'snapshot_hour' => $hour,
            ],
            $data
        );
    }

    /**
     * Clean up old session data based on retention policy.
     *
     * @param int $retentionDays Number of days to retain data
     * @return int Number of records deleted
     */
    public function cleanupOldSessions(int $retentionDays = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        return ClientSession::where('connected_at', '<', $cutoffDate)
            ->delete();
    }
}

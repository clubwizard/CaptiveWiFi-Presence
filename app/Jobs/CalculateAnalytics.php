<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\AnalyticsCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CalculateAnalytics implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;

    protected Tenant $tenant;
    protected string $type; // 'hourly' or 'daily'
    protected ?Carbon $dateTime;

    /**
     * Create a new job instance.
     *
     * @param Tenant $tenant
     * @param string $type 'hourly' or 'daily'
     * @param Carbon|null $dateTime Specific date/time to calculate, defaults to now/today
     */
    public function __construct(Tenant $tenant, string $type = 'hourly', ?Carbon $dateTime = null)
    {
        $this->tenant = $tenant;
        $this->type = $type;
        $this->dateTime = $dateTime;
    }

    /**
     * Execute the job.
     * Calculates analytics snapshots for the tenant.
     */
    public function handle(): void
    {
        if (!$this->tenant->isActive()) {
            Log::info('Skipping analytics for inactive tenant', ['tenant_id' => $this->tenant->id]);
            return;
        }

        // Initialize tenant context
        tenancy()->initialize($this->tenant);

        try {
            $calculator = new AnalyticsCalculator();

            if ($this->type === 'hourly') {
                $this->calculateHourlySnapshot($calculator);
            } elseif ($this->type === 'daily') {
                $this->calculateDailySnapshot($calculator);
            }
        } catch (\Exception $e) {
            Log::error('Analytics calculation failed', [
                'tenant_id' => $this->tenant->id,
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Calculate hourly analytics snapshot.
     */
    protected function calculateHourlySnapshot(AnalyticsCalculator $calculator): void
    {
        $dateTime = $this->dateTime ?? Carbon::now();

        Log::info('Calculating hourly analytics', [
            'tenant_id' => $this->tenant->id,
            'date' => $dateTime->toDateString(),
            'hour' => $dateTime->hour,
        ]);

        $snapshot = $calculator->calculateHourlySnapshot($dateTime);

        if ($snapshot) {
            Log::info('Hourly analytics calculated', [
                'tenant_id' => $this->tenant->id,
                'snapshot_id' => $snapshot->id,
                'unique_visitors' => $snapshot->unique_visitors,
                'total_sessions' => $snapshot->total_sessions,
            ]);
        }
    }

    /**
     * Calculate daily analytics snapshot.
     */
    protected function calculateDailySnapshot(AnalyticsCalculator $calculator): void
    {
        $date = $this->dateTime ?? Carbon::today();

        Log::info('Calculating daily analytics', [
            'tenant_id' => $this->tenant->id,
            'date' => $date->toDateString(),
        ]);

        $snapshot = $calculator->calculateDailySnapshot($date);

        if ($snapshot) {
            Log::info('Daily analytics calculated', [
                'tenant_id' => $this->tenant->id,
                'snapshot_id' => $snapshot->id,
                'unique_visitors' => $snapshot->unique_visitors,
                'total_sessions' => $snapshot->total_sessions,
                'avg_dwell_time' => $snapshot->avg_dwell_time_minutes,
            ]);
        }
    }
}

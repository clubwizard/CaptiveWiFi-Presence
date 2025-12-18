<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\AnalyticsCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateDailyReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600;

    protected Tenant $tenant;
    protected ?Carbon $date;

    /**
     * Create a new job instance.
     *
     * @param Tenant $tenant
     * @param Carbon|null $date Date for the report (defaults to yesterday)
     */
    public function __construct(Tenant $tenant, ?Carbon $date = null)
    {
        $this->tenant = $tenant;
        $this->date = $date;
    }

    /**
     * Execute the job.
     * Generates comprehensive daily report and cleans up old data.
     */
    public function handle(): void
    {
        if (!$this->tenant->isActive()) {
            Log::info('Skipping daily report for inactive tenant', ['tenant_id' => $this->tenant->id]);
            return;
        }

        $date = $this->date ?? Carbon::yesterday();

        Log::info('Generating daily report', [
            'tenant_id' => $this->tenant->id,
            'date' => $date->toDateString(),
        ]);

        // Initialize tenant context
        tenancy()->initialize($this->tenant);

        try {
            $calculator = new AnalyticsCalculator();

            // Calculate daily snapshot for the date
            $snapshot = $calculator->calculateDailySnapshot($date);

            if ($snapshot) {
                Log::info('Daily report generated', [
                    'tenant_id' => $this->tenant->id,
                    'date' => $date->toDateString(),
                    'unique_visitors' => $snapshot->unique_visitors,
                    'total_sessions' => $snapshot->total_sessions,
                    'avg_dwell_time' => $snapshot->avg_dwell_time_minutes,
                    'returning_percentage' => $snapshot->returning_visitor_percentage,
                ]);
            }

            // Clean up old session data based on retention policy
            $retentionDays = config('app.analytics_retention_days', env('ANALYTICS_RETENTION_DAYS', 90));
            $deletedCount = $calculator->cleanupOldSessions($retentionDays);

            if ($deletedCount > 0) {
                Log::info('Cleaned up old session data', [
                    'tenant_id' => $this->tenant->id,
                    'deleted_count' => $deletedCount,
                    'retention_days' => $retentionDays,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Daily report generation failed', [
                'tenant_id' => $this->tenant->id,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            tenancy()->end();
        }
    }
}

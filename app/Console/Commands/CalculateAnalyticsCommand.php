<?php

namespace App\Console\Commands;

use App\Jobs\CalculateAnalytics;
use App\Models\Tenant;
use Illuminate\Console\Command;

class CalculateAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:calculate-analytics
                            {type=hourly : Type of analytics (hourly or daily)}
                            {--tenant= : Specific tenant ID to calculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate analytics snapshots for all active tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $tenantId = $this->option('tenant');

        if (!in_array($type, ['hourly', 'daily'])) {
            $this->error('Type must be either "hourly" or "daily"');
            return self::FAILURE;
        }

        if ($tenantId) {
            return $this->calculateForTenant($tenantId, $type);
        }

        return $this->calculateForAllTenants($type);
    }

    /**
     * Calculate analytics for a specific tenant.
     */
    protected function calculateForTenant(string $tenantId, string $type): int
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return self::FAILURE;
        }

        if (!$tenant->isActive()) {
            $this->warn("Tenant {$tenant->name} is not active");
            return self::SUCCESS;
        }

        $this->info("Dispatching {$type} analytics job for tenant: {$tenant->name}");
        CalculateAnalytics::dispatch($tenant, $type);

        return self::SUCCESS;
    }

    /**
     * Calculate analytics for all active tenants.
     */
    protected function calculateForAllTenants(string $type): int
    {
        $tenants = Tenant::all()->filter(fn ($tenant) => $tenant->isActive());

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found');
            return self::SUCCESS;
        }

        $this->info("Dispatching {$type} analytics jobs for {$tenants->count()} tenants...");

        foreach ($tenants as $tenant) {
            CalculateAnalytics::dispatch($tenant, $type);
            $this->line("  - {$tenant->name} ({$tenant->subdomain})");
        }

        $this->info('All analytics jobs dispatched');

        return self::SUCCESS;
    }
}

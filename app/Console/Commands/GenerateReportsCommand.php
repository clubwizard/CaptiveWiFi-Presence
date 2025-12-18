<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDailyReport;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:generate-reports
                            {--tenant= : Specific tenant ID to generate report for}
                            {--date= : Specific date to generate report for (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily reports for all active tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        if ($tenantId) {
            return $this->generateForTenant($tenantId, $date);
        }

        return $this->generateForAllTenants($date);
    }

    /**
     * Generate report for a specific tenant.
     */
    protected function generateForTenant(string $tenantId, ?Carbon $date): int
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

        $dateStr = $date ? $date->toDateString() : 'yesterday';
        $this->info("Dispatching daily report job for tenant: {$tenant->name} ({$dateStr})");
        GenerateDailyReport::dispatch($tenant, $date);

        return self::SUCCESS;
    }

    /**
     * Generate reports for all active tenants.
     */
    protected function generateForAllTenants(?Carbon $date): int
    {
        $tenants = Tenant::all()->filter(fn ($tenant) => $tenant->isActive());

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found');
            return self::SUCCESS;
        }

        $dateStr = $date ? $date->toDateString() : 'yesterday';
        $this->info("Dispatching daily report jobs for {$tenants->count()} tenants ({$dateStr})...");

        foreach ($tenants as $tenant) {
            GenerateDailyReport::dispatch($tenant, $date);
            $this->line("  - {$tenant->name} ({$tenant->subdomain})");
        }

        $this->info('All report jobs dispatched');

        return self::SUCCESS;
    }
}

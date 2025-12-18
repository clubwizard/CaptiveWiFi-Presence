<?php

namespace App\Console\Commands;

use App\Jobs\PollFortiGateClients;
use App\Models\Tenant;
use Illuminate\Console\Command;

class PollFortiGateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:poll-fortigate
                            {--tenant= : Specific tenant ID to poll}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll FortiGate devices for all active tenants to track WiFi clients';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            return $this->pollSpecificTenant($tenantId);
        }

        return $this->pollAllTenants();
    }

    /**
     * Poll a specific tenant.
     */
    protected function pollSpecificTenant(string $tenantId): int
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

        $this->info("Dispatching poll job for tenant: {$tenant->name}");
        PollFortiGateClients::dispatch($tenant);

        return self::SUCCESS;
    }

    /**
     * Poll all active tenants.
     */
    protected function pollAllTenants(): int
    {
        $tenants = Tenant::all()->filter(fn ($tenant) => $tenant->isActive());

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found');
            return self::SUCCESS;
        }

        $this->info("Dispatching poll jobs for {$tenants->count()} tenants...");

        foreach ($tenants as $tenant) {
            PollFortiGateClients::dispatch($tenant);
            $this->line("  - {$tenant->name} ({$tenant->subdomain})");
        }

        $this->info('All poll jobs dispatched');

        return self::SUCCESS;
    }
}

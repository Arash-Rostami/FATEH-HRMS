<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantHidden extends Command
{
    protected $signature = 'tenant:hidden {--all : List every switchable module and view key} {--tenant= : Inspect this tenant slug instead of the active one} {--every : Inspect every tenant (sub-tenants included once they exist)}';

    protected $description = 'Print the modules and views hidden for the active tenant (expanded aliases and dependents); with --all, every key that can be switched off.';

    public function handle(): int
    {
        $slugs = $this->option('every') ? array_keys(config('tenants')) : [$this->option('tenant') ?: config('app.tenant')];

        foreach ($slugs as $slug) {
            if (!isset(config('tenants')[$slug])) {
                $this->error("Unknown tenant: {$slug}");

                return self::FAILURE;
            }

            if ($slug !== config('app.tenant')) {
                config(['app.tenant' => $slug, 'app.off' => array_keys(array_filter(config("tenants.{$slug}.modules", []), fn ($on) => !$on))]);
            }

            $this->line('Tenant: ' . $slug);
            $this->line('Hidden: ' . (implode(', ', array_keys(app(TenantService::class)->lookup())) ?: '-'));
        }

        if ($this->option('all')) {
            $this->line('Switchable: ' . collect(config('modules'))
                    ->flatMap(fn (array $module) => [$module['id'], ...array_map(fn (string $view) => "{$module['id']}:{$view}", array_slice($module['views'] ?? [], 1))])
                    ->implode(', '));
        }

        return self::SUCCESS;
    }
}

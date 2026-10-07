<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Settings\SettingsRuntimeBootstrap;
use Illuminate\Console\Command;

class BootstrapRuntimeSettingsCommand extends Command
{
    protected $signature = 'settings:bootstrap-runtime {--force : Rebuild cached runtime settings}';

    protected $description = 'Load DB-backed payment, mail, storage, and search config into the runtime (run on deploy)';

    public function handle(SettingsRuntimeBootstrap $bootstrap): int
    {
        if ($this->option('force')) {
            $bootstrap->forceRefresh();
        } else {
            $bootstrap->apply();
        }

        $this->info('Runtime settings bootstrap complete.');

        return self::SUCCESS;
    }
}

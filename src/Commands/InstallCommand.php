<?php

namespace Ddys\Laravel\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'ddys:install {--force : Overwrite previously published files} {--views : Publish Blade views for customization}';
    protected $description = 'Publish DDYS Laravel config, views, and assets.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->call('vendor:publish', ['--tag' => 'ddys-config', '--force' => $force]);
        $this->call('vendor:publish', ['--tag' => 'ddys-assets', '--force' => $force]);
        if ($this->option('views')) {
            $this->call('vendor:publish', ['--tag' => 'ddys-views', '--force' => $force]);
        }
        $this->info('DDYS Laravel package installed.');

        return self::SUCCESS;
    }
}

<?php

namespace Ddys\Laravel\Commands;

use Ddys\Laravel\CacheStore;
use Illuminate\Console\Command;

class ClearCacheCommand extends Command
{
    protected $signature = 'ddys:clear-cache';
    protected $description = 'Clear cached DDYS API responses.';

    public function handle(CacheStore $cache): int
    {
        $cache->clear();
        $this->info('DDYS cache cleared.');

        return self::SUCCESS;
    }
}


<?php

namespace Ddys\Laravel\Commands;

use Ddys\Laravel\Client;
use Ddys\Laravel\Exceptions\DdysException;
use Illuminate\Console\Command;

class DiagnosticsCommand extends Command
{
    protected $signature = 'ddys:test {--route=latest : API route to test}';
    protected $description = 'Test the DDYS API connection and show package diagnostics.';

    public function handle(Client $client): int
    {
        $route = (string) $this->option('route');
        $this->line('DDYS Laravel package ' . Client::VERSION);
        $this->line('API Base URL: ' . config('ddys.api_base_url'));
        $this->line('API Key: ' . (config('ddys.api_key') ? 'configured' : 'not configured'));

        try {
            $payload = $route === 'hot'
                ? $client->get('/hot', ['limit' => 1], ['no_cache' => true])
                : $client->get('/latest', ['limit' => 1], ['no_cache' => true]);
            $this->info('Connection OK.');
            $this->line('Response keys: ' . implode(', ', array_keys($payload)));

            return self::SUCCESS;
        } catch (DdysException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}


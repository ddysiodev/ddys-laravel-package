<?php

namespace Ddys\Laravel\Commands;

use Illuminate\Console\Command;

class RoutesCommand extends Command
{
    protected $signature = 'ddys:routes';
    protected $description = 'List DDYS package routes and shortcodes.';

    public function handle(): int
    {
        $prefix = trim((string) config('ddys.routes.prefix', 'ddys'), '/');
        $proxy = trim((string) config('ddys.proxy.prefix', 'ddys-api'), '/');
        $rows = [
            ['GET', "/{$prefix}", 'front'],
            ['GET', "/{$prefix}/latest", 'latest'],
            ['GET', "/{$prefix}/hot", 'hot'],
            ['GET', "/{$prefix}/movies", 'movies'],
            ['GET', "/{$prefix}/search", 'search'],
            ['GET', "/{$prefix}/calendar", 'calendar'],
            ['GET', "/{$prefix}/movie/{slug}", 'movie'],
            ['GET', "/{$prefix}/movie/{slug}/sources", 'sources'],
            ['GET', "/{$prefix}/movie/{slug}/related", 'related'],
            ['GET', "/{$prefix}/movie/{slug}/comments", 'comments'],
            ['GET', "/{$prefix}/collections", 'collections'],
            ['GET', "/{$prefix}/collection/{slug}", 'collection'],
            ['GET', "/{$prefix}/shares", 'shares'],
            ['GET', "/{$prefix}/share/{id}", 'share'],
            ['GET', "/{$prefix}/requests", 'requests'],
            ['GET', "/{$prefix}/activities", 'activities'],
            ['GET', "/{$prefix}/user/{username}", 'user'],
            ['GET', "/{$prefix}/types", 'types'],
            ['GET', "/{$prefix}/genres", 'genres'],
            ['GET', "/{$prefix}/regions", 'regions'],
            ['POST', "/{$prefix}/request-submit", 'request-submit'],
            ['GET', "/{$proxy}/{route}", 'proxy'],
        ];

        $this->table(['Method', 'URI', 'Name'], $rows);

        return self::SUCCESS;
    }
}


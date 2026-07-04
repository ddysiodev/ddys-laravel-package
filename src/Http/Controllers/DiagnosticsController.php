<?php

namespace Ddys\Laravel\Http\Controllers;

use Ddys\Laravel\CacheStore;
use Ddys\Laravel\Client;
use Ddys\Laravel\Exceptions\DdysException;
use Ddys\Laravel\PageService;
use Ddys\Laravel\Shortcode;
use Illuminate\Http\Request;
use Throwable;

class DiagnosticsController
{
    public function __construct(
        protected Client $client,
        protected CacheStore $cache,
        protected Shortcode $shortcode,
        protected PageService $pages
    ) {}

    public function index()
    {
        abort_unless(config('ddys.diagnostics.enabled', false), 403);

        return view('ddys::diagnostics', [
            'config' => config('ddys'),
            'shortcodes' => $this->shortcode->examples(),
            'views' => $this->pages->views(),
            'testUrl' => $this->routeUrl('diagnostics.test'),
            'cacheUrl' => $this->routeUrl('diagnostics.cache'),
        ]);
    }

    public function test(Request $request)
    {
        abort_unless(config('ddys.diagnostics.enabled', false), 403);

        try {
            return response()->json(['success' => true, 'data' => $this->client->get('/latest', ['limit' => 1], ['no_cache' => true])]);
        } catch (DdysException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function clear()
    {
        abort_unless(config('ddys.diagnostics.enabled', false), 403);
        $this->cache->clear();

        return response()->json(['success' => true]);
    }

    protected function routeUrl(string $name): string
    {
        $route = rtrim((string) config('ddys.routes.name', 'ddys.'), '.') . '.' . $name;

        try {
            return route($route);
        } catch (Throwable) {
            return url(trim((string) config('ddys.routes.prefix', 'ddys'), '/') . '/' . str_replace('.', '/', $name));
        }
    }
}

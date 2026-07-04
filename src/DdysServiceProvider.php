<?php

namespace Ddys\Laravel;

use Ddys\Laravel\Commands\ClearCacheCommand;
use Ddys\Laravel\Commands\DiagnosticsCommand;
use Ddys\Laravel\Commands\InstallCommand;
use Ddys\Laravel\Commands\RoutesCommand;
use Ddys\Laravel\View\Components\Widget;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DdysServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ddys.php', 'ddys');

        $this->app->singleton(CacheStore::class, function ($app) {
            return new CacheStore($app['cache'], (array) $app['config']->get('ddys.cache', []));
        });

        $this->app->singleton(Client::class, function ($app) {
            return new Client($app->make(HttpFactory::class), $app->make(CacheStore::class), (array) $app['config']->get('ddys', []));
        });

        $this->app->alias(Client::class, 'ddys.client');
        $this->app->singleton(Renderer::class);
        $this->app->alias(Renderer::class, 'ddys.renderer');
        $this->app->singleton(Shortcode::class);
        $this->app->alias(Shortcode::class, 'ddys.shortcode');
        $this->app->singleton(PageService::class);
        $this->app->singleton(RequestService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/ddys.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ddys');

        $this->registerBlade();
        $this->registerPublishing();

        if ($this->app->runningInConsole()) {
            $this->commands([
                DiagnosticsCommand::class,
                ClearCacheCommand::class,
                RoutesCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    protected function registerBlade(): void
    {
        Blade::component('ddys-widget', Widget::class);
        Blade::componentNamespace('Ddys\\Laravel\\View\\Components', 'ddys');

        Blade::directive('ddys', function ($expression) {
            $args = $this->stripDirectiveExpression((string) $expression);

            return "<?php echo app('ddys.renderer')->render({$args}); ?>";
        });

        foreach ($this->shortcodeNames() as $name) {
            $method = 'ddys' . str_replace(' ', '', ucwords(str_replace('_', ' ', substr($name, 5))));
            Blade::directive($method, function ($expression = '') use ($name) {
                $expression = $this->stripDirectiveExpression((string) $expression);
                $args = $expression === '' ? '[]' : $expression;

                return "<?php echo app('ddys.shortcode')->render('{$name}', {$args}); ?>";
            });
        }

        Blade::directive('ddysAssets', function () {
            return "<?php echo app('ddys.renderer')->assets(); ?>";
        });
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ddys.php' => config_path('ddys.php'),
        ], ['ddys', 'ddys-config']);

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/ddys'),
        ], ['ddys', 'ddys-views']);

        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/ddys-laravel'),
        ], ['ddys', 'ddys-assets', 'laravel-assets']);
    }

    protected function stripDirectiveExpression(string $expression): string
    {
        $expression = trim($expression);

        if ($expression === '()') {
            return '';
        }

        if (str_starts_with($expression, '(') && str_ends_with($expression, ')')) {
            return trim(substr($expression, 1, -1));
        }

        return $expression;
    }

    protected function shortcodeNames(): array
    {
        return [
            'ddys_movies', 'ddys_latest', 'ddys_hot', 'ddys_search', 'ddys_suggest',
            'ddys_calendar', 'ddys_movie', 'ddys_sources', 'ddys_related', 'ddys_comments',
            'ddys_collections', 'ddys_collection', 'ddys_shares', 'ddys_share',
            'ddys_requests', 'ddys_activities', 'ddys_user', 'ddys_types',
            'ddys_genres', 'ddys_regions', 'ddys_request_form',
        ];
    }
}

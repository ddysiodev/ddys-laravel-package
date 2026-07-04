<?php

namespace Ddys\Laravel;

use Ddys\Laravel\Support\Security;
use Throwable;

class PageService
{
    public function __construct(protected Renderer $renderer) {}

    public function views(): array
    {
        return [
            'movies', 'latest', 'hot', 'search', 'suggest', 'calendar',
            'movie', 'sources', 'related', 'comments', 'collections', 'collection',
            'shares', 'share', 'requests', 'activities', 'user', 'types', 'genres', 'regions',
        ];
    }

    public function labels(): array
    {
        return [
            'movies' => 'Movies',
            'latest' => 'Latest',
            'hot' => 'Hot',
            'search' => 'Search',
            'suggest' => 'Suggest',
            'calendar' => 'Calendar',
            'movie' => 'Movie Detail',
            'sources' => 'Sources',
            'related' => 'Related',
            'comments' => 'Comments',
            'collections' => 'Collections',
            'collection' => 'Collection Detail',
            'shares' => 'Shares',
            'share' => 'Share Detail',
            'requests' => 'Requests',
            'activities' => 'Activities',
            'user' => 'User',
            'types' => 'Types',
            'genres' => 'Genres',
            'regions' => 'Regions',
        ];
    }

    public function normaliseView(string $view): string
    {
        return in_array($view, $this->views(), true) ? $view : 'latest';
    }

    public function title(string $view): string
    {
        $labels = $this->labels();

        return 'DDYS ' . ($labels[$view] ?? 'Latest');
    }

    public function tabs(string $active): string
    {
        if (!config('ddys.display.show_nav', true)) {
            return '';
        }

        $tabs = ['latest', 'hot', 'movies', 'search', 'calendar', 'collections', 'shares', 'requests', 'types'];
        $labels = $this->labels();
        $html = '<nav class="ddys-laravel-tabs" aria-label="DDYS">';

        foreach ($tabs as $view) {
            $html .= '<a class="' . ($active === $view ? 'active' : '') . '" href="' . Security::attr($this->routeUrl($view)) . '">' . Security::h($labels[$view]) . '</a>';
        }

        return $html . '</nav>';
    }

    public function render(string $view, array $params = []): string
    {
        $view = $this->normaliseView($view);

        if ($view === 'requests') {
            $html = config('ddys.request_form.enabled', false) ? $this->renderer->renderRequestForm([]) : '';
            return $html . $this->renderer->render('requests', $params);
        }

        return $this->renderer->render($view, $params);
    }

    protected function routeUrl(string $name, array $params = []): string
    {
        $route = rtrim((string) config('ddys.routes.name', 'ddys.'), '.') . '.' . $name;

        try {
            return route($route, $params);
        } catch (Throwable) {
            return url(trim((string) config('ddys.routes.prefix', 'ddys'), '/') . ($name === 'front' ? '' : '/' . $name));
        }
    }
}


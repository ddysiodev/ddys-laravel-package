<?php

namespace Ddys\Laravel;

use Ddys\Laravel\Exceptions\DdysException;
use Ddys\Laravel\Support\Security;
use Throwable;

class Renderer
{
    public function __construct(protected Client $client) {}

    public function render(string $view, array $params = []): string
    {
        $view = strtolower(trim($view));
        $view = str_starts_with($view, 'ddys_') ? substr($view, 5) : $view;

        try {
            return match ($view) {
                'movies' => $this->renderList($this->client->movies($params), $params),
                'latest' => $this->renderList($this->client->latest($params), $params),
                'hot' => $this->renderList($this->client->hot($params), $params),
                'search' => $this->renderSearch($params),
                'suggest' => $this->renderList($this->client->suggest((string) ($params['q'] ?? ''), $params), $params),
                'calendar' => $this->renderCalendar($this->client->calendar($params), $params),
                'movie' => $this->renderRequiredSlug($params, 'movie'),
                'sources' => $this->renderRequiredSlug($params, 'sources'),
                'related' => $this->renderRequiredSlug($params, 'related'),
                'comments' => $this->renderRequiredSlug($params, 'comments'),
                'collections' => $this->renderList($this->client->collections($params), $params),
                'collection' => $this->renderRequiredSlug($params, 'collection'),
                'shares' => $this->renderList($this->client->shares($params), $params),
                'share' => $this->renderShare($params),
                'requests' => $this->renderList($this->client->requests($params), $params),
                'activities' => $this->renderList($this->client->activities($params), $params),
                'user' => $this->renderUser($params),
                'types' => $this->renderDictionary($this->client->types(), $params),
                'genres' => $this->renderDictionary($this->client->genres(), $params),
                'regions' => $this->renderDictionary($this->client->regions(), $params),
                'request_form', 'requestform' => $this->renderRequestForm($params),
                default => '',
            };
        } catch (DdysException $e) {
            return $this->renderError($e->getMessage(), $params);
        }
    }

    public function renderList(mixed $payload, array $params = []): string
    {
        $items = $this->toList($payload);

        if ($items === []) {
            return $this->renderEmpty('No DDYS content found.', $params);
        }

        $html = '<div class="ddys-laravel-items">';
        foreach ($items as $item) {
            $html .= $this->card($item, $params);
        }
        $html .= '</div>' . $this->paginationMeta($payload);

        return $this->wrap($html, $params);
    }

    public function renderDetail(mixed $data, array $params = []): string
    {
        $data = $this->payloadData($data);

        if (!is_array($data)) {
            return $this->renderEmpty('No detail found.', $params);
        }

        $html = '<div class="ddys-laravel-detail">';
        $html .= $this->card($data, $params);
        $intro = $this->value($data, ['intro', 'description', 'summary', 'note', 'content', 'bio'], '');

        if ($intro !== '') {
            $html .= '<div class="ddys-laravel-description">' . nl2br(Security::h($intro)) . '</div>';
        }

        if (!empty($data['movies']) && is_array($data['movies'])) {
            $html .= '<h3>Movies</h3><div class="ddys-laravel-items">';
            foreach ($data['movies'] as $item) {
                $html .= $this->card($item, $params);
            }
            $html .= '</div>';
        }

        if (!empty($data['resources']) || !empty($data['sources']) || !empty($data['online']) || !empty($data['download'])) {
            $html .= $this->renderSources($data, $params, true);
        }

        $html .= '</div>';

        return $this->wrap($html, $params);
    }

    public function renderSources(mixed $data, array $params = [], bool $inner = false): string
    {
        $data = $this->payloadData($data);

        if (!is_array($data)) {
            return $this->renderEmpty('No sources found.', $params);
        }

        $groups = [];
        if (isset($data['online']) || isset($data['download'])) {
            if (!empty($data['online'])) {
                $groups['Online'] = $data['online'];
            }
            if (!empty($data['download'])) {
                $groups['Download'] = $data['download'];
            }
        } elseif (isset($data['resources'])) {
            $groups['Resources'] = $data['resources'];
        } elseif (isset($data['sources'])) {
            $groups['Resources'] = $data['sources'];
        } else {
            $groups = $this->isAssoc($data) ? $data : ['Resources' => $data];
        }

        $html = '<div class="ddys-laravel-sources">';
        foreach ($groups as $name => $resources) {
            if (!is_array($resources)) {
                continue;
            }
            $html .= '<section class="ddys-laravel-source-group"><h3>' . Security::h($name) . '</h3>';
            foreach ($resources as $resource) {
                if (!is_array($resource)) {
                    continue;
                }
                $title = $this->value($resource, ['title', 'name', 'label', 'download_type', 'type', 'quality'], 'Resource');
                $url = $this->value($resource, ['url', 'link', 'href'], '');
                $html .= '<p class="ddys-laravel-resource">' . $this->resourceLinks($title, $url) . '</p>';
            }
            $html .= '</section>';
        }
        $html .= '</div>';

        return $inner ? $html : $this->wrap($html, $params);
    }

    public function renderCalendar(mixed $payload, array $params = []): string
    {
        $data = $this->payloadData($payload);
        $days = is_array($data) && isset($data['days']) && is_array($data['days']) ? $data['days'] : $data;

        if (!is_array($days)) {
            return $this->renderList($payload, $params);
        }

        $html = '<div class="ddys-laravel-calendar">';
        foreach ($days as $day => $items) {
            if (is_array($items) && isset($items['shows']) && is_array($items['shows'])) {
                $items = $items['shows'];
            }
            $html .= '<section class="ddys-laravel-calendar-day"><h3>' . Security::h($day) . '</h3>';
            $html .= '<div class="ddys-laravel-items">';
            if (is_array($items)) {
                foreach ($items as $item) {
                    $html .= $this->card($item, $params);
                }
            }
            $html .= '</div></section>';
        }
        $html .= '</div>';

        return $this->wrap($html, $params);
    }

    public function renderDictionary(mixed $payload, array $params = []): string
    {
        $items = $this->toList($payload);

        if ($items === []) {
            return $this->renderEmpty('No dictionary data found.', $params);
        }

        $html = '<div class="ddys-laravel-tags">';
        foreach ($items as $item) {
            $label = is_array($item) ? $this->value($item, ['name', 'title', 'label', 'value'], '') : (string) $item;
            $code = is_array($item) ? $this->value($item, ['code', 'slug', 'id'], '') : '';
            if ($label !== '') {
                $html .= '<span>' . Security::h($label) . ($code !== '' ? ' <code>' . Security::h($code) . '</code>' : '') . '</span>';
            }
        }
        $html .= '</div>';

        return $this->wrap($html, $params);
    }

    public function renderSearch(array $params = []): string
    {
        $q = Security::scalar(request('q', request('ddys_q', $params['q'] ?? '')));
        $type = Security::choice(request('type', request('ddys_type', $params['type'] ?? 'movie')), ['movie', 'share', 'request'], 'movie');
        $html = '<form class="ddys-laravel-search" method="get" action="' . Security::attr($this->routeUrl('search')) . '">';
        $html .= '<input type="search" name="q" value="' . Security::attr($q) . '" placeholder="Search DDYS">';
        $html .= '<select name="type"><option value="movie"' . ($type === 'movie' ? ' selected' : '') . '>Movie</option><option value="share"' . ($type === 'share' ? ' selected' : '') . '>Share</option><option value="request"' . ($type === 'request' ? ' selected' : '') . '>Request</option></select>';
        $html .= '<button type="submit">Search</button></form>';

        if ($q !== '') {
            try {
                $html .= $this->renderList($this->client->search(['q' => $q, 'type' => $type, 'per_page' => $params['per_page'] ?? 12]), $params);
            } catch (DdysException $e) {
                $html .= $this->renderError($e->getMessage(), $params);
            }
        }

        return $this->wrap($html, $params);
    }

    public function renderRequestForm(array $params = []): string
    {
        if (!config('ddys.request_form.enabled', false)) {
            return $this->renderEmpty('DDYS request form is disabled.', $params);
        }

        $honeypot = (string) config('ddys.request_form.honeypot_field', 'ddys_website');
        $html = '<form class="ddys-laravel-request-form" method="post" action="' . Security::attr($this->routeUrl('request-submit')) . '" data-ddys-laravel-request-form>';
        $html .= csrf_field();
        $html .= '<label class="ddys-laravel-honeypot-wrapper" style="left:-10000px;position:absolute;top:auto;">Website<input class="ddys-laravel-honeypot" style="left:-10000px;position:absolute;top:auto;" type="text" name="' . Security::attr($honeypot) . '" value="" tabindex="-1" autocomplete="off" aria-hidden="true"></label>';
        $html .= '<label>Title<input type="text" name="title" maxlength="255" required></label>';
        $html .= '<label>Year<input type="number" name="year" min="1900" max="2099"></label>';
        $html .= '<label>Type<select name="type"><option value=""></option><option value="movie">Movie</option><option value="series">Series</option><option value="variety">Variety</option><option value="anime">Anime</option></select></label>';
        $html .= '<label>Douban ID<input type="text" name="douban_id" maxlength="30"></label>';
        $html .= '<label>IMDb ID<input type="text" name="imdb_id" maxlength="30"></label>';
        $html .= '<label>Description<textarea name="description" maxlength="1000"></textarea></label>';
        $html .= '<button type="submit">Submit request</button><p class="ddys-laravel-status" role="status"></p></form>';

        return $this->wrap($html, $params);
    }

    public function assets(): string
    {
        if (!config('ddys.display.load_assets', true)) {
            return '';
        }

        $version = Client::VERSION;

        return '<link rel="stylesheet" href="' . Security::attr(asset('vendor/ddys-laravel/css/frontend.css') . '?v=' . $version) . '">'
            . '<script defer src="' . Security::attr(asset('vendor/ddys-laravel/js/frontend.js') . '?v=' . $version) . '"></script>';
    }

    public function wrap(string $html, array $params = []): string
    {
        $layout = Security::choice($params['layout'] ?? config('ddys.display.layout', 'grid'), ['grid', 'list', 'compact'], 'grid');
        $theme = Security::choice($params['theme'] ?? config('ddys.display.theme', 'auto'), ['auto', 'light', 'dark'], 'auto');
        $columns = Security::intRange($params['columns'] ?? config('ddys.display.columns', 4), 4, 1, 6);

        return '<div class="ddys-laravel ddys-laravel-theme-' . Security::attr($theme) . ' ddys-laravel-layout-' . Security::attr($layout) . '" style="--ddys-laravel-columns:' . $columns . '">' . $html . '</div>';
    }

    public function renderError(string $message, array $params = []): string
    {
        return $this->wrap('<div class="ddys-laravel-alert ddys-laravel-alert-error">' . Security::h($message) . '</div>', $params);
    }

    public function renderEmpty(string $message, array $params = []): string
    {
        return $this->wrap('<div class="ddys-laravel-empty">' . Security::h($message) . '</div>', $params);
    }

    protected function renderRequiredSlug(array $params, string $view): string
    {
        $slug = Security::scalar($params['slug'] ?? '');
        if ($slug === '') {
            return $this->renderError('Missing slug.', $params);
        }

        return match ($view) {
            'movie' => $this->renderDetail($this->client->movie($slug), $params),
            'sources' => $this->renderSources($this->client->sources($slug), $params),
            'related' => $this->renderList($this->client->related($slug), $params),
            'comments' => $this->renderList($this->client->comments($slug, $params), $params),
            'collection' => $this->renderDetail($this->client->collection($slug, $params), $params),
            default => '',
        };
    }

    protected function renderShare(array $params): string
    {
        $id = Security::scalar($params['id'] ?? '');
        if (!preg_match('/^[1-9][0-9]*$/', $id)) {
            return $this->renderError('Missing share ID.', $params);
        }

        return $this->renderDetail($this->client->share($id), $params);
    }

    protected function renderUser(array $params): string
    {
        $username = Security::scalar($params['username'] ?? '');
        if ($username === '') {
            return $this->renderError('Missing username.', $params);
        }

        return $this->renderDetail($this->client->user($username), $params);
    }

    protected function card(mixed $item, array $params = []): string
    {
        if (!is_array($item)) {
            return '';
        }

        $title = $this->value($item, ['title', 'name', 'cn_name', 'en_name', 'username', 'search_keyword'], 'Untitled');
        $poster = Security::safeMediaUrl($this->value($item, ['poster', 'cover', 'image', 'avatar'], ''));
        $url = $this->siteUrl($item);
        $target = Security::choice($params['target'] ?? config('ddys.display.target', '_blank'), ['_blank', '_self'], '_blank');
        $showPoster = Security::bool($params['show_poster'] ?? config('ddys.display.show_poster', true));
        $showRating = Security::bool($params['show_rating'] ?? config('ddys.display.show_rating', true));
        $showSummary = Security::bool($params['show_summary'] ?? config('ddys.display.show_summary', true));
        $meta = [];

        foreach (['year', 'type', 'type_code', 'region', 'quality', 'episode', 'status', 'resource_type'] as $key) {
            if (!empty($item[$key])) {
                $meta[] = is_array($item[$key]) ? implode(', ', $item[$key]) : (string) $item[$key];
            }
        }

        if ($showRating && !empty($item['rating'])) {
            $meta[] = 'Rating ' . $item['rating'];
        }

        $summary = $this->value($item, ['description', 'intro', 'summary', 'note', 'content', 'bio'], '');
        $html = '<article class="ddys-laravel-card">';

        if ($showPoster && $poster !== '') {
            $html .= '<div class="ddys-laravel-poster"><img src="' . Security::attr($poster) . '" alt="' . Security::attr($title) . '" loading="lazy"></div>';
        }

        $html .= '<div class="ddys-laravel-card-body"><h3 class="ddys-laravel-title">';
        if ($url !== '' && config('ddys.display.show_source_link', true)) {
            $html .= '<a href="' . Security::attr($url) . '" target="' . Security::attr($target) . '" rel="noopener">' . Security::h($title) . '</a>';
        } else {
            $html .= Security::h($title);
        }
        $html .= '</h3>';

        if ($meta !== []) {
            $html .= '<div class="ddys-laravel-meta">' . Security::h(implode(' / ', $meta)) . '</div>';
        }

        if ($showSummary && $summary !== '') {
            $html .= '<div class="ddys-laravel-summary">' . Security::h(Security::substr(strip_tags((string) $summary), 0, 160)) . '</div>';
        }

        $html .= '</div></article>';

        return $html;
    }

    protected function resourceLinks(string $title, string $url): string
    {
        if ($url === '') {
            return Security::h($title);
        }

        $allowed = (array) config('ddys.security.allowed_resource_protocols', ['http:', 'https:', 'magnet:', 'ed2k:', 'thunder:']);
        $links = [];
        $parts = explode('#', $url);

        foreach ($parts as $index => $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $label = $title;
            $href = $part;
            if (str_contains($part, '$')) {
                [$labelCandidate, $hrefCandidate] = explode('$', $part, 2);
                $label = $labelCandidate !== '' ? $labelCandidate : $title;
                $href = $hrefCandidate;
            } elseif (count($parts) > 1) {
                $label = $title . ' ' . ($index + 1);
            }
            foreach ($allowed as $protocol) {
                if (stripos($href, $protocol) === 0) {
                    $links[] = '<a href="' . Security::attr($href) . '" target="_blank" rel="noopener">' . Security::h($label) . '</a>';
                    break;
                }
            }
        }

        return $links === [] ? Security::h($title) : implode(' ', $links);
    }

    protected function siteUrl(array $item): string
    {
        $site = rtrim((string) config('ddys.site_base_url', 'https://ddys.io'), '/');
        $url = $this->value($item, ['url', 'link', 'href'], '');

        if ($url !== '' && preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if ($url !== '' && str_starts_with($url, '/')) {
            return $site . $url;
        }

        $slug = $this->value($item, ['slug'], '');

        return $slug !== '' ? $site . '/movie/' . rawurlencode($slug) : '';
    }

    protected function toList(mixed $payload): array
    {
        $data = $this->payloadData($payload);
        if (!is_array($data)) {
            return [];
        }

        foreach (['items', 'movies', 'results', 'related', 'series', 'shares', 'requests', 'activities', 'comments', 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        if ($this->isAssoc($data) && (isset($data['slug']) || isset($data['id']) || isset($data['title']) || isset($data['name']))) {
            return [$data];
        }

        return $this->isAssoc($data) ? [] : $data;
    }

    protected function payloadData(mixed $payload): mixed
    {
        return is_array($payload) && array_key_exists('data', $payload) ? $payload['data'] : $payload;
    }

    protected function paginationMeta(mixed $payload): string
    {
        $meta = is_array($payload) && isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];
        if ($meta === [] || empty($meta['total'])) {
            return '';
        }

        return '<div class="ddys-laravel-page-meta">Page ' . Security::h($meta['page'] ?? 1) . ', total ' . Security::h($meta['total']) . '</div>';
    }

    protected function value(array $item, array $keys, string $fallback = ''): string
    {
        foreach ($keys as $key) {
            if (isset($item[$key]) && $item[$key] !== '') {
                return is_array($item[$key]) ? implode(', ', $item[$key]) : (string) $item[$key];
            }
        }

        return $fallback;
    }

    protected function isAssoc(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
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


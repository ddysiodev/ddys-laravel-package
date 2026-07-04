<?php

namespace Ddys\Laravel;

class Shortcode
{
    public function __construct(protected Renderer $renderer) {}

    public function names(): array
    {
        return [
            'ddys_movies', 'ddys_latest', 'ddys_hot', 'ddys_search', 'ddys_suggest',
            'ddys_calendar', 'ddys_movie', 'ddys_sources', 'ddys_related', 'ddys_comments',
            'ddys_collections', 'ddys_collection', 'ddys_shares', 'ddys_share',
            'ddys_requests', 'ddys_activities', 'ddys_user', 'ddys_types',
            'ddys_genres', 'ddys_regions', 'ddys_request_form',
        ];
    }

    public function examples(): array
    {
        return [
            'ddys_movies' => '[ddys_movies type="movie" per_page="24"]',
            'ddys_latest' => '[ddys_latest type="movie" limit="12"]',
            'ddys_hot' => '[ddys_hot limit="10"]',
            'ddys_search' => '[ddys_search]',
            'ddys_suggest' => '[ddys_suggest q="interstellar"]',
            'ddys_calendar' => '[ddys_calendar year="2026" month="7"]',
            'ddys_movie' => '[ddys_movie slug="this-tempting-madness"]',
            'ddys_sources' => '[ddys_sources slug="this-tempting-madness"]',
            'ddys_related' => '[ddys_related slug="this-tempting-madness"]',
            'ddys_comments' => '[ddys_comments slug="this-tempting-madness" per_page="20"]',
            'ddys_collections' => '[ddys_collections per_page="10"]',
            'ddys_collection' => '[ddys_collection slug="best-sci-fi"]',
            'ddys_shares' => '[ddys_shares per_page="10"]',
            'ddys_share' => '[ddys_share id="1"]',
            'ddys_requests' => '[ddys_requests per_page="10"]',
            'ddys_activities' => '[ddys_activities per_page="10"]',
            'ddys_user' => '[ddys_user username="demo"]',
            'ddys_types' => '[ddys_types]',
            'ddys_genres' => '[ddys_genres]',
            'ddys_regions' => '[ddys_regions]',
            'ddys_request_form' => '[ddys_request_form]',
        ];
    }

    public function parse(string $content): string
    {
        if (!str_contains($content, '[ddys_')) {
            return $content;
        }

        return (string) preg_replace_callback('/\[(ddys_[a-z_]+)([^\]]*)\]/i', function (array $matches) {
            return $this->render(strtolower($matches[1]), $this->parseAttributes($matches[2] ?? ''));
        }, $content);
    }

    public function parseAttributes(string $text): array
    {
        $atts = [];

        if (preg_match_all('/([a-zA-Z0-9_:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : $match[4]);
                $atts[strtolower($match[1])] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $atts;
    }

    public function render(string $tag, array $atts = []): string
    {
        $tag = strtolower($tag);
        if (!in_array($tag, $this->names(), true)) {
            return '';
        }

        $atts = $this->commonAttributes($atts);
        $view = substr($tag, 5);

        return $this->renderer->render($view, $atts);
    }

    protected function commonAttributes(array $atts): array
    {
        return array_merge([
            'layout' => config('ddys.display.layout', 'grid'),
            'theme' => config('ddys.display.theme', 'auto'),
            'columns' => config('ddys.display.columns', 4),
            'target' => config('ddys.display.target', '_blank'),
            'limit' => config('ddys.display.default_limit', 12),
            'per_page' => config('ddys.display.default_limit', 12),
            'show_poster' => config('ddys.display.show_poster', true) ? '1' : '0',
            'show_rating' => config('ddys.display.show_rating', true) ? '1' : '0',
            'show_summary' => config('ddys.display.show_summary', true) ? '1' : '0',
        ], $atts);
    }
}


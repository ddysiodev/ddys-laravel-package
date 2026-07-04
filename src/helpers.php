<?php

use Ddys\Laravel\Client;
use Ddys\Laravel\Renderer;
use Ddys\Laravel\Shortcode;

if (!function_exists('ddys_client')) {
    function ddys_client(): Client
    {
        return app('ddys.client');
    }
}

if (!function_exists('ddys_render')) {
    function ddys_render(string $view, array $params = []): string
    {
        return app(Renderer::class)->render($view, $params);
    }
}

if (!function_exists('ddys_shortcode')) {
    function ddys_shortcode(string $content): string
    {
        return app(Shortcode::class)->parse($content);
    }
}

if (!function_exists('ddys_request_form')) {
    function ddys_request_form(array $params = []): string
    {
        return app(Renderer::class)->renderRequestForm($params);
    }
}


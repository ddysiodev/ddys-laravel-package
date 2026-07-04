# DDYS Laravel Package

English | [中文](README.zh-CN.md)

Official Laravel package for the [DDYS](https://ddys.io/) API. It adds frontend pages, Blade components, Blade directives, text shortcodes, a local JSON proxy, caching, diagnostics, Artisan commands, and a server-side request form for Laravel applications.

- GitHub repository: [ddysiodev/ddys-laravel-package](https://github.com/ddysiodev/ddys-laravel-package)
- Composer package: `ddysiodev/ddys-laravel-package`
- Package namespace: `Ddys\Laravel`
- Target: Laravel 10 / 11 / 12 / 13, PHP 8.1+
- License: MIT

## Features

- Laravel package auto-discovery through `extra.laravel.providers`.
- `DdysServiceProvider` with config merge, routes, views, Blade directives, Blade component namespace, publish groups, and Artisan commands.
- Full DDYS API client for movies, latest, hot, search, suggestions, calendar, movie details, sources, related items, comments, collections, shares, requests, activities, users, dictionaries, authenticated request submission, comments, reports, follows, and account data.
- Frontend pages for all 20 public views under `/ddys`.
- Local JSON proxy at `/ddys-api/{route}` with route and parameter allow-lists.
- Blade usage: `@ddys('latest', ['limit' => 12])`, `@ddysLatest(['limit' => 12])`, `<x-ddys::widget view="latest" :params="['limit' => 12]" />`.
- Text shortcode parser for all 21 `[ddys_*]` shortcodes.
- Server-side request form with Laravel CSRF, Validator rules, honeypot field, IP rate limiting, and API Key kept server-side.
- Cache integration with TTL buckets, tag-aware clearing when the cache driver supports tags, and fallback key-index clearing when it does not.
- Publishable config, Blade views, CSS, JavaScript, and icons.
- Artisan commands for install, diagnostics, route listing, and cache clearing.

## Installation

```bash
composer require ddysiodev/ddys-laravel-package
```

Install from GitHub before Packagist availability:

```bash
composer config repositories.ddys-laravel-package vcs https://github.com/ddysiodev/ddys-laravel-package
composer require ddysiodev/ddys-laravel-package:^0.1
```

Publish config and assets:

```bash
php artisan ddys:install
php artisan ddys:install --views
```

Then review `config/ddys.php` and set environment variables as needed:

```env
DDYS_API_BASE_URL=https://ddys.io/api/v1
DDYS_SITE_BASE_URL=https://ddys.io
DDYS_API_KEY=
DDYS_REQUEST_FORM_ENABLED=false
DDYS_DIAGNOSTICS_ENABLED=false
```

## Routes

Default route prefix is `/ddys`:

```text
/ddys
/ddys/latest
/ddys/hot
/ddys/movies
/ddys/search?q=interstellar
/ddys/suggest?q=interstellar
/ddys/calendar?year=2026&month=7
/ddys/movie/this-tempting-madness
/ddys/movie/this-tempting-madness/sources
/ddys/movie/this-tempting-madness/related
/ddys/movie/this-tempting-madness/comments
/ddys/collections
/ddys/collection/best-sci-fi
/ddys/shares
/ddys/share/1
/ddys/requests
/ddys/activities
/ddys/user/demo
/ddys/types
/ddys/genres
/ddys/regions
```

Local JSON proxy:

```text
/ddys-api/latest?limit=12
/ddys-api/movie?slug=this-tempting-madness
/ddys-api/share?id=1
```

## Blade

```blade
@ddys('latest', ['limit' => 12])
@ddysLatest(['limit' => 12])
@ddysMovie(['slug' => 'this-tempting-madness'])
@ddysRequestForm()

<x-ddys::widget view="hot" :params="['limit' => 10]" />
<x-ddys::latest :params="['limit' => 12]" />
<x-ddys::hot :params="['limit' => 10]" />
<x-ddys::movies :params="['type' => 'movie']" />
<x-ddys::search />
<x-ddys::request-form />
```

## Shortcodes

```text
[ddys_movies type="movie" per_page="24"]
[ddys_latest type="movie" limit="12"]
[ddys_hot limit="10"]
[ddys_search]
[ddys_suggest q="interstellar"]
[ddys_calendar year="2026" month="7"]
[ddys_movie slug="this-tempting-madness"]
[ddys_sources slug="this-tempting-madness"]
[ddys_related slug="this-tempting-madness"]
[ddys_comments slug="this-tempting-madness" per_page="20"]
[ddys_collections per_page="10"]
[ddys_collection slug="best-sci-fi"]
[ddys_shares per_page="10"]
[ddys_share id="1"]
[ddys_requests per_page="10"]
[ddys_activities per_page="10"]
[ddys_user username="demo"]
[ddys_types]
[ddys_genres]
[ddys_regions]
[ddys_request_form]
```

## PHP Usage

```php
use Ddys\Laravel\Facades\Ddys;

$latest = Ddys::latest(['limit' => 12]);
$movie = Ddys::movie('this-tempting-madness');

echo ddys_render('hot', ['limit' => 10]);
echo ddys_shortcode('[ddys_latest limit="6"]');
```

## Artisan Commands

```bash
php artisan ddys:install
php artisan ddys:install --views
php artisan ddys:test
php artisan ddys:routes
php artisan ddys:clear-cache
```

## Local Checks

```powershell
node tools/check.mjs
node --test tests/*.test.mjs
powershell -ExecutionPolicy Bypass -File tools/build-package.ps1
```

The check covers Composer metadata, Laravel auto-discovery, ServiceProvider behavior, routes, controllers, client methods, Blade directives/components, shortcodes, renderer behavior, request form security, cache fallback, commands, assets, icons, documentation, package safety, and forbidden sensitive text.

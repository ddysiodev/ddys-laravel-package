<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DDYS Diagnostics</title>
    {!! app('ddys.renderer')->assets() !!}
</head>
<body>
    <main class="ddys-laravel-page">
        <h1 class="ddys-laravel-page-title">DDYS Diagnostics</h1>
        <section class="ddys-laravel ddys-laravel-layout-list">
            <div class="ddys-laravel-description">
                <p>API Base URL: {{ $config['api_base_url'] ?? '' }}</p>
                <p>Site Base URL: {{ $config['site_base_url'] ?? '' }}</p>
                <p>API Key: {{ empty($config['api_key']) ? 'not configured' : 'configured' }}</p>
                <p>Shortcodes: {{ count($shortcodes) }}</p>
                <p>Views: {{ count($views) }}</p>
            </div>
            <form method="post" action="{{ $testUrl }}" data-ddys-laravel-request-form>
                @csrf
                <button type="submit">Test connection</button>
                <p class="ddys-laravel-status" role="status"></p>
            </form>
            <form method="post" action="{{ $cacheUrl }}" data-ddys-laravel-request-form>
                @csrf
                <button type="submit">Clear cache</button>
                <p class="ddys-laravel-status" role="status"></p>
            </form>
            <h2>Shortcodes</h2>
            <div class="ddys-laravel-tags">
                @foreach ($shortcodes as $example)
                    <span><code>{{ $example }}</code></span>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>

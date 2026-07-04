<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    {!! app('ddys.renderer')->assets() !!}
</head>
<body>
    <main class="ddys-laravel-page ddys-laravel-page-{{ $view }}">
        <h1 class="ddys-laravel-page-title">{{ $title }}</h1>
        {!! $tabs !!}
        {!! $content !!}
    </main>
</body>
</html>


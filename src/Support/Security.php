<?php

namespace Ddys\Laravel\Support;

final class Security
{
    public static function scalar(mixed $value, string $default = ''): string
    {
        if (is_array($value) || is_object($value)) {
            return $default;
        }

        return trim(str_replace("\0", '', (string) $value));
    }

    public static function h(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function attr(mixed $value): string
    {
        return self::h($value);
    }

    public static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(self::scalar($value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function intRange(mixed $value, int $fallback, int $min, int $max): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        $value = (int) $value;

        return max($min, min($max, $value));
    }

    public static function choice(mixed $value, array $allowed, string $fallback): string
    {
        $value = strtolower(self::scalar($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public static function normalizeBaseUrl(mixed $value, string $fallback): string
    {
        $value = self::scalar($value);

        if ($value === '' || !preg_match('#^https?://#i', $value)) {
            return $fallback;
        }

        $parts = parse_url($value);

        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass']) || !empty($parts['query']) || !empty($parts['fragment'])) {
            return $fallback;
        }

        return rtrim($value, '/');
    }

    public static function safeMediaUrl(mixed $value): string
    {
        $value = self::scalar($value);

        return preg_match('#^https?://#i', $value) ? $value : '';
    }

    public static function cleanQuery(array $params): array
    {
        $out = [];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $value = is_scalar($value) ? self::scalar($value) : '';

            if ($value !== '') {
                $out[$key] = $value;
            }
        }

        ksort($out);

        return $out;
    }

    public static function normalizeQueryValue(string $key, mixed $value): mixed
    {
        $value = self::scalar($value);

        if ($value === '') {
            return '';
        }

        if ($key === 'limit') {
            return self::intRange($value, 12, 1, (int) config('ddys.security.max_limit', 50));
        }

        if ($key === 'per_page') {
            return self::intRange($value, 12, 1, (int) config('ddys.security.max_per_page', 50));
        }

        if ($key === 'page') {
            return self::intRange($value, 1, 1, (int) config('ddys.security.max_page', 999));
        }

        if ($key === 'year') {
            return preg_match('/^\d{4}$/', $value) && (int) $value >= 1900 && (int) $value <= 2099 ? (int) $value : '';
        }

        if ($key === 'month') {
            return preg_match('/^\d{1,2}$/', $value) && (int) $value >= 1 && (int) $value <= 12 ? (int) $value : '';
        }

        if ($key === 'q') {
            return self::substr($value, 0, 120);
        }

        if (in_array($key, ['type', 'genre', 'region', 'sort'], true)) {
            return self::substr($value, 0, 64);
        }

        return self::substr($value, 0, 255);
    }

    public static function buildQuery(array $source, array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $source)) {
                continue;
            }

            $value = self::normalizeQueryValue($key, $source[$key]);

            if ($value !== '' && $value !== null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public static function substr(mixed $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr((string) $value, $start, $length, 'UTF-8') : substr((string) $value, $start, $length);
    }
}


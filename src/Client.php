<?php

namespace Ddys\Laravel;

use Ddys\Laravel\Exceptions\DdysException;
use Ddys\Laravel\Support\Security;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Throwable;

class Client
{
    public const VERSION = '0.1.0';
    public const DEFAULT_BASE_URL = 'https://ddys.io/api/v1';

    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;
    protected string $userAgent;

    public function __construct(
        protected HttpFactory $http,
        protected CacheStore $cache,
        protected array $config = []
    ) {
        $this->config = $config ?: (array) config('ddys', []);
        $this->baseUrl = Security::normalizeBaseUrl($this->config['api_base_url'] ?? self::DEFAULT_BASE_URL, self::DEFAULT_BASE_URL);
        $this->apiKey = trim((string) ($this->config['api_key'] ?? ''));
        $this->timeout = Security::intRange($this->config['timeout'] ?? 12, 12, 1, 60);
        $this->retryTimes = Security::intRange($this->config['retry_times'] ?? 1, 1, 0, 5);
        $this->retrySleep = Security::intRange($this->config['retry_sleep'] ?? 150, 150, 0, 3000);
        $this->userAgent = (string) ($this->config['user_agent'] ?? 'ddys-laravel-package/' . self::VERSION);
    }

    public function request(string $method, string $path, array $query = [], ?array $body = null, array $options = []): array
    {
        $method = strtoupper($method);
        $path = '/' . ltrim($path, '/');
        $query = Security::cleanQuery($query);
        $auth = !empty($options['auth']);

        if ($auth && $this->apiKey === '') {
            throw new DdysException('DDYS API Key is not configured.', 401, $method, $path);
        }

        $useCache = $method === 'GET' && empty($options['no_cache']);
        $cacheKey = $this->cache->key($method, $this->baseUrl, $path, $query);
        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $pending = $this->http
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout)
                ->withUserAgent($this->userAgent)
                ->retry($this->retryTimes, $this->retrySleep);

            if ($auth) {
                $pending = $pending->withToken($this->apiKey);
            }

            $response = $method === 'GET'
                ? $pending->get($this->baseUrl . $path, $query)
                : $pending->send($method, $this->baseUrl . $path, ['query' => $query, 'json' => $body ?? []]);

            if ($response->failed()) {
                throw new DdysException('DDYS API HTTP ' . $response->status() . '.', $response->status(), $method, $path, $response->json() ?: $response->body());
            }

            $json = $response->json();
        } catch (RequestException $e) {
            throw new DdysException($e->getMessage(), $e->response?->status() ?? 0, $method, $path, $e->response?->json(), $e);
        } catch (DdysException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DdysException('DDYS API request failed: ' . $e->getMessage(), 0, $method, $path, null, $e);
        }

        if (!is_array($json)) {
            throw new DdysException('DDYS API returned invalid JSON.', 502, $method, $path, $json);
        }

        if (array_key_exists('success', $json) && $json['success'] === false) {
            throw new DdysException((string) ($json['message'] ?? 'DDYS API request failed.'), 502, $method, $path, $json);
        }

        if ($useCache) {
            $ttl = array_key_exists('cache_ttl', $options) ? Security::intRange($options['cache_ttl'], 0, 0, 604800) : $this->cache->ttlForPath($path);
            $this->cache->set($cacheKey, $json, $ttl);
        }

        return $json;
    }

    public function get(string $path, array $query = [], array $options = []): array
    {
        return $this->request('GET', $path, $query, null, $options);
    }

    public function post(string $path, array $body = [], array $options = []): array
    {
        return $this->request('POST', $path, [], $body, $options);
    }

    public function delete(string $path, array $options = []): array
    {
        return $this->request('DELETE', $path, [], null, $options);
    }

    public function data(string $path, array $query = [], array $options = []): mixed
    {
        $payload = $this->get($path, $query, $options);

        return $payload['data'] ?? $payload;
    }

    public function paginated(string $path, array $query = [], array $options = []): array
    {
        $payload = $this->get($path, $query, $options);

        return [
            'data' => is_array($payload['data'] ?? null) ? $payload['data'] : [],
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
        ];
    }

    public function movies(array $params = []): array { return $this->paginated('/movies', Security::buildQuery($params, ['type', 'genre', 'region', 'year', 'sort', 'page', 'per_page'])); }
    public function latest(array $params = []): mixed { return $this->data('/latest', Security::buildQuery($params, ['type', 'genre', 'region', 'year', 'limit'])); }
    public function hot(array $params = []): mixed { return $this->data('/hot', Security::buildQuery($params, ['type', 'genre', 'region', 'limit'])); }
    public function search(array $params = []): array { return $this->paginated('/search', Security::buildQuery($params, ['q', 'type', 'page', 'per_page'])); }
    public function suggest(string $q, array $params = []): mixed { return $this->data('/suggest', Security::buildQuery(array_merge($params, ['q' => $q]), ['q', 'limit'])); }
    public function calendar(array $params = []): mixed { return $this->data('/calendar', Security::buildQuery($params, ['year', 'month'])); }
    public function movie(string $slug): mixed { return $this->data('/movies/' . rawurlencode(Security::scalar($slug))); }
    public function sources(string $slug): mixed { return $this->data('/movies/' . rawurlencode(Security::scalar($slug)) . '/sources'); }
    public function related(string $slug): mixed { return $this->data('/movies/' . rawurlencode(Security::scalar($slug)) . '/related'); }
    public function comments(string $slug, array $params = []): array { return $this->paginated('/movies/' . rawurlencode(Security::scalar($slug)) . '/comments', Security::buildQuery($params, ['page', 'per_page'])); }
    public function collections(array $params = []): array { return $this->paginated('/collections', Security::buildQuery($params, ['page', 'per_page'])); }
    public function collection(string $slug, array $params = []): mixed { return $this->data('/collections/' . rawurlencode(Security::scalar($slug)), Security::buildQuery($params, ['page', 'per_page'])); }
    public function shares(array $params = []): array { return $this->paginated('/shares', Security::buildQuery($params, ['page', 'per_page'])); }
    public function share(int|string $id): mixed { return $this->data('/shares/' . (int) $id); }
    public function requests(array $params = []): array { return $this->paginated('/requests', Security::buildQuery($params, ['page', 'per_page'])); }
    public function activities(array $params = []): array { return $this->paginated('/activities', Security::buildQuery($params, ['type', 'page', 'per_page'])); }
    public function user(string $username): mixed { return $this->data('/user/' . rawurlencode(Security::scalar($username))); }
    public function types(): mixed { return $this->data('/types'); }
    public function genres(): mixed { return $this->data('/genres'); }
    public function regions(): mixed { return $this->data('/regions'); }
    public function me(): mixed { return $this->unwrap($this->get('/me', [], ['auth' => true, 'no_cache' => true])); }
    public function createRequest(array $input): mixed { return $this->unwrap($this->post('/requests', $input, ['auth' => true, 'no_cache' => true])); }
    public function createComment(array $input): mixed { return $this->unwrap($this->post('/comments', $input, ['auth' => true, 'no_cache' => true])); }
    public function deleteComment(int|string $id): mixed { return $this->unwrap($this->delete('/comments/' . (int) $id, ['auth' => true, 'no_cache' => true])); }
    public function reportInvalidResource(array $input): mixed { return $this->unwrap($this->post('/report', $input, ['auth' => true, 'no_cache' => true])); }
    public function follow(string $username): mixed { return $this->setFollow($username, 'follow'); }
    public function unfollow(string $username): mixed { return $this->setFollow($username, 'unfollow'); }

    public function setFollow(string $username, string $action): mixed
    {
        return $this->unwrap($this->post('/follow', [
            'username' => Security::scalar($username),
            'action' => Security::choice($action, ['follow', 'unfollow'], 'follow'),
        ], ['auth' => true, 'no_cache' => true]));
    }

    public function proxy(string $route, array $query = []): array
    {
        $route = strtolower(Security::scalar($route, 'latest'));
        $allowed = (array) config('ddys.proxy.allow_routes', []);

        if (!in_array($route, $allowed, true)) {
            throw new DdysException('Route is not allowed.', 403, 'GET', '/proxy');
        }

        $path = $this->proxyPath($route, $query);
        if ($path === '') {
            throw new DdysException('Invalid route parameters.', 400, 'GET', '/proxy');
        }

        return $this->get($path, Security::buildQuery($query, ['type', 'genre', 'region', 'year', 'sort', 'page', 'per_page', 'limit', 'q', 'month']));
    }

    protected function proxyPath(string $route, array $query): string
    {
        $slug = Security::scalar($query['slug'] ?? '');
        $id = Security::scalar($query['id'] ?? '');
        $username = Security::scalar($query['username'] ?? '');

        return match ($route) {
            'movies' => '/movies',
            'latest' => '/latest',
            'hot' => '/hot',
            'search' => '/search',
            'suggest' => '/suggest',
            'calendar' => '/calendar',
            'movie' => $slug === '' ? '' : '/movies/' . rawurlencode($slug),
            'sources' => $slug === '' ? '' : '/movies/' . rawurlencode($slug) . '/sources',
            'related' => $slug === '' ? '' : '/movies/' . rawurlencode($slug) . '/related',
            'comments' => $slug === '' ? '' : '/movies/' . rawurlencode($slug) . '/comments',
            'collections' => '/collections',
            'collection' => $slug === '' ? '' : '/collections/' . rawurlencode($slug),
            'shares' => '/shares',
            'share' => preg_match('/^[1-9][0-9]*$/', $id) ? '/shares/' . (int) $id : '',
            'requests' => '/requests',
            'activities' => '/activities',
            'user' => $username === '' ? '' : '/user/' . rawurlencode($username),
            'types' => '/types',
            'genres' => '/genres',
            'regions' => '/regions',
            default => '',
        };
    }

    protected function unwrap(array $payload): mixed
    {
        return $payload['data'] ?? $payload;
    }
}

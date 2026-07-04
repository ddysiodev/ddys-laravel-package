<?php

namespace Ddys\Laravel;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Throwable;

class CacheStore
{
    public function __construct(
        protected CacheFactory $cache,
        protected array $config = []
    ) {}

    public function key(string $method, string $base, string $path, array $params): string
    {
        ksort($params);

        return $this->prefix() . ':' . sha1(strtoupper($method) . '|' . $base . '|' . $path . '|' . http_build_query($params, '', '&'));
    }

    public function get(string $key): mixed
    {
        return $this->store()->get($key);
    }

    public function set(string $key, mixed $value, int $ttl): bool
    {
        if ($ttl <= 0) {
            return false;
        }

        $this->rememberKey($key, $ttl);

        return $this->store()->put($key, $value, $ttl);
    }

    public function clear(): void
    {
        $repo = $this->repository();

        if ($this->supportsTags($repo)) {
            try {
                $repo->tags([$this->tag()])->flush();
            } catch (Throwable) {
            }
        }

        $indexKey = $this->prefix() . ':keys';
        $keys = $repo->get($indexKey, []);
        if (is_array($keys)) {
            foreach (array_keys($keys) as $key) {
                $repo->forget($key);
            }
        }
        $repo->forget($indexKey);
    }

    public function ttlForPath(string $path): int
    {
        if (preg_match('#^/(types|genres|regions|calendar)$#', $path)) {
            return (int) config('ddys.cache.dictionary_ttl', 86400);
        }

        if (preg_match('#^/(latest|hot)$#', $path)) {
            return (int) config('ddys.cache.fresh_ttl', 300);
        }

        if (preg_match('#^/(movies/[^/]+|movies/[^/]+/sources|movies/[^/]+/related|collections/[^/]+|shares/[0-9]+)$#', $path)) {
            return (int) config('ddys.cache.detail_ttl', 1800);
        }

        if (preg_match('#^/(movies/[^/]+/comments|suggest|shares|requests|activities|user/)#', $path)) {
            return (int) config('ddys.cache.community_ttl', 120);
        }

        if (preg_match('#^/(movies|search|collections)#', $path)) {
            return (int) config('ddys.cache.list_ttl', 600);
        }

        return (int) config('ddys.cache.default_ttl', 300);
    }

    protected function repository(): Repository
    {
        $store = config('ddys.cache.store');

        return $store ? $this->cache->store($store) : $this->cache->store();
    }

    protected function store(): mixed
    {
        $repo = $this->repository();

        if ($this->supportsTags($repo)) {
            try {
                return $repo->tags([$this->tag()]);
            } catch (Throwable) {
            }
        }

        return $repo;
    }

    protected function rememberKey(string $key, int $ttl): void
    {
        $repo = $this->repository();
        $indexKey = $this->prefix() . ':keys';
        $keys = $repo->get($indexKey, []);
        $keys = is_array($keys) ? $keys : [];
        $keys[$key] = time();
        $repo->put($indexKey, $keys, max($ttl, 86400));
    }

    protected function supportsTags(Repository $repo): bool
    {
        if (!config('ddys.cache.tags', true) || !method_exists($repo, 'tags')) {
            return false;
        }

        try {
            $repo->tags([$this->tag()]);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function prefix(): string
    {
        return (string) config('ddys.cache.prefix', 'ddys_laravel');
    }

    protected function tag(): string
    {
        return $this->prefix();
    }
}

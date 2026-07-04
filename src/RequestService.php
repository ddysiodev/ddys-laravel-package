<?php

namespace Ddys\Laravel;

use Ddys\Laravel\Exceptions\DdysException;
use Ddys\Laravel\Support\Security;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

class RequestService
{
    public function __construct(
        protected Client $client,
        protected ValidatorFactory $validator,
        protected RateLimiter $limiter
    ) {}

    public function submit(array $input, string $identity): mixed
    {
        if (!config('ddys.request_form.enabled', false)) {
            throw new DdysException('DDYS request form is disabled.', 403, 'POST', '/requests');
        }

        $honeypot = (string) config('ddys.request_form.honeypot_field', 'ddys_website');
        if (Security::scalar($input[$honeypot] ?? '') !== '') {
            throw new DdysException('Invalid submission.', 400, 'POST', '/requests');
        }

        $interval = Security::intRange(config('ddys.request_form.rate_limit_seconds', 60), 60, 10, 3600);
        $key = 'ddys:request:' . sha1($identity);
        if ($this->limiter->tooManyAttempts($key, 1)) {
            throw new DdysException('Too many submissions. Please try again later.', 429, 'POST', '/requests');
        }
        $this->limiter->hit($key, $interval);

        $payload = $this->normalize($input);

        return $this->client->createRequest($payload);
    }

    public function normalize(array $input): array
    {
        $validator = $this->validator->make($input, [
            'title' => ['required', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'between:1900,2099'],
            'type' => ['nullable', 'in:movie,series,variety,anime'],
            'description' => ['nullable', 'string', 'max:1000'],
            'douban_id' => ['nullable', 'regex:/^\d{1,20}$/'],
            'imdb_id' => ['nullable', 'regex:/^tt\d{1,20}$/i'],
        ]);

        if ($validator->fails()) {
            throw new DdysException($validator->errors()->first(), 422, 'POST', '/requests', $validator->errors()->toArray());
        }

        $data = $validator->validated();

        return array_filter([
            'title' => Security::substr(Security::scalar($data['title'] ?? ''), 0, 255),
            'year' => $data['year'] ?? '',
            'type' => Security::choice($data['type'] ?? '', ['movie', 'series', 'variety', 'anime', ''], ''),
            'description' => Security::substr(Security::scalar($data['description'] ?? ''), 0, 1000),
            'douban_id' => Security::scalar($data['douban_id'] ?? ''),
            'imdb_id' => Security::scalar($data['imdb_id'] ?? ''),
            'site' => config('app.name', 'Laravel'),
        ], fn ($value) => $value !== '' && $value !== null);
    }
}

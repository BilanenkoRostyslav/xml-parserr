<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis as RedisFacade;

class RedisService
{
    public function getOfferIdsFromFilters(): array
    {
        return RedisFacade::smembers(Config::get('redis.active_filters_key'));
    }

    public function createUnion(string $key, string ...$other_keys)
    {
        return RedisFacade::sUnionStore($key, ...$other_keys);
    }

    public function setExpireValue(string $key, int $seconds): void
    {
        RedisFacade::expire($key, $seconds);
    }

    public function sinterStore(array|string $key, string ...$other_keys): \Redis|int|false
    {
        return RedisFacade::sInterStore($key, ...$other_keys);
    }

    public function sinterCard(array $keys): int
    {
        return RedisFacade::sintercard($keys);
    }

    public function scard(string $key): int
    {
        return RedisFacade::sCard($key);
    }

    public function setExists(string $key): bool
    {
        return RedisFacade::exists($key);
    }
}
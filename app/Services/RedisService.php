<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis as RedisFacade;

class RedisService
{
    public function getOfferIdsFromFilters(array|string $key, string ...$other_keys): array
    {
        $ids = RedisFacade::sInter($key, ...$other_keys);
        return !$ids ? [] : $ids;
    }
}
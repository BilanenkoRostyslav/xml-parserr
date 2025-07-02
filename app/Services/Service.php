<?php

namespace App\Services;

use App\Collections\FilterDTOCollection;
use App\Collections\FilterResponseDTOCollection;
use App\Collections\FilterValuesResponseDTOCollection;
use App\Collections\OfferDTOCollection;
use App\DTO\FilterDTO;
use App\DTO\FilterResponseDTO;
use App\DTO\FilterValueResponseDTO;
use App\DTO\GetOffersDTO;
use App\DTO\MetaDTO;
use App\DTO\OfferDTO;
use App\DTO\OfferResponseDTO;
use App\Repositories\Abstracts\MainRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class Service
{

    public function __construct(
        private readonly MainRepositoryInterface $mainRepository,
        private RedisService                     $redis,
    )
    {
    }

    public function getOffers(GetOffersDTO $getOffersDTO): OfferResponseDTO
    {
        $otherFilters = $getOffersDTO->getFilters()->getActiveFilterRedisKeys();
        $this->createSetActiveFilters($otherFilters);
        $ids = $this->redis->getOfferIdsFromFilters();
        $offset = ($getOffersDTO->getPage() - 1) * $getOffersDTO->getLimit();

        $offers = $this->mainRepository->getOffersByIds(
            $ids,
            $getOffersDTO->getLimit(),
            $offset,
            $getOffersDTO->getOrderAttribute(),
            $getOffersDTO->getOrder()
        );
        $offers = $offers->map(function ($offer) {
            return new OfferDTO($offer['id'], $offer['name'], $offer['price'], $offer['description']);
        });
        $total = count($ids);
        $lastPage = ceil($total / $getOffersDTO->getLimit());
        $meta = new MetaDTO($getOffersDTO->getPage(), $lastPage, $total, $getOffersDTO->getLimit());

        return new OfferResponseDTO(new OfferDTOCollection($offers), $meta);
    }

    public function getFilters(FilterDTOCollection $filters): FilterResponseDTOCollection
    {
        $filtersData = $this->mainRepository->getFilters();
        $filtersData = $filtersData->groupBy('slug');
        $activeFilters = $filters->getActiveFilterRedisKeys();
        $this->createSetActiveFilters($activeFilters);
        $mappedFilters = $this->mapFilters($filtersData, $activeFilters);
        return new FilterResponseDTOCollection($mappedFilters);
    }

    private function createSetActiveFilters(array $activeFilters): void
    {
        $expireTtl = Config::get('redis.expire_ttl');
        $activeKey = Config::get('redis.active_filters_key');

        $tempKeys = [];
        foreach ($activeFilters as $slug => $filterKeys) {
            $prefix = Config::get('redis.union_prefix');
            $tempKey = "$prefix:$slug";
            $this->redis->createUnion($tempKey, ...$filterKeys);
            $this->redis->setExpireValue($tempKey, $expireTtl);
            $tempKeys[] = $tempKey;
        }

        $this->redis->sinterStore($activeKey, ...$tempKeys);
        $this->redis->setExpireValue($activeKey, $expireTtl);
    }

    private function mapFilters(Collection $filtersData, array $activeFilters): array
    {
        return $filtersData->map(function ($filter) use ($activeFilters) {
            $slug = $filter[0]['slug'];
            $name = $filter[0]['name'];

            $valueResponseDTO = $this->mapFilterValues($filter, $activeFilters, $slug);

            return new FilterResponseDTO($name, $slug, new FilterValuesResponseDTOCollection($valueResponseDTO));
        })->values()->all();
    }

    private function mapFilterValues(Collection $filterValues, array $activeFilters, string $slug): array
    {
        return $filterValues->map(function ($item) use ($activeFilters, $slug) {
            [$count, $isActive] = $this->calculateCountAndActive($item, $activeFilters, $slug);

            return new FilterValueResponseDTO($item['value'], $count, $isActive);
        })->all();
    }

    private function calculateCountAndActive(array $item, array $activeFilters, string $slug): array
    {
        $filterKey = "filter:{$item['slug']}:{$item['value']}";
        $activeFiltersKey = Config::get("redis.active_filters_key");
        $unionPrefixBase = Config::get("redis.union_prefix");
        $expireTTL = Config::get("redis.expire_ttl");

        if (empty($activeFilters)) {
            return [$this->redis->scard($filterKey), false];
        }

        $key = $filterKey;

        if (array_key_exists($slug, $activeFilters)) {
            $isActive = in_array($key, $activeFilters[$slug]);
            $unionPrefix = "$unionPrefixBase:$slug";

            if (!$isActive) {
                $activeFiltersWithoutSlug = "{$activeFiltersKey}:without:{$slug}";

                $activeUnionKeysWithoutCurrent = [];
                foreach (array_keys($activeFilters) as $activeFilter) {
                    if ($activeFilter !== $slug) {
                        $activeUnionKeysWithoutCurrent[] = "$unionPrefixBase:$activeFilter";
                    }
                }

                if (count($activeUnionKeysWithoutCurrent) > 0) {
                    $this->redis->sinterStore($activeFiltersWithoutSlug, ...$activeUnionKeysWithoutCurrent);
                    $this->redis->setExpireValue($activeFiltersWithoutSlug, $expireTTL);

                    $this->redis->createUnion($unionPrefix, $key, ...$activeFilters[$slug]);
                    $this->redis->setExpireValue($unionPrefix, $expireTTL);

                    $count = $this->redis->sinterCard([$activeFiltersWithoutSlug, $unionPrefix]);
                } else {
                    $this->redis->createUnion($unionPrefix, $key, ...$activeFilters[$slug]);
                    $this->redis->setExpireValue($unionPrefix, $expireTTL);

                    $count = $this->redis->scard($unionPrefix);
                }
            } else {
                $count = $this->redis->scard($activeFiltersKey);
            }
        } else {
            $isActive = false;
            $count = $this->redis->sinterCard([$activeFiltersKey, $key]);
        }

        return [$count, $isActive];
    }

}
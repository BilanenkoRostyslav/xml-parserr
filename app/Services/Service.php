<?php

namespace App\Services;

use App\Collections\FilterDTOCollection;
use App\Collections\FilterResponseDTOCollection;
use App\Collections\FilterValuesResponseDTOCollection;
use App\Collections\OfferDTOCollection;
use App\DTO\BuildFiltersWithoutSlugDTO;
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
use Illuminate\Support\Facades\Log;

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
        return $filtersData->map(function ($filter) use ($activeFilters, &$total) {
            $slug = $filter[0]['slug'];
            $name = $filter[0]['name'];
            $valueResponseDTO = $this->mapFilterValues($filter, $activeFilters, $slug);
            return new FilterResponseDTO($name, $slug, new FilterValuesResponseDTOCollection($valueResponseDTO));
        })->values()->all();
    }

    private function mapFilterValues(Collection $filterValues, array $activeFilters, string $slug): array
    {
        $activeFiltersKey = Config::get("redis.active_filters_key");
        $unionPrefixBase = Config::get("redis.union_prefix");

        $commands = [];
        $dto = new BuildFiltersWithoutSlugDTO($activeFilters, $slug, $activeFiltersKey, $unionPrefixBase);

        $activeFiltersWithoutSlug = $this->buildActiveFiltersWithoutSlug($commands, $dto);
        foreach ($filterValues as $i => $item) {
            $itemCommands = $this->buildFilterValueCommands(
                $item, $i, $dto, $activeFiltersWithoutSlug
            );
            $commands = array_merge($commands, $itemCommands);
        }

        $rawResults = $this->redis->pipeline(function ($pipe) use ($commands) {
            foreach ($commands as $cmd) {
                $method = array_shift($cmd['cmd']);
                $pipe->$method(...$cmd['cmd']);
            }
        });

        return $this->parsePipelineResults($commands, $filterValues, $rawResults);
    }

    private function buildActiveFiltersWithoutSlug(array &$commands, BuildFiltersWithoutSlugDTO $dto): ?string
    {
        $activeFiltersWithoutSlug = null;
        $activeUnionKeysWithoutCurrent = [];
        $slug = $dto->getSlug();
        $activeFilters = $dto->getActiveFilters();
        $unionPrefixBase = $dto->getUnionPrefixBase();
        $activeFiltersKey = $dto->getActiveFiltersKey();
        $expireTTL = Config::get("redis.expire_ttl");

        if (array_key_exists($slug, $activeFilters)) {
            foreach (array_keys($activeFilters) as $activeFilter) {
                if ($activeFilter !== $slug) {
                    $activeUnionKeysWithoutCurrent[] = "$unionPrefixBase:$activeFilter";
                }
            }
            if (count($activeUnionKeysWithoutCurrent) > 0) {
                $activeFiltersWithoutSlug = "{$activeFiltersKey}:without:{$slug}";
                $commands[] = ['cmd' => ['sinterstore', $activeFiltersWithoutSlug, ...$activeUnionKeysWithoutCurrent], 'need' => false];
                $commands[] = ['cmd' => ['expire', $activeFiltersWithoutSlug, $expireTTL], 'need' => false];
            }
        }

        return $activeFiltersWithoutSlug;
    }

    private function buildFilterValueCommands(array $item, int $index, BuildFiltersWithoutSlugDTO $dto, ?string $activeFiltersWithoutSlug): array
    {
        $commands = [];
        $filterKey = "filter:{$item['slug']}:{$item['value']}";
        $expireTTL = Config::get("redis.expire_ttl");
        $slug = $dto->getSlug();
        $unionPrefixBase = $dto->getUnionPrefixBase();
        $activeFiltersKey = $dto->getActiveFiltersKey();
        $activeFilters = $dto->getActiveFilters();

        if (empty($activeFilters)) {
            $commands[] = ['cmd' => ['scard', $filterKey], 'need' => true, 'index' => $index, 'isActive' => false];
        } else {
            if (array_key_exists($slug, $activeFilters)) {
                $isActive = in_array($filterKey, $activeFilters[$slug]);
                $unionKey = "$unionPrefixBase:$slug:{$item['value']}";

                if (!$isActive) {
                    $commands[] = ['cmd' => ['sunionstore', $unionKey, $filterKey, ...$activeFilters[$slug]], 'need' => false];
                    $commands[] = ['cmd' => ['expire', $unionKey, $expireTTL], 'need' => false];

                    if ($activeFiltersWithoutSlug) {
                        $commands[] = ['cmd' => ['sintercard', [$activeFiltersWithoutSlug, $unionKey]], 'need' => true, 'index' => $index, 'isActive' => false];
                    } else {
                        $commands[] = ['cmd' => ['scard', $unionKey], 'need' => true, 'index' => $index, 'isActive' => false];
                    }
                } else {
                    $commands[] = ['cmd' => ['scard', $activeFiltersKey], 'need' => true, 'index' => $index, 'isActive' => true];
                }
            } else {
                $commands[] = ['cmd' => ['sintercard', [$activeFiltersKey, $filterKey]], 'need' => true, 'index' => $index, 'isActive' => false];
            }
        }

        return $commands;
    }

    private function parsePipelineResults(array $commands, Collection $filterValues, array $rawResults): array
    {
        $final = [];
        foreach ($commands as $idx => $cmd) {
            if (!empty($cmd['need'])) {
                $index = $cmd['index'];
                $item = $filterValues[$index];
                $count = $rawResults[$idx];
                $isActive = $cmd['isActive'] ?? false;

                $final[] = new FilterValueResponseDTO($item['value'], $count, $isActive);
            }
        }

        return $final;
    }
}
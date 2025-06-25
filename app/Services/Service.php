<?php

namespace App\Services;

use App\Collections\FilterDTOCollection;
use App\Collections\FilterResponseDTOCollection;
use App\Collections\FilterValuesResponseDTOCollection;
use App\Collections\OfferDTOCollection;
use App\DTO\FilterDTO;
use App\DTO\FilterResponseDTO;
use App\DTO\FilterValuesResponseDTO;
use App\DTO\GetOffersDTO;
use App\DTO\OfferDTO;
use App\Repositories\Abstracts\MainRepositoryInterface;
use Illuminate\Support\Collection;

class Service
{

    public function __construct(
        private readonly MainRepositoryInterface $mainRepository,
        private RedisService                     $redis,
    )
    {
    }

    public function getOffers(GetOffersDTO $getOffersDTO): Collection
    {
        $otherFilters = $getOffersDTO->getFilters()->getFilterRedisKeys();

        $ids = $this->redis->getOfferIdsFromFilters(
            $otherFilters,
        );
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
        return new OfferDTOCollection($offers);
    }

    public function getFilters(FilterDTOCollection $filters): FilterResponseDTOCollection
    {
        $filtersData = $this->mainRepository->getFilters();
        $filtersData = $filtersData->groupBy('slug');
        $activeFilters = $filters->getFilterRedisKeys();
        $activeValues = $filters->getFilterValues();

        $redis = $this->redis;
        $mappedFilters = $filtersData->map(function ($items) use ($activeFilters, $activeValues, $redis) {
            $first = $items->first();

            $filterValues = $items->map(function ($filter) use ($activeFilters, $activeValues, $redis) {
                $count = $redis->getOfferIdsFromFilters(
                    "filter:{$filter['slug']}:{$filter['value']}",
                    ...$activeFilters,
                );
                return new FilterValuesResponseDTO($filter['value'], count($count), in_array($filter['value'], $activeValues));
            });
            $filterValues = new FilterValuesResponseDTOCollection($filterValues);
            return new FilterResponseDTO($first['name'], $first['slug'], $filterValues);
        })->values()->all();

        return new FilterResponseDTOCollection($mappedFilters);
    }
}
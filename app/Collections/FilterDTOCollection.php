<?php

namespace App\Collections;

use App\DTO\FilterDTO;
use Illuminate\Support\Collection;

class FilterDTOCollection extends Collection
{
    /**
     * @return FilterDTO[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function getActiveFilterRedisKeys(): array
    {
        return $this->groupBy(function (FilterDTO $item) {
            return $item->getSlug();
        })->map(function (FilterDTOCollection $collection) {
            return $collection->map(function (FilterDTO $filter) {
                return "filter:{$filter->getSlug()}:{$filter->getValue()}";
            });
        })->toArray();
    }

    public function getFilterValues(): array
    {
        return array_map(function (FilterDTO $filter) {
            return $filter->getValue();
        }, $this->all());
    }
}
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

    public function getFilterRedisKeys(): array
    {
        return array_map(function (FilterDTO $filter) {
            return "filter:{$filter->getSlug()}:{$filter->getValue()}";
        }, $this->all());
    }

    public function getFilterValues(): array
    {
        return array_map(function (FilterDTO $filter) {
            return $filter->getValue();
        }, $this->all());
    }
}
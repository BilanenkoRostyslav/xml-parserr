<?php

namespace App\DTO;

use App\DTO\Abstracts\BaseDTO;

class BuildFiltersWithoutSlugDTO extends BaseDTO
{
    public function __construct(
        private array  $activeFilters,
        private string $slug,
        private string $activeFiltersKey,
        private string $unionPrefixBase,
    )
    {
    }

    public function getActiveFilters(): array
    {
        return $this->activeFilters;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getActiveFiltersKey(): string
    {
        return $this->activeFiltersKey;
    }

    public function getUnionPrefixBase(): string
    {
        return $this->unionPrefixBase;
    }
}
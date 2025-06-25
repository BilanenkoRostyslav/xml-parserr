<?php

namespace App\DTO;

use App\Collections\FilterValuesResponseDTOCollection;
use App\DTO\Abstracts\BaseDTO;

class FilterResponseDTO extends BaseDTO
{
    public function __construct(
        private string                            $name,
        private string                            $slug,
        private FilterValuesResponseDTOCollection $values,
    )
    {
    }

    public function getValues(): FilterValuesResponseDTOCollection
    {
        return $this->values;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
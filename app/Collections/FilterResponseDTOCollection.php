<?php

namespace App\Collections;

use App\DTO\FilterResponseDTO;
use Illuminate\Support\Collection;

class FilterResponseDTOCollection extends Collection
{
    /**
     * @return FilterResponseDTO[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
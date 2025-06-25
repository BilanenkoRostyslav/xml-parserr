<?php

namespace App\Collections;

use App\DTO\OfferDTO;
use Illuminate\Support\Collection;

class OfferDTOCollection extends Collection
{
    /**
     * @return OfferDTO[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
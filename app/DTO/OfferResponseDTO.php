<?php

namespace App\DTO;

use App\Collections\OfferDTOCollection;
use App\DTO\Abstracts\BaseDTO;

class OfferResponseDTO extends BaseDTO
{
    public function __construct(
        private OfferDTOCollection $offers,
        private MetaDTO            $meta
    )
    {
    }

    public function getOffers(): OfferDTOCollection
    {
        return $this->offers;
    }

    public function getMeta(): MetaDTO
    {
        return $this->meta;
    }
}
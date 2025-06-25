<?php

namespace App\DTO;

use App\DTO\Abstracts\BaseDTO;

class OfferDTO extends BaseDTO
{
    public function __construct(
        private int    $id,
        private string $name,
        private string $price,
        private string $description,

    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
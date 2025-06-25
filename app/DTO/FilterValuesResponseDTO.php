<?php

namespace App\DTO;

use App\DTO\Abstracts\BaseDTO;

class FilterValuesResponseDTO extends BaseDTO
{
    public function __construct(
        private string $value,
        private int    $count,
        private bool   $active,
    )
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getActive(): bool
    {
        return $this->active;
    }
}
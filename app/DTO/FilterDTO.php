<?php

namespace App\DTO;

use App\DTO\Abstracts\BaseDTO;

class FilterDTO extends BaseDTO
{
    public function __construct(
        private string $slug,
        private string $value,
    )
    {
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
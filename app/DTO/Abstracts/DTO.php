<?php

namespace App\DTO\Abstracts;

use Illuminate\Contracts\Support\Arrayable;

interface DTO extends Arrayable
{
    public function toArray(): array;

    public function getFilteredArray();
}
<?php

namespace App\DTO;

use App\DTO\Abstracts\BaseDTO;

class MetaDTO extends BaseDTO
{
    public function __construct(
        private int $currentPage,
        private int $lastPage,
        private int $total,
        private int $perPage,
    )
    {
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
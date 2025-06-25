<?php

namespace App\DTO;


use App\Collections\FilterDTOCollection;
use App\DTO\Abstracts\BaseDTO;
use App\Enums\Order;
use App\Enums\OrderAttribute;

class GetOffersDTO extends BaseDTO
{
    public function __construct(
        private int                 $page,
        private int                 $limit,
        private FilterDTOCollection $filters,
        private OrderAttribute      $orderAttribute = OrderAttribute::ID,
        private Order               $order = Order::ASC,
    )
    {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOrderAttribute(): OrderAttribute
    {
        return $this->orderAttribute;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getFilters(): FilterDTOCollection
    {
        return $this->filters;
    }
}
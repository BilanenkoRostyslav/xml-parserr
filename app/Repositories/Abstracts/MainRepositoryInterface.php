<?php

namespace App\Repositories\Abstracts;

use App\Enums\Order;
use App\Enums\OrderAttribute;
use Illuminate\Support\Collection;

interface MainRepositoryInterface
{
    public function getOffersByIds(array $ids, int $limit = 10, int $offset = 0, OrderAttribute $orderAttribute = OrderAttribute::ID, Order $orderBy = Order::ASC): Collection;

    public function getFilters(): Collection;

    public function insertMany(array $items, string $table, array $columns);

    public function getFilterValueId(int $filterId, string $paramValue);

    public function filterIdBySlug(string $slug);
}
<?php

namespace App\Repositories;

use App\Enums\Order;
use App\Enums\OrderAttribute;
use App\Repositories\Abstracts\BaseRepository;
use App\Repositories\Abstracts\MainRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MainRepository extends BaseRepository implements MainRepositoryInterface
{

    public function getOffersByIds(array $ids, int $limit = 10, int $offset = 0, OrderAttribute $orderAttribute = OrderAttribute::ID, Order $orderBy = Order::ASC): Collection
    {
        $orderAttribute = $orderAttribute->value;
        $orderBy = $orderBy->value;
        $inPart = '';
        if (count($ids) != 0) {
            $ids = '(' . implode(',', $ids) . ')';
            $inPart .= "IN {$ids}";
        }
        $query = "SELECT id, name, price, description FROM offers WHERE id {$inPart} ORDER BY {$orderAttribute} {$orderBy} LIMIT {$limit} OFFSET {$offset} ";
        $queryResult = $this->getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return new Collection($queryResult);
    }


    public function getFilters(): Collection
    {
        $query = "SELECT filters.name, filters.slug, filter_values.value FROM filters INNER JOIN filter_values ON filters.id = filter_values.filter_id ";
        $queryResult = $this->getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return new Collection($queryResult);
    }

    public function getFilterValueId(int $filterId, string $paramValue)
    {
        $sql = "SELECT id FROM filter_values WHERE value = ? AND filter_id = ? LIMIT 1";
        $result = DB::selectOne($sql, [$paramValue, $filterId]);

        return $result?->id;
    }

    public function filterIdBySlug(string $slug)
    {
        $sql = "SELECT id FROM filters WHERE slug = ? LIMIT ?";

        $result = DB::selectOne($sql, [$slug, 1]);
        return $result?->id;
    }

    public function upsert(array $items, string $table, array $columns): void
    {
        if (empty($items)) {
            return;
        }

        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholders = implode(', ', array_fill(0, count($items), $rowPlaceholder));

        $bindings = [];
        foreach ($items as $item) {
            foreach ($columns as $column) {
                $bindings[] = $item[$column] ?? null;
            }
        }

        $updates = implode(', ', array_map(fn($col) => "`$col` = VALUES(`$col`)", $columns));

        $sql = "INSERT INTO `$table` (" . implode(', ', array_map(fn($col) => "`$col`", $columns)) . ")
            VALUES $placeholders
            ON DUPLICATE KEY UPDATE $updates";

        DB::statement($sql, $bindings);
    }
}
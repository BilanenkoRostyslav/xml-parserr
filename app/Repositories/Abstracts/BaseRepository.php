<?php

namespace App\Repositories\Abstracts;

use Illuminate\Support\Facades\DB;
use PDO;

abstract class BaseRepository
{
    protected ?PDO $pdo = null;

    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = DB::getPdo();
        }
        return $this->pdo;
    }
}
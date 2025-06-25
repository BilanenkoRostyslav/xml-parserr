<?php

namespace App\Repositories\Abstracts;

use Illuminate\Support\Facades\DB;
use PDO;

abstract class BaseRepository
{
    protected PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::getPdo();
    }
}
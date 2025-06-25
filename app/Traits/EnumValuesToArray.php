<?php

namespace App\Traits;

trait EnumValuesToArray
{
    public static function values(): array
    {
        return array_map(fn($value) => $value->value, self::cases());
    }
}

<?php

namespace App\Enums;

use App\Traits\EnumValuesToArray;

enum OrderAttribute: string
{
    use EnumValuesToArray;

    case ID = 'id';
    case NAME = 'name';
    case PRICE = 'price';
}
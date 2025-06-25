<?php

namespace App\Enums;

use App\Traits\EnumValuesToArray;

enum Order: string
{
    use EnumValuesToArray;

    case ASC = 'asc';
    case DESC = 'desc';
}
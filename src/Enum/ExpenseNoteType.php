<?php

declare(strict_types=1);

namespace App\Enum;

enum ExpenseNoteType: string
{
    case FOOD = 'FOOD';
    case TOLL = 'TOLL';
    case LOCATION = 'LOCATION';
    case OTHER = 'OTHER';
}

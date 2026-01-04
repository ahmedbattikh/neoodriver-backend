<?php

declare(strict_types=1);

namespace App\Enum;

enum ExpenseNoteType: string
{
    case FUEL = 'FUEL';
    case TOLL = 'TOLL';
    case PARKING = 'PARKING';
    case MAINTENANCE = 'MAINTENANCE';
    case OTHER = 'OTHER';
}

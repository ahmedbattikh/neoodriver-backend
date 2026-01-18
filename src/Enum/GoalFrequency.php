<?php

declare(strict_types=1);

namespace App\Enum;

enum GoalFrequency: string
{
    case WEEKLY = 'WEEKLY';
    case MONTHLY = 'MONTHLY';
    case DAILY = 'DAILY';
}

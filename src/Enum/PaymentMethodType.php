<?php
declare(strict_types=1);

namespace App\Enum;

enum PaymentMethodType: string
{
    case CASH = 'CASH';
    case CB = 'CB';
}


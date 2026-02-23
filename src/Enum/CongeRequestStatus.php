<?php

declare(strict_types=1);

namespace App\Enum;

enum CongeRequestStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}

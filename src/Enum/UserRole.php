<?php
declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case DRIVER = 'DRIVER';
}
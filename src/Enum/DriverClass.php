<?php
declare(strict_types=1);

namespace App\Enum;

enum DriverClass: string
{
    case CLASS1 = 'class1';
    case CLASS2 = 'class2';
    case CLASS3 = 'class3';
    case CLASS5 = 'class5';

    public static function values(): array
    {
        return [
            self::CLASS1->value,
            self::CLASS2->value,
            self::CLASS3->value,
            self::CLASS5->value,
        ];
    }
}


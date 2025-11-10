<?php
declare(strict_types=1);

namespace App\Enum;

enum EnergyType: string
{
    case GASOLINE = 'GASOLINE';
    case DIESEL = 'DIESEL';
    case HYBRID = 'HYBRID';
    case ELECTRIC = 'ELECTRIC';
    case LPG = 'LPG';
    case CNG = 'CNG';
    case HYDROGEN = 'HYDROGEN';
    case OTHER = 'OTHER';
}
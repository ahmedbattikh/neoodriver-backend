<?php

declare(strict_types=1);

namespace App\Enum;

enum ValidationStatus: string
{
    case VALIDATION_INPROGRESS = 'VALIDATION_INPROGRESS';
    case DOCUMENT_INVALIDE = 'DOCUMENT_INVALIDE';
    case DOCUMENT_VALID = 'DOCUMENT_VALID';
    case DOCUMENT_REJECTED = 'DOCUMENT_REJECTED';
}

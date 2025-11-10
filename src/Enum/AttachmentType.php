<?php
declare(strict_types=1);

namespace App\Enum;

enum AttachmentType: string
{
    case DOCUMENT = 'DOCUMENT';
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case AUDIO = 'AUDIO';
    case PDF = 'PDF';
    case SPREADSHEET = 'SPREADSHEET';
    case PRESENTATION = 'PRESENTATION';
    case ARCHIVE = 'ARCHIVE';
    case OTHER = 'OTHER';
}
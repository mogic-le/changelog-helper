<?php

namespace App\Enums;

enum ChangelogType: string
{
    case ADDED = 'Added';
    case CHANGED = 'Changed';
    case DEPRECATED = 'Deprecated';
    case REMOVED = 'Removed';
    case FIXED = 'Fixed';
    case SECURITY = 'Security';

    public static function toArray(): array
    {
        return [
            self::ADDED->value,
            self::CHANGED->value,
            self::DEPRECATED->value,
            self::REMOVED->value,
            self::FIXED->value,
            self::SECURITY->value,
        ];
    }
}

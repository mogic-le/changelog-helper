<?php

namespace App\Enums;

enum ChangelogReleaseLevel: string
{
    case MAJOR = 'major';
    case MINOR = 'minor';
    case PATCH = 'patch';

    public static function toArray(): array
    {
        return [
            self::PATCH->value,
            self::MINOR->value,
            self::MAJOR->value,
        ];
    }
}

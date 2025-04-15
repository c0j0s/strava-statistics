<?php

declare(strict_types=1);

namespace App\Domain\App;

final readonly class AppVersion
{
    private const int MAJOR = 1;
    private const int MINOR = 2;
    private const int PATCH = 1;

    public static function getSemanticVersion(): string
    {
        return sprintf('v%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH);
    }
}

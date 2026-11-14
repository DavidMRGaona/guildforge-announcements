<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

/**
 * A page a window is limited to: a path of this site ("/eventos"), or a prefix when it ends with
 * "*" ("/eventos*" is "/eventos" and everything below it). No query, no host, no other "*".
 */
final class PopupPagePattern
{
    public const int MAX_LENGTH = 255;

    private const string PATTERN = '~^/(?!/)[A-Za-z0-9\-._\~/%]*\*?$~';

    public static function isValid(string $pattern): bool
    {
        return strlen($pattern) <= self::MAX_LENGTH && preg_match(self::PATTERN, $pattern) === 1;
    }
}

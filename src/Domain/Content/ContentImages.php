<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Content;

use DateTimeInterface;

/**
 * Images inserted in an announcement's text live in "announcements/content/<year>/<month>", the
 * folder the module's media source claims, as the core's editor images do (core#481).
 */
final class ContentImages
{
    /** Folder claimed in the media library, with its trailing slash */
    public const string FOLDER = 'announcements/content/';

    /** Type of the media source that reads the announcements' text. Stored with each usage: never rename it */
    public const string MEDIA_TYPE = 'announcements.content';

    public static function directory(DateTimeInterface $month): string
    {
        return self::FOLDER.$month->format('Y/m');
    }

    /**
     * Whether the path is an image uploaded from the editor: "announcements/content/<year>/<month>/<file>".
     */
    public static function isStoredPath(string $path): bool
    {
        return preg_match('~^'.preg_quote(self::FOLDER, '~').'\d{4}/\d{2}/[^/]+$~', $path) === 1;
    }
}

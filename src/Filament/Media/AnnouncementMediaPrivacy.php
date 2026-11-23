<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Media;

use Illuminate\Database\Eloquent\Model;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;

/**
 * Which announcements keep their images out of sight in the media library (announcements#80): those for
 * members only, which only some people may read. Every announcement is written by the team in the panel,
 * so one not shown yet, or no longer, is theirs as much as an unpublished event, and is shown. A hidden
 * usage is titled neutrally, since the title says what the members were told; the file still counts as
 * used and the edit link still follows the policy.
 */
final class AnnouncementMediaPrivacy
{
    public static function hides(Model $announcement): bool
    {
        $visibility = $announcement->getAttribute('visibility');

        // Fails closed: a visibility that cannot be read is treated as members only
        return ! $visibility instanceof AnnouncementVisibility || ! $visibility->isOpenToAnybody();
    }

    public static function titleOf(Model $announcement): string
    {
        return self::hides($announcement)
            ? (string) __('announcements::announcements.media.members_only_title')
            : (string) $announcement->getAttribute('title');
    }
}

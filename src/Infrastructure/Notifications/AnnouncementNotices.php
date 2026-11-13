<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Notifications;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Which announcements were already told in the bell (`announcements_notices`). Deleting an
 * announcement deletes its row with it (foreign key).
 */
final class AnnouncementNotices
{
    private const string TABLE = 'announcements_notices';

    /**
     * Marks the announcement as told; false when it already was. Two jobs at once cannot both
     * claim it: the unique key lets only one row in.
     */
    public function claim(string $announcementId): bool
    {
        return DB::table(self::TABLE)->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'announcement_id' => $announcementId,
            'announced_at' => now(),
        ]) === 1;
    }
}

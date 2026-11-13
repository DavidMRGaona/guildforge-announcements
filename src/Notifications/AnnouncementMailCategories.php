<?php

declare(strict_types=1);

namespace Modules\Announcements\Notifications;

use App\Application\Mail\DTOs\MailCategoryDTO;

/**
 * The module's notice categories. New announcements are told in the bell only, never mailed, to
 * every account; optional and on by default, like the core's site news.
 */
final class AnnouncementMailCategories
{
    public const string NEW_ANNOUNCEMENTS = 'announcements.new-announcements';

    /**
     * @return list<MailCategoryDTO>
     */
    public static function all(): array
    {
        return [
            MailCategoryDTO::optional(
                key: self::NEW_ANNOUNCEMENTS,
                section: 'announcements::notifications.categories.section',
                label: 'announcements::notifications.categories.new_announcements.label',
                description: 'announcements::notifications.categories.new_announcements.description',
                icon: 'megaphone',
                site: true,
                mail: false,
            ),
        ];
    }
}

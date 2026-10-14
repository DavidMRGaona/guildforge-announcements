<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

enum AnnouncementVisibility: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';
    case Members = 'members';

    /**
     * Whether a user (null for a guest) may see an announcement with this visibility.
     * Membership is decided by the caller and passed in as $isMember.
     */
    public function isVisibleTo(?object $user, bool $isMember = false): bool
    {
        return match ($this) {
            self::Public => true,
            self::Authenticated => $user !== null,
            self::Members => $user !== null && $isMember,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Public => __('announcements::announcements.visibility.public'),
            self::Authenticated => __('announcements::announcements.visibility.authenticated'),
            self::Members => __('announcements::announcements.visibility.members'),
        };
    }
}

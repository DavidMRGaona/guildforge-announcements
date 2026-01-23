<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

enum AnnouncementVisibility: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';
    case Members = 'members';

    public function isVisibleTo(?object $user): bool
    {
        return match ($this) {
            self::Public => true,
            self::Authenticated => $user !== null,
            self::Members => $user !== null && $this->isMember($user),
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

    private function isMember(?object $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Check for is_member property (stdClass in tests, UserModel in app)
        if (property_exists($user, 'is_member')) {
            return (bool) $user->is_member;
        }

        // Check for isMember method (if available)
        if (method_exists($user, 'isMember')) {
            return $user->isMember();
        }

        return false;
    }
}

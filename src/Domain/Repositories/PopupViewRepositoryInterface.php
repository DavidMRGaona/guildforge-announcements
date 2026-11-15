<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Repositories;

use DateTimeImmutable;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\PopupView;

interface PopupViewRepositoryInterface
{
    /**
     * What the account remembers of the given windows, by announcement id. Windows it never saw
     * have no entry.
     *
     * @param  list<string>  $announcementIds
     * @return array<string, PopupView>
     */
    public function forUser(string $userId, array $announcementIds): array;

    /** The window was shown to the account at this instant */
    public function recordShown(string $userId, AnnouncementId $announcementId, DateTimeImmutable $at): void;

    /** The account closed the window at this instant (which also counts as having been shown) */
    public function recordDismissed(string $userId, AnnouncementId $announcementId, DateTimeImmutable $at): void;

    /** Drops everything the account remembers */
    public function forgetUser(string $userId): void;
}

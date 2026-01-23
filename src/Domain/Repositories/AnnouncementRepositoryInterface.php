<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Repositories;

use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;

interface AnnouncementRepositoryInterface
{
    public function save(Announcement $announcement): void;

    public function find(AnnouncementId $id): ?Announcement;

    public function findOrFail(AnnouncementId $id): Announcement;

    public function delete(AnnouncementId $id): void;

    /**
     * @return array<Announcement>
     */
    public function all(): array;

    /**
     * Get active announcements that are currently valid.
     *
     * @return array<Announcement>
     */
    public function findActive(): array;
}

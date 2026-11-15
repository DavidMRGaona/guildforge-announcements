<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Repositories;

use Modules\Announcements\Domain\Enums\PopupMetric;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;

interface PopupMetricRepositoryInterface
{
    /**
     * Adds one to the counter of the window, metric and day (`Y-m-d`, display zone), atomically.
     */
    public function increment(AnnouncementId $announcementId, PopupMetric $metric, string $day): void;

    /**
     * What a window counted from a day on (`Y-m-d`, included), per metric; a metric with no rows is 0.
     *
     * @return array<string, int>
     */
    public function totalsSince(AnnouncementId $announcementId, string $fromDay): array;

    /**
     * Deletes the counters of days before the given one (`Y-m-d`) and returns how many rows went.
     */
    public function pruneBefore(string $day): int;
}

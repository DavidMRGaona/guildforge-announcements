<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use Modules\Announcements\Application\DTOs\PopupMetricsSummaryDTO;
use Modules\Announcements\Domain\Enums\PopupMetric;

interface PopupMetricServiceInterface
{
    /** Days of display time the panel summary covers, today included */
    public const int SUMMARY_DAYS = 30;

    /**
     * Counts one more of the metric for a window the visitor may see now; false (nothing counted)
     * for anything else: an unknown id, a banner, an inactive or ended window, one for another audience.
     */
    public function record(?object $user, string $announcementId, PopupMetric $metric): bool;

    public function summary(string $announcementId): PopupMetricsSummaryDTO;

    /**
     * Deletes the counters older than the given number of display days and returns how many rows went.
     */
    public function prune(int $days): int;
}

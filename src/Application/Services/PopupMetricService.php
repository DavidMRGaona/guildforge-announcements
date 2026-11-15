<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use App\Application\Services\TimezoneServiceInterface;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\DTOs\PopupMetricsSummaryDTO;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\PopupMetric;
use Modules\Announcements\Domain\Repositories\PopupMetricRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;

final readonly class PopupMetricService implements PopupMetricServiceInterface
{
    public function __construct(
        private AnnouncementServiceInterface $announcements,
        private PopupMetricRepositoryInterface $metrics,
        private TimezoneServiceInterface $timezone,
    ) {}

    public function record(?object $user, string $announcementId, PopupMetric $metric): bool
    {
        $announcement = $this->announcements->findVisibleForUser($announcementId, $user);

        if (! $announcement instanceof AnnouncementResponseDTO || $announcement->format !== AnnouncementFormat::Popup->value) {
            return false;
        }

        $this->metrics->increment(
            AnnouncementId::fromString($announcement->id),
            $metric,
            $this->timezone->today()->format('Y-m-d'),
        );

        return true;
    }

    public function summary(string $announcementId): PopupMetricsSummaryDTO
    {
        $from = $this->timezone->today()
            ->modify(sprintf('-%d days', self::SUMMARY_DAYS - 1))
            ->format('Y-m-d');

        $totals = $this->metrics->totalsSince(AnnouncementId::fromString($announcementId), $from);

        return new PopupMetricsSummaryDTO(
            shown: $totals[PopupMetric::Shown->value] ?? 0,
            dismissed: $totals[PopupMetric::Dismissed->value] ?? 0,
            actionClicked: $totals[PopupMetric::ActionClicked->value] ?? 0,
        );
    }

    public function prune(int $days): int
    {
        $limit = $this->timezone->today()->modify('-'.max(0, $days).' days')->format('Y-m-d');

        return $this->metrics->pruneBefore($limit);
    }
}

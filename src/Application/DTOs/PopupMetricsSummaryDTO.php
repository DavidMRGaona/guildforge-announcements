<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\DTOs;

/**
 * What a window counted in a period (announcements#69).
 */
final readonly class PopupMetricsSummaryDTO
{
    /** Button presses per hundred times shown, with one decimal; null until it was shown */
    public ?float $clickRate;

    public function __construct(
        public int $shown,
        public int $dismissed,
        public int $actionClicked,
    ) {
        $this->clickRate = $shown > 0 ? round($actionClicked / $shown * 100, 1) : null;
    }
}

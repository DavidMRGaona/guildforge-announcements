<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Modules\Announcements\Application\DTOs\PopupMetricsSummaryDTO;
use Modules\Announcements\Application\Services\PopupMetricServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

/**
 * What a window did in the last 30 display days (announcements#69), on its page in the panel:
 * times shown, closed, button presses and the press rate. Anonymous counters; only for a window
 * and for whoever may edit the announcement.
 */
final class PopupMetricsWidget extends StatsOverviewWidget
{
    // A handful of sums: drawn with the page, not after it
    protected static bool $isLazy = false;

    public ?Model $record = null;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return AnnouncementResource::canViewAny();
    }

    protected function getHeading(): string
    {
        return __('announcements::announcements.metrics.heading', ['days' => PopupMetricServiceInterface::SUMMARY_DAYS]);
    }

    protected function getDescription(): string
    {
        return __('announcements::announcements.metrics.description');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $record = $this->record;

        if (! $record instanceof AnnouncementModel || $record->format !== AnnouncementFormat::Popup || ! AnnouncementResource::canEdit($record)) {
            return [];
        }

        $summary = app(PopupMetricServiceInterface::class)->summary((string) $record->getKey());

        return [
            Stat::make(__('announcements::announcements.metrics.shown'), number_format($summary->shown, 0, ',', '.')),
            Stat::make(__('announcements::announcements.metrics.dismissed'), number_format($summary->dismissed, 0, ',', '.')),
            Stat::make(__('announcements::announcements.metrics.action_clicked'), number_format($summary->actionClicked, 0, ',', '.')),
            Stat::make(__('announcements::announcements.metrics.click_rate'), $this->rate($summary)),
        ];
    }

    private function rate(PopupMetricsSummaryDTO $summary): string
    {
        if ($summary->clickRate === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($summary->clickRate, 1, ',', '.'), '0'), ',').' %';
    }
}

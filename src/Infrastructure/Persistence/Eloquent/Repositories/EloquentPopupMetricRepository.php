<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Announcements\Domain\Enums\PopupMetric;
use Modules\Announcements\Domain\Repositories\PopupMetricRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;

final class EloquentPopupMetricRepository implements PopupMetricRepositoryInterface
{
    private const string TABLE = 'announcements_popup_metrics';

    public function increment(AnnouncementId $announcementId, PopupMetric $metric, string $day): void
    {
        // One statement: two visitors at the same time never lose a count
        DB::table(self::TABLE)->upsert(
            [[
                'id' => (string) Str::uuid(),
                'announcement_id' => $announcementId->value,
                'day' => $day,
                'metric' => $metric->value,
                'count' => 1,
            ]],
            ['announcement_id', 'day', 'metric'],
            ['count' => DB::raw(self::TABLE.'.count + 1')],
        );
    }

    public function totalsSince(AnnouncementId $announcementId, string $fromDay): array
    {
        $rows = DB::table(self::TABLE)
            ->selectRaw('metric, SUM(count) as total')
            ->where('announcement_id', $announcementId->value)
            ->where('day', '>=', $fromDay)
            ->groupBy('metric')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $totals[(string) $row->metric] = (int) $row->total;
        }

        return $totals;
    }

    public function pruneBefore(string $day): int
    {
        return DB::table(self::TABLE)->where('day', '<', $day)->delete();
    }
}

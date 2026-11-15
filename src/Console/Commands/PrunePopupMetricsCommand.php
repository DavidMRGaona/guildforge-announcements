<?php

declare(strict_types=1);

namespace Modules\Announcements\Console\Commands;

use Illuminate\Console\Command;
use Modules\Announcements\Application\Services\PopupMetricServiceInterface;

final class PrunePopupMetricsCommand extends Command
{
    protected $signature = 'announcements:prune-metrics {--days= : Days to keep (defaults to announcements.metrics_retention_days)}';

    protected $description = 'Delete the anonymous counters of the popup windows older than the retention period';

    public function handle(PopupMetricServiceInterface $metrics): int
    {
        $option = $this->option('days');
        $days = $option === null || $option === ''
            ? config('announcements.metrics_retention_days')
            : $option;

        if (! is_numeric($days) || (int) $days < 0 || (string) (int) $days !== (string) $days) {
            $this->error('The --days option must be a non-negative integer.');

            return self::FAILURE;
        }

        $this->info('Pruned: '.$metrics->prune((int) $days));

        return self::SUCCESS;
    }
}

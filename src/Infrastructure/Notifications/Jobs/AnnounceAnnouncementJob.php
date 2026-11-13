<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Notifications\Jobs;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Infrastructure\Notifications\AnnouncementNotices;

/**
 * Tells a new announcement once it is shown on the site: queued when it is saved, delayed until
 * its start when it is scheduled. Only a job that finds it shown claims it, so a job that runs
 * early (its start was moved later) leaves the announcement to the job queued with the new start.
 *
 * Splits the accounts in batches, one DeliverAnnouncementNoticesJob each, so no job grows with
 * the number of accounts. Deactivated and anonymized accounts are left out; whether each person
 * may see it is decided when the notice is built.
 */
final class AnnounceAnnouncementJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public const int BATCH_SIZE = 100;

    /** A retry would queue the batches already queued a second time */
    public int $tries = 1;

    /**
     * @param  list<string>  $excludedIds  Accounts that already know: who saved it.
     */
    public function __construct(
        public readonly string $announcementId,
        public readonly array $excludedIds = [],
    ) {}

    public function handle(): void
    {
        $announcement = app(AnnouncementRepositoryInterface::class)->find(AnnouncementId::fromString($this->announcementId));

        // Deleted, turned off, expired or not started yet: nothing to tell now
        if (! $announcement instanceof Announcement || ! $announcement->isCurrentlyValid()) {
            return;
        }

        if (! app(AnnouncementNotices::class)->claim($this->announcementId)) {
            return;
        }

        UserModel::query()
            ->whereNull('anonymized_at')
            ->whereNotIn('id', $this->excludedIds)
            ->select('id')
            ->chunkById(self::BATCH_SIZE, function (Collection $accounts): void {
                /** @var list<string> $ids */
                $ids = $accounts->modelKeys();

                DeliverAnnouncementNoticesJob::dispatch($this->announcementId, $ids);
            });
    }
}

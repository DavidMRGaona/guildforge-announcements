<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Notifications\Jobs;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Infrastructure\SiteNotifications\SiteNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Announcements\Notifications\AnnouncementPublishedNotification;
use Throwable;

/**
 * Delivers the notice of one announcement to one batch of accounts.
 *
 * It goes straight to the site channel, which decides for each account (active, wants it, not
 * muted), as the core's DeliverSiteNewsJob does: the notice is never mailed, so nothing else would
 * run through the notification facade, and another module's test faking notifications does not
 * see everybody's notice.
 */
final class DeliverAnnouncementNoticesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** A retry would add the notice again to the accounts already reached */
    public int $tries = 1;

    /**
     * @param  list<string>  $accountIds
     */
    public function __construct(
        public readonly string $announcementId,
        public readonly array $accountIds,
    ) {}

    public function handle(SiteNotificationChannel $channel): void
    {
        $notification = new AnnouncementPublishedNotification($this->announcementId);

        foreach (UserModel::query()->whereKey($this->accountIds)->get() as $account) {
            try {
                $channel->send($account, $notification);
            } catch (Throwable $exception) {
                // One account that cannot be reached does not leave the rest of the batch without it
                Log::warning('An announcement notice could not be delivered.', ['announcement_id' => $this->announcementId, 'user_id' => $account->id, 'exception' => $exception->getMessage()]);
            }
        }
    }
}

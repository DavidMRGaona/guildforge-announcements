<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Listeners;

use App\Domain\Events\UserAnonymized;
use Modules\Announcements\Domain\Repositories\PopupViewRepositoryInterface;

/**
 * Anonymizing keeps the row of the account, so the foreign key does not drop what it remembers
 * about the windows (announcements#68): the core's event does.
 */
final readonly class ForgetPopupViewsOfAnonymizedAccount
{
    public function __construct(
        private PopupViewRepositoryInterface $views,
    ) {}

    public function handle(UserAnonymized $event): void
    {
        $this->views->forgetUser($event->userId);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * What the browser tells the server about the windows of a signed-in account (announcements#68).
 * Only a window the account can see is remembered; anything else is refused, so the notices
 * never reveal or fill the table with announcements the account has no business with.
 */
interface PopupViewServiceInterface
{
    /**
     * @return bool false when the id is not a window this account can see
     */
    public function recordShown(Authenticatable $user, string $announcementId): bool;

    /**
     * @return bool false when the id is not a window this account can see
     */
    public function recordDismissed(Authenticatable $user, string $announcementId): bool;
}

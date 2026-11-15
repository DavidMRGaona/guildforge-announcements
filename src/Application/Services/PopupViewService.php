<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Repositories\PopupViewRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;

final readonly class PopupViewService implements PopupViewServiceInterface
{
    public function __construct(
        private AnnouncementServiceInterface $announcements,
        private PopupViewRepositoryInterface $views,
    ) {}

    public function recordShown(Authenticatable $user, string $announcementId): bool
    {
        $window = $this->visibleWindow($user, $announcementId);

        if (! $window instanceof AnnouncementId) {
            return false;
        }

        $this->views->recordShown((string) $user->getAuthIdentifier(), $window, CarbonImmutable::now());

        return true;
    }

    public function recordDismissed(Authenticatable $user, string $announcementId): bool
    {
        $window = $this->visibleWindow($user, $announcementId);

        if (! $window instanceof AnnouncementId) {
            return false;
        }

        $this->views->recordDismissed((string) $user->getAuthIdentifier(), $window, CarbonImmutable::now());

        return true;
    }

    private function visibleWindow(Authenticatable $user, string $announcementId): ?AnnouncementId
    {
        $announcement = $this->announcements->findVisibleForUser($announcementId, $user);

        if (! $announcement instanceof AnnouncementResponseDTO || $announcement->format !== AnnouncementFormat::Popup->value) {
            return null;
        }

        return AnnouncementId::fromString($announcement->id);
    }
}

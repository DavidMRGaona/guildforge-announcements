<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Support;

use Illuminate\Http\Request;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use WeakMap;

/**
 * The announcements shared with every Inertia page (banners in `announcements`, windows in
 * `announcementPopups`), read once per request for the signed-in user and split by format.
 * Kept per request object, so a container that serves several requests (tests, long-running
 * workers) never hands one request what another read.
 */
final class SharedAnnouncements
{
    /** @var WeakMap<Request, list<AnnouncementResponseDTO>> */
    private WeakMap $read;

    public function __construct()
    {
        $this->read = new WeakMap;
    }

    /**
     * In the service's order: for windows it is also which one wins.
     *
     * @return list<array<string, mixed>>
     */
    public function of(AnnouncementFormat $format, Request $request): array
    {
        // Resolved on each read, so a long-lived instance never keeps a stale service
        $this->read[$request] ??= array_values(app(AnnouncementServiceInterface::class)->getActiveForUser($request->user()));

        $shared = [];

        foreach ($this->read[$request] as $announcement) {
            if ($announcement->format === $format->value) {
                $shared[] = $announcement->toArray();
            }
        }

        return $shared;
    }
}

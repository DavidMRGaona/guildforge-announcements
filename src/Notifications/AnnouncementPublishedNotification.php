<?php

declare(strict_types=1);

namespace Modules\Announcements\Notifications;

use App\Application\Mail\Contracts\HasMailCategory;
use App\Application\Mail\Contracts\HasMailSubject;
use App\Application\Mail\DTOs\MailSubjectDTO;
use App\Application\SiteNotifications\Contracts\HasSiteNotification;
use App\Application\SiteNotifications\DTOs\SiteNotificationDTO;
use App\Application\SiteNotifications\DTOs\SiteNotificationGroupDTO;
use App\Infrastructure\Search\Support\PlainText;
use Illuminate\Notifications\Notification;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;

/**
 * «Nuevo anuncio: X», in the bell only (announcements#63). Whether the person sees it is asked
 * when it is delivered, with the same rule as the banner: an announcement turned off, expired,
 * deleted or not meant for that person shows nothing. It links to the home page, where the
 * banner is, and carries the text itself, so a banner closed in that browser loses nothing.
 *
 * Sent in batches straight to the site channel by DeliverAnnouncementNoticesJob, which is
 * already queued, so the notification itself is not.
 */
final class AnnouncementPublishedNotification extends Notification implements HasMailCategory, HasMailSubject, HasSiteNotification
{
    public const string GROUP_KEY = 'announcements.news';

    public const string SUBJECT_TYPE = 'announcements.announcement';

    private ?AnnouncementResponseDTO $announcement = null;

    private bool $read = false;

    public function __construct(
        public readonly string $announcementId,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [HasSiteNotification::CHANNEL];
    }

    public function mailCategory(): string
    {
        return AnnouncementMailCategories::NEW_ANNOUNCEMENTS;
    }

    /**
     * Ties the notice to its announcement, so deleting the announcement forgets it.
     */
    public function mailSubject(): ?MailSubjectDTO
    {
        $announcement = $this->announcement();

        return $announcement instanceof AnnouncementResponseDTO
            ? new MailSubjectDTO(self::SUBJECT_TYPE, $this->announcementId, $announcement->title, 'announcements::notifications.subjects.announcement')
            : null;
    }

    public function toSiteNotification(object $notifiable): ?SiteNotificationDTO
    {
        $visible = app(AnnouncementServiceInterface::class)->findVisibleForUser($this->announcementId, $notifiable);

        if (! $visible instanceof AnnouncementResponseDTO) {
            return null;
        }

        $body = PlainText::from($visible->content);

        return new SiteNotificationDTO(
            title: __('announcements::notifications.title', ['title' => $visible->title]),
            url: '/',
            body: $body === '' ? null : $body,
            icon: 'megaphone',
            groupKey: self::GROUP_KEY,
            groupedTitle: static fn (SiteNotificationGroupDTO $group): string => trans_choice(
                'announcements::notifications.grouped',
                $group->count,
                ['count' => $group->count],
            ),
        );
    }

    private function announcement(): ?AnnouncementResponseDTO
    {
        if (! $this->read) {
            $this->announcement = app(AnnouncementServiceInterface::class)->find($this->announcementId);
            $this->read = true;
        }

        return $this->announcement;
    }
}

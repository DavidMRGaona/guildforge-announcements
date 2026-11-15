<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\DTOs;

use App\Application\Services\TimezoneServiceInterface;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use Modules\Announcements\Domain\ValueObjects\PopupBlock;

final readonly class AnnouncementResponseDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $content,
        public string $visibility,
        public string $position,
        public int $priority,
        public ?string $backgroundColor,
        public ?string $textColor,
        public ?string $startsAt,
        public ?string $endsAt,
        public bool $isActive,
        public bool $isDismissible,
        public ?string $createdAt,
        public ?string $updatedAt,
        public string $format = 'banner',
        /** @var array<string, mixed>|null */
        public ?array $popup = null,
        /** A window this account has already seen and that is not due again: still shared, so the page keeps one that is open */
        public bool $seen = false,
        /** The block of a window, still as stored: it never leaves with `toArray()`, the page gets it resolved for the person (announcements#70) */
        public ?PopupBlock $block = null,
        public bool $hideWhenBlockUnavailable = true,
    ) {}

    public static function fromEntity(Announcement $announcement, bool $seen = false): self
    {
        $timezone = app(TimezoneServiceInterface::class);
        $popup = $announcement->popup();

        return new self(
            id: $announcement->id()->value,
            title: $announcement->title(),
            content: $announcement->content(),
            visibility: $announcement->visibility()->value,
            position: $announcement->position()->value,
            priority: $announcement->priority()->value,
            backgroundColor: $announcement->backgroundColor(),
            textColor: $announcement->textColor(),
            startsAt: $timezone->iso($announcement->startsAt()),
            endsAt: $timezone->iso($announcement->endsAt()),
            isActive: $announcement->isActive(),
            isDismissible: $announcement->canBeDismissed(),
            createdAt: $timezone->iso($announcement->createdAt()),
            updatedAt: $timezone->iso($announcement->updatedAt()),
            format: $announcement->format()->value,
            popup: self::popupToArray($popup),
            seen: $seen,
            block: $popup?->block,
            hideWhenBlockUnavailable: $popup instanceof AnnouncementPopup ? $popup->hideWhenBlockUnavailable : true,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function popupToArray(?AnnouncementPopup $popup): ?array
    {
        if (! $popup instanceof AnnouncementPopup) {
            return null;
        }

        return [
            'shape' => $popup->shape->value,
            'image_public_id' => $popup->imagePublicId,
            'image_alt' => $popup->imageAlt,
            'action' => $popup->action instanceof PopupAction
                ? ['label' => $popup->action->label, 'url' => $popup->action->url]
                : null,
            'triggers' => [
                'delay_seconds' => $popup->triggers->delaySeconds,
                'scroll_percent' => $popup->triggers->scrollPercent,
                'exit_intent' => $popup->triggers->exitIntent,
                'page_views' => $popup->triggers->pageViews,
            ],
            'pages' => $popup->pages->value,
            'paths' => $popup->paths,
            'device' => $popup->device->value,
            'frequency' => $popup->frequency->value,
            'frequency_days' => $popup->frequencyDays,
            // Filled when the page is shared, for the person viewing it
            'block' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'visibility' => $this->visibility,
            'position' => $this->position,
            'priority' => $this->priority,
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'is_active' => $this->isActive,
            'is_dismissible' => $this->isDismissible,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'format' => $this->format,
            'popup' => $this->popup,
            'seen' => $this->seen,
        ];
    }
}

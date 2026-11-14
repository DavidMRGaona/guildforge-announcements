<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\DTOs;

use DateTimeImmutable;
use Modules\Announcements\Application\Support\PopupAttributes;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;

final readonly class UpdateAnnouncementDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $content,
        public AnnouncementVisibility $visibility,
        public AnnouncementPosition $position,
        public int $priority,
        public ?string $backgroundColor = null,
        public ?string $textColor = null,
        public ?DateTimeImmutable $startsAt = null,
        public ?DateTimeImmutable $endsAt = null,
        public bool $isActive = true,
        public bool $isDismissible = true,
        public AnnouncementFormat $format = AnnouncementFormat::Banner,
        public ?AnnouncementPopup $popup = null,
    ) {}

    /**
     * The window settings use the table's column names (`popup_shape`, `popup_paths`…).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $format = isset($data['format']) ? AnnouncementFormat::from($data['format']) : AnnouncementFormat::Banner;

        return new self(
            id: $data['id'],
            title: $data['title'],
            content: $data['content'],
            visibility: isset($data['visibility'])
                ? AnnouncementVisibility::from($data['visibility'])
                : AnnouncementVisibility::Public,
            position: isset($data['position'])
                ? AnnouncementPosition::from($data['position'])
                : AnnouncementPosition::BeforeHeader,
            priority: $data['priority'] ?? 5,
            backgroundColor: $data['background_color'] ?? null,
            textColor: $data['text_color'] ?? null,
            startsAt: self::parseDateTime($data['starts_at'] ?? null),
            endsAt: self::parseDateTime($data['ends_at'] ?? null),
            isActive: $data['is_active'] ?? true,
            isDismissible: $data['is_dismissible'] ?? true,
            format: $format,
            popup: $format === AnnouncementFormat::Popup ? PopupAttributes::toPopup($data) : null,
        );
    }

    private static function parseDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if (is_string($value)) {
            return new DateTimeImmutable($value);
        }

        return null;
    }
}

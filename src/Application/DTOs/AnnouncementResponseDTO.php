<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\DTOs;

use App\Application\Services\TimezoneServiceInterface;
use Modules\Announcements\Domain\Entities\Announcement;

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
    ) {}

    public static function fromEntity(Announcement $announcement): self
    {
        $timezone = app(TimezoneServiceInterface::class);

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
            isDismissible: $announcement->isDismissible(),
            createdAt: $timezone->iso($announcement->createdAt()),
            updatedAt: $timezone->iso($announcement->updatedAt()),
        );
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
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\DTOs;

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
        return new self(
            id: $announcement->id()->value,
            title: $announcement->title(),
            content: $announcement->content(),
            visibility: $announcement->visibility()->value,
            position: $announcement->position()->value,
            priority: $announcement->priority()->value,
            backgroundColor: $announcement->backgroundColor(),
            textColor: $announcement->textColor(),
            startsAt: $announcement->startsAt()?->format('c'),
            endsAt: $announcement->endsAt()?->format('c'),
            isActive: $announcement->isActive(),
            isDismissible: $announcement->isDismissible(),
            createdAt: $announcement->createdAt()?->format('c'),
            updatedAt: $announcement->updatedAt()?->format('c'),
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

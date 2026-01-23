<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Entities;

use DateTimeImmutable;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;

final class Announcement
{
    public function __construct(
        private readonly AnnouncementId $id,
        private string $title,
        private string $content,
        private AnnouncementVisibility $visibility,
        private AnnouncementPosition $position,
        private AnnouncementPriority $priority,
        private ?string $backgroundColor = null,
        private ?string $textColor = null,
        private ?DateTimeImmutable $startsAt = null,
        private ?DateTimeImmutable $endsAt = null,
        private bool $isActive = true,
        private bool $isDismissible = true,
        private readonly ?DateTimeImmutable $createdAt = null,
        private readonly ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): AnnouncementId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function visibility(): AnnouncementVisibility
    {
        return $this->visibility;
    }

    public function position(): AnnouncementPosition
    {
        return $this->position;
    }

    public function priority(): AnnouncementPriority
    {
        return $this->priority;
    }

    public function backgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function textColor(): ?string
    {
        return $this->textColor;
    }

    public function startsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDismissible(): bool
    {
        return $this->isDismissible;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function isCurrentlyValid(?DateTimeImmutable $now = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = $now ?? new DateTimeImmutable();

        if ($this->startsAt !== null && $now < $this->startsAt) {
            return false;
        }

        if ($this->endsAt !== null && $now > $this->endsAt) {
            return false;
        }

        return true;
    }

    public function isVisibleTo(?object $user): bool
    {
        return $this->visibility->isVisibleTo($user);
    }

    public function update(
        string $title,
        string $content,
        AnnouncementVisibility $visibility,
        AnnouncementPosition $position,
        AnnouncementPriority $priority,
        ?string $backgroundColor = null,
        ?string $textColor = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        bool $isDismissible = true,
    ): void {
        $this->title = $title;
        $this->content = $content;
        $this->visibility = $visibility;
        $this->position = $position;
        $this->priority = $priority;
        $this->backgroundColor = $backgroundColor;
        $this->textColor = $textColor;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->isDismissible = $isDismissible;
    }
}

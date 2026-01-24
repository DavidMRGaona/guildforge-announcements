<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\DTOs\CreateAnnouncementDTO;
use Modules\Announcements\Application\DTOs\UpdateAnnouncementDTO;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;

final readonly class AnnouncementService implements AnnouncementServiceInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
    ) {}

    public function create(CreateAnnouncementDTO $dto): AnnouncementResponseDTO
    {
        $announcement = new Announcement(
            id: AnnouncementId::generate(),
            title: $dto->title,
            content: $dto->content,
            visibility: $dto->visibility,
            position: $dto->position,
            priority: new AnnouncementPriority($dto->priority),
            backgroundColor: $dto->backgroundColor,
            textColor: $dto->textColor,
            startsAt: $dto->startsAt,
            endsAt: $dto->endsAt,
            isActive: $dto->isActive,
            isDismissible: $dto->isDismissible,
        );

        $this->repository->save($announcement);

        return AnnouncementResponseDTO::fromEntity($announcement);
    }

    public function update(UpdateAnnouncementDTO $dto): AnnouncementResponseDTO
    {
        $announcement = $this->repository->findOrFail(AnnouncementId::fromString($dto->id));

        $announcement->update(
            title: $dto->title,
            content: $dto->content,
            visibility: $dto->visibility,
            position: $dto->position,
            priority: new AnnouncementPriority($dto->priority),
            backgroundColor: $dto->backgroundColor,
            textColor: $dto->textColor,
            startsAt: $dto->startsAt,
            endsAt: $dto->endsAt,
            isDismissible: $dto->isDismissible,
        );

        if ($dto->isActive !== $announcement->isActive()) {
            if ($dto->isActive) {
                $announcement->activate();
            } else {
                $announcement->deactivate();
            }
        }

        $this->repository->save($announcement);

        return AnnouncementResponseDTO::fromEntity($announcement);
    }

    public function delete(string $id): void
    {
        $announcementId = AnnouncementId::fromString($id);

        // Verify it exists
        $this->repository->findOrFail($announcementId);

        $this->repository->delete($announcementId);
    }

    public function find(string $id): ?AnnouncementResponseDTO
    {
        $announcement = $this->repository->find(AnnouncementId::fromString($id));

        if ($announcement === null) {
            return null;
        }

        return AnnouncementResponseDTO::fromEntity($announcement);
    }

    /**
     * @return array<AnnouncementResponseDTO>
     */
    public function all(): array
    {
        $announcements = $this->repository->all();

        return array_map(
            fn (Announcement $announcement) => AnnouncementResponseDTO::fromEntity($announcement),
            $announcements
        );
    }

    /**
     * @return array<AnnouncementResponseDTO>
     */
    public function getActiveForUser(?object $user): array
    {
        $activeAnnouncements = $this->repository->findActive();

        // Filter by visibility and current validity
        $visibleAnnouncements = array_filter(
            $activeAnnouncements,
            fn (Announcement $announcement) => $announcement->isCurrentlyValid() && $announcement->isVisibleTo($user)
        );

        // Re-index after filter and sort by priority (highest first)
        $visibleAnnouncements = array_values($visibleAnnouncements);
        usort(
            $visibleAnnouncements,
            fn (Announcement $a, Announcement $b) => $b->priority()->value <=> $a->priority()->value
        );

        return array_map(
            fn (Announcement $announcement) => AnnouncementResponseDTO::fromEntity($announcement),
            $visibleAnnouncements
        );
    }

    public function activate(string $id): void
    {
        $announcement = $this->repository->findOrFail(AnnouncementId::fromString($id));
        $announcement->activate();
        $this->repository->save($announcement);
    }

    public function deactivate(string $id): void
    {
        $announcement = $this->repository->findOrFail(AnnouncementId::fromString($id));
        $announcement->deactivate();
        $this->repository->save($announcement);
    }
}

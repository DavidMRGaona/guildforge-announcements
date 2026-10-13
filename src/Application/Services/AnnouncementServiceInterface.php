<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\DTOs\CreateAnnouncementDTO;
use Modules\Announcements\Application\DTOs\UpdateAnnouncementDTO;

interface AnnouncementServiceInterface
{
    public function create(CreateAnnouncementDTO $dto): AnnouncementResponseDTO;

    public function update(UpdateAnnouncementDTO $dto): AnnouncementResponseDTO;

    public function delete(string $id): void;

    public function find(string $id): ?AnnouncementResponseDTO;

    /**
     * @return array<AnnouncementResponseDTO>
     */
    public function all(): array;

    /**
     * Get active announcements visible to the given user.
     *
     * @return array<AnnouncementResponseDTO>
     */
    public function getActiveForUser(?object $user): array;

    /**
     * Get an announcement only if it is currently valid and visible to the given user.
     */
    public function findVisibleForUser(string $id, ?object $user): ?AnnouncementResponseDTO;

    public function activate(string $id): void;

    public function deactivate(string $id): void;
}

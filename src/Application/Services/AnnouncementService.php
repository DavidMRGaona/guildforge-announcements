<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use App\Application\Authorization\Services\AssociationMembershipServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\DTOs\CreateAnnouncementDTO;
use Modules\Announcements\Application\DTOs\UpdateAnnouncementDTO;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Domain\Repositories\PopupViewRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;
use Modules\Announcements\Domain\ValueObjects\PopupView;

final readonly class AnnouncementService implements AnnouncementServiceInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private AssociationMembershipServiceInterface $associationMembership,
        private PopupViewRepositoryInterface $popupViews,
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
            format: $dto->format,
            popup: $dto->popup,
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
            format: $dto->format,
            popup: $dto->popup,
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

        if (! $announcement instanceof Announcement) {
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
            AnnouncementResponseDTO::fromEntity(...),
            $announcements
        );
    }

    /**
     * @return array<AnnouncementResponseDTO>
     */
    public function getActiveForUser(?object $user, ?AnnouncementFormat $format = null): array
    {
        $activeAnnouncements = $this->repository->findActive();

        if ($format instanceof AnnouncementFormat) {
            $activeAnnouncements = array_values(array_filter(
                $activeAnnouncements,
                static fn (Announcement $announcement): bool => $announcement->format() === $format,
            ));
        }

        $isMember = $this->isMemberIfNeeded($user, $activeAnnouncements);

        // Filter by visibility and current validity
        $visibleAnnouncements = array_filter(
            $activeAnnouncements,
            fn (Announcement $announcement) => $this->isShownTo($announcement, $user, $isMember)
        );

        // Highest priority first; on a tie, the one that started most recently (the browser opens
        // the first window that fits the page, so this order is also which window wins)
        $visibleAnnouncements = array_values($visibleAnnouncements);
        usort(
            $visibleAnnouncements,
            static fn (Announcement $a, Announcement $b): int => [$b->priority()->value, $b->startedAt()?->getTimestamp() ?? PHP_INT_MIN]
                <=> [$a->priority()->value, $a->startedAt()?->getTimestamp() ?? PHP_INT_MIN]
        );

        $seen = $this->idsOfWindowsAlreadySeen($visibleAnnouncements, $user);

        return array_map(
            static fn (Announcement $announcement): AnnouncementResponseDTO => AnnouncementResponseDTO::fromEntity(
                $announcement,
                isset($seen[$announcement->id()->value]),
            ),
            $visibleAnnouncements
        );
    }

    /**
     * The windows a signed-in account does not get again because its memory says they are not due
     * (announcements#68: seen once, or seen less than N days ago), by id. They are still shared,
     * marked as seen: a window that is open keeps being offered while the person moves around, and
     * the browser just does not open it again. Guests are left alone: only their browser knows.
     * Banners never go through it, and the memory is read once, only when there are windows.
     *
     * @param  list<Announcement>  $announcements
     * @return array<string, true>
     */
    private function idsOfWindowsAlreadySeen(array $announcements, ?object $user): array
    {
        if (! $user instanceof Authenticatable) {
            return [];
        }

        $windowIds = [];

        foreach ($announcements as $announcement) {
            if ($announcement->isPopup()) {
                $windowIds[] = $announcement->id()->value;
            }
        }

        if ($windowIds === []) {
            return [];
        }

        $views = $this->popupViews->forUser((string) $user->getAuthIdentifier(), $windowIds);

        if ($views === []) {
            return [];
        }

        $now = CarbonImmutable::now();
        $seen = [];

        foreach ($announcements as $announcement) {
            $popup = $announcement->popup();
            $id = $announcement->id()->value;

            if ($popup instanceof AnnouncementPopup && ! PopupView::isDue($popup, $views[$id] ?? null, $now)) {
                $seen[$id] = true;
            }
        }

        return $seen;
    }

    public function findVisibleForUser(string $id, ?object $user): ?AnnouncementResponseDTO
    {
        $announcement = $this->repository->find(AnnouncementId::fromString($id));

        if (! $announcement instanceof Announcement) {
            return null;
        }

        $isMember = $this->isMemberIfNeeded($user, [$announcement]);

        if (! $this->isShownTo($announcement, $user, $isMember)) {
            return null;
        }

        return AnnouncementResponseDTO::fromEntity($announcement);
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

    private function isShownTo(Announcement $announcement, ?object $user, bool $isMember): bool
    {
        return $announcement->isCurrentlyValid() && $announcement->isVisibleTo($user, $isMember);
    }

    /**
     * A member is an association member: a user holding the role configured in the core
     * site settings ("socio" by default), never the "member" role every registered user
     * has. Admins get no exception. The role is only looked up when a members-only
     * announcement is among the candidates.
     *
     * @param  array<Announcement>  $announcements
     */
    private function isMemberIfNeeded(?object $user, array $announcements): bool
    {
        if ($user === null) {
            return false;
        }

        foreach ($announcements as $announcement) {
            if ($announcement->visibility() === AnnouncementVisibility::Members) {
                return $this->associationMembership->isAssociationMember($user);
            }
        }

        return false;
    }
}

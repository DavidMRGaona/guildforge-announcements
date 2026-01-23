<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\Exceptions\AnnouncementNotFoundException;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

final class EloquentAnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function save(Announcement $announcement): void
    {
        AnnouncementModel::updateOrCreate(
            ['id' => $announcement->id()->value],
            [
                'title' => $announcement->title(),
                'content' => $announcement->content(),
                'visibility' => $announcement->visibility()->value,
                'position' => $announcement->position()->value,
                'priority' => $announcement->priority()->value,
                'background_color' => $announcement->backgroundColor(),
                'text_color' => $announcement->textColor(),
                'starts_at' => $announcement->startsAt(),
                'ends_at' => $announcement->endsAt(),
                'is_active' => $announcement->isActive(),
                'is_dismissible' => $announcement->isDismissible(),
            ]
        );
    }

    public function find(AnnouncementId $id): ?Announcement
    {
        $model = AnnouncementModel::find($id->value);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findOrFail(AnnouncementId $id): Announcement
    {
        $announcement = $this->find($id);

        if ($announcement === null) {
            throw AnnouncementNotFoundException::withId($id);
        }

        return $announcement;
    }

    public function delete(AnnouncementId $id): void
    {
        AnnouncementModel::destroy($id->value);
    }

    /**
     * @return array<Announcement>
     */
    public function all(): array
    {
        return AnnouncementModel::orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (AnnouncementModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * @return array<Announcement>
     */
    public function findActive(): array
    {
        return AnnouncementModel::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (AnnouncementModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(AnnouncementModel $model): Announcement
    {
        // Note: visibility and position are cast to enum by Eloquent
        /** @var AnnouncementVisibility $visibility */
        $visibility = $model->visibility;
        // Default to BeforeHeader if position is null (for legacy records)
        $position = $model->position ?? AnnouncementPosition::BeforeHeader;

        return new Announcement(
            id: AnnouncementId::fromString($model->id),
            title: $model->title,
            content: $model->content,
            visibility: $visibility,
            position: $position,
            priority: new AnnouncementPriority($model->priority),
            backgroundColor: $model->background_color,
            textColor: $model->text_color,
            startsAt: $model->starts_at !== null
                ? DateTimeImmutable::createFromMutable($model->starts_at->toDateTime())
                : null,
            endsAt: $model->ends_at !== null
                ? DateTimeImmutable::createFromMutable($model->ends_at->toDateTime())
                : null,
            isActive: $model->is_active,
            isDismissible: $model->is_dismissible ?? true,
            createdAt: $model->created_at !== null
                ? DateTimeImmutable::createFromMutable($model->created_at->toDateTime())
                : null,
            updatedAt: $model->updated_at !== null
                ? DateTimeImmutable::createFromMutable($model->updated_at->toDateTime())
                : null,
        );
    }
}

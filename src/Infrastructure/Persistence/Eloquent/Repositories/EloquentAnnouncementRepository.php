<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories;

use App\Infrastructure\Support\SanitizesHtml;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Announcements\Application\Support\PopupAttributes;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\Exceptions\AnnouncementNotFoundException;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

final class EloquentAnnouncementRepository implements AnnouncementRepositoryInterface
{
    use SanitizesHtml;

    /**
     * A banner leaves the window settings as they are, so turning it back into a window finds them.
     */
    public function save(Announcement $announcement): void
    {
        $popup = $announcement->popup();

        AnnouncementModel::updateOrCreate(
            ['id' => $announcement->id()->value],
            [
                'format' => $announcement->format()->value,
                ...($popup instanceof AnnouncementPopup ? PopupAttributes::toAttributes($popup) : []),
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

        if (! $announcement instanceof Announcement) {
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
            ->map(fn (AnnouncementModel $model): ?Announcement => $this->toEntity($model))
            ->filter()
            ->values()
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
            ->map(fn (AnnouncementModel $model): ?Announcement => $this->toEntity($model))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * A row this version cannot read (a format, visibility, position or priority it does not
     * know, written by hand or by a newer version) is left out with a warning, so one bad row
     * never breaks every page that shares the announcements.
     */
    private function toEntity(AnnouncementModel $model): ?Announcement
    {
        // Read raw: the enum casts would throw on an unknown value
        $raw = $model->getAttributes();
        $visibility = AnnouncementVisibility::tryFrom((string) ($raw['visibility'] ?? AnnouncementVisibility::Public->value));
        // Default to BeforeHeader if position is null (for legacy records)
        $position = AnnouncementPosition::tryFrom((string) ($raw['position'] ?? AnnouncementPosition::BeforeHeader->value));
        $format = AnnouncementFormat::tryFrom((string) ($raw['format'] ?? AnnouncementFormat::Banner->value));

        if (! $visibility instanceof AnnouncementVisibility || ! $position instanceof AnnouncementPosition || ! $format instanceof AnnouncementFormat) {
            return $this->unreadable($model, 'unknown format, visibility or position');
        }

        try {
            $priority = new AnnouncementPriority($model->priority);
        } catch (InvalidArgumentException) {
            return $this->unreadable($model, 'priority out of range');
        }

        return new Announcement(
            id: AnnouncementId::fromString($model->id),
            title: $model->title,
            // The rich editor's value can be tampered with and AnnouncementCard renders it as HTML
            content: $this->sanitizeHtml($model->content),
            visibility: $visibility,
            position: $position,
            priority: $priority,
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
            format: $format,
            popup: $format === AnnouncementFormat::Popup ? $this->popupOf($model) : null,
        );
    }

    private function unreadable(AnnouncementModel $model, string $reason): null
    {
        Log::warning('An announcement cannot be read and is left out.', [
            'announcement_id' => $model->id,
            'reason' => $reason,
        ]);

        return null;
    }

    /**
     * The panel validates every window setting, but a row edited by hand can hold anything: such
     * a window gets no settings, and a window without settings is never shown.
     */
    private function popupOf(AnnouncementModel $model): ?AnnouncementPopup
    {
        try {
            return PopupAttributes::toPopup($model->only(PopupAttributes::COLUMNS));
        } catch (InvalidArgumentException $exception) {
            Log::warning('An announcement window has settings it cannot use and is not shown.', [
                'announcement_id' => $model->id,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}

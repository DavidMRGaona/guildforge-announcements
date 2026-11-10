<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Media;

use App\Application\Media\Contracts\MediaUsageSourceInterface;
use App\Application\Media\DTOs\MediaUsageDTO;
use App\Domain\Media\ValueObjects\MediaFileId;
use App\Infrastructure\Media\Sources\StoredMediaIdPrefilter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Announcements\Domain\Content\ContentImages;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Infrastructure\Media\ContentMediaFileIds;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

/**
 * Files of this site whose delivery URL sits inside an announcement's text: the images uploaded from its
 * editor ("announcements/content/", the folder it claims) and any other URL of this site pasted in it.
 * Announcements have no trash, so a deleted one uses nothing.
 */
final readonly class AnnouncementContentMediaSource implements MediaUsageSourceInterface
{
    private const string COLUMN = 'content';

    /** Announcements read at a time */
    private const int CHUNK_SIZE = 200;

    public function __construct(
        private ContentMediaFileIds $fileIds,
    ) {}

    public function type(): string
    {
        return ContentImages::MEDIA_TYPE;
    }

    public function label(): string
    {
        return __('announcements::announcements.media.source');
    }

    public function folders(): array
    {
        return [ContentImages::FOLDER];
    }

    public function excludedSubfolders(): array
    {
        return [];
    }

    public function hidden(): bool
    {
        return false;
    }

    /**
     * @return iterable<MediaUsageDTO>
     */
    public function usages(): iterable
    {
        $announcements = AnnouncementModel::query()
            ->whereNotNull(self::COLUMN)
            ->where(self::COLUMN, '!=', '')
            ->lazyById(self::CHUNK_SIZE);

        foreach ($announcements as $announcement) {
            yield from $this->usagesOf($announcement);
        }
    }

    /**
     * @return list<MediaUsageDTO>
     */
    public function usagesFor(string $recordId): array
    {
        $announcement = $this->find($recordId);

        return $announcement instanceof AnnouncementModel ? $this->usagesOf($announcement) : [];
    }

    public function usesFile(MediaFileId $file): bool
    {
        $query = AnnouncementModel::query()->select([self::COLUMN])->whereNotNull(self::COLUMN);
        $like = StoredMediaIdPrefilter::likeClause($query->getQuery()->getGrammar(), self::COLUMN, $file);

        if ($like !== null) {
            // @phpstan-ignore argument.type
            $query->whereRaw($like[0], $like[1]);
        }

        foreach ($query->lazy(self::CHUNK_SIZE) as $announcement) {
            if (isset($this->fileIds->in($announcement->getAttribute(self::COLUMN))[$file->value])) {
                return true;
            }
        }

        return false;
    }

    public function editUrl(string $recordId, object $user): ?string
    {
        $announcement = $this->find($recordId);

        if (! $announcement instanceof AnnouncementModel || ! Gate::forUser($user)->allows('update', $announcement)) {
            return null;
        }

        return AnnouncementResource::getUrl('edit', ['record' => $announcement]);
    }

    private function find(string $recordId): ?AnnouncementModel
    {
        // PostgreSQL rejects a malformed uuid instead of finding nothing
        if (! Str::isUuid($recordId)) {
            return null;
        }

        return AnnouncementModel::query()->whereKey($recordId)->first();
    }

    /**
     * @return list<MediaUsageDTO>
     */
    private function usagesOf(AnnouncementModel $announcement): array
    {
        $usages = [];

        foreach ($this->fileIds->in($announcement->getAttribute(self::COLUMN)) as $url) {
            $usages[] = new MediaUsageDTO(
                storedPath: $url,
                recordId: $announcement->id,
                role: __('announcements::announcements.media.role_in_text'),
                title: $announcement->title,
            );
        }

        return $usages;
    }
}

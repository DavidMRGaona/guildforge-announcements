<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Media;

use App\Application\Media\Contracts\ReleasesMediaThroughCoreInterface;
use App\Infrastructure\Media\Sources\EloquentColumnMediaSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

/**
 * The image of a window (announcements#67), uploaded to "announcements/popups/<year>/<month>/" or
 * chosen from the media library. An announcement turned back into a banner keeps its window
 * settings, image included, so it still uses it. The model releases a replaced or deleted image
 * through the core, which keeps it while anyone else uses it.
 */
final class AnnouncementPopupImageMediaSource extends EloquentColumnMediaSource implements ReleasesMediaThroughCoreInterface
{
    /** Stored with each usage: never rename it */
    public const string TYPE = 'announcements.popup-image';

    /** Where the panel uploads window images (with the year and month below it) */
    public const string FOLDER = 'announcements/popups/';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('announcements::announcements.media.source');
    }

    public function folders(): array
    {
        return [self::FOLDER];
    }

    /**
     * The editor images live in "announcements/content/", a folder of their own outside this one.
     *
     * @return list<string>
     */
    public function excludedSubfolders(): array
    {
        return [];
    }

    protected function modelClass(): string
    {
        return AnnouncementModel::class;
    }

    protected function column(): string
    {
        return 'popup_image_public_id';
    }

    protected function role(): string
    {
        return __('announcements::announcements.media.role_popup_image');
    }

    protected function titleOf(Model $record): string
    {
        return (string) $record->getAttribute('title');
    }

    protected function editUrlOf(Model $record, object $user): ?string
    {
        if (! Gate::forUser($user)->allows('update', $record)) {
            return null;
        }

        return AnnouncementResource::getUrl('edit', ['record' => $record]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Forms;

use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Announcements\Domain\Content\ContentImages;

/**
 * Images inserted in an announcement's text go to the "images" disk (Cloudinary), in the content
 * folder of their month, with a UUID name; only images, as large as the core's image uploads.
 * Same rules as the core's editors (core#481), whose helper only knows the core's origins.
 */
final class ContentEditorImages
{
    public const string DISK = 'images';

    /** No SVG: it can carry scripts */
    public const array ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Kilobytes, as the core's featured images */
    public const int MAX_SIZE = 2048;

    public static function configure(RichEditor $editor): RichEditor
    {
        return $editor
            ->fileAttachments(true)
            ->fileAttachmentsDisk(self::DISK)
            ->fileAttachmentsDirectory(static fn (): string => ContentImages::directory(now()))
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(self::ACCEPTED_TYPES)
            ->fileAttachmentsMaxSize(self::MAX_SIZE)
            ->saveUploadedFileAttachmentUsing(static fn (TemporaryUploadedFile $file): ?string => self::store($file))
            // Built locally: the default asks Cloudinary's Admin API whether each image exists every time
            // the form loads. Null for an image still on the local disk keeps the URL saved in the HTML.
            ->getFileAttachmentUrlUsing(static fn (mixed $file): ?string => self::urlFor($file));
    }

    /**
     * Full delivery URL of an image uploaded from the editor, or null for any other value.
     */
    public static function urlFor(mixed $path): ?string
    {
        if (! is_string($path) || ! ContentImages::isStoredPath($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    private static function store(TemporaryUploadedFile $file): ?string
    {
        $extension = $file->guessExtension() ?? strtolower($file->getClientOriginalExtension());

        $path = $file->storeAs(ContentImages::directory(now()), Str::uuid()->toString().'.'.$extension, [
            'disk' => self::DISK,
            'mimetype' => $file->getMimeType(),
        ]);

        return is_string($path) ? $path : null;
    }
}

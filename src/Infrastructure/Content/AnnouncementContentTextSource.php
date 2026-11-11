<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Content;

use App\Domain\Content\ValueObjects\EditorImageFolder;
use App\Infrastructure\Content\Sources\TableEditorTextSource;

/**
 * The announcements' texts, written with the panel's rich editor: the core's
 * content:move-editor-images-to-cloudinary reads them and moves their local images (core#496).
 */
final class AnnouncementContentTextSource extends TableEditorTextSource
{
    /** Selected with --source=; never rename it */
    public const string TYPE = 'announcements.content';

    public function type(): string
    {
        return self::TYPE;
    }

    public function folder(): EditorImageFolder
    {
        return EditorImageFolder::of('announcements');
    }

    protected function table(): string
    {
        return 'announcements_announcements';
    }

    protected function column(): string
    {
        return 'content';
    }
}

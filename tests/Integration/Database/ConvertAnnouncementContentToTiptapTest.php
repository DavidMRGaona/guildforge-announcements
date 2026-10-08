<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Database;

use App\Application\Content\Services\TrixHtmlConverterInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The conversion itself is the host's (and tested there); this pins what the module does
 * with it: which rows change, the backup, a second run and the way back.
 */
final class ConvertAnnouncementContentToTiptapTest extends ModuleTestCase
{
    private const TRIX = '<div>Primera línea<br>segunda línea</div>';

    private const CONVERTED = '<p>Primera línea<br>segunda línea</p>';

    protected ?string $moduleName = 'announcements';

    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        // Bound before the module is enabled: enabling it runs this migration on an empty table
        $this->app->instance(TrixHtmlConverterInterface::class, new class () implements TrixHtmlConverterInterface {
            public function convert(string $html): string
            {
                return str_replace(['<div>', '</div>'], ['<p>', '</p>'], $html);
            }
        });
        $this->discoverModules();
        $this->enableModule('announcements');

        $this->migration = require dirname(__DIR__, 3).'/database/migrations/2026_10_08_000001_convert_announcement_content_to_tiptap.php';
    }

    public function test_it_converts_trix_content_and_keeps_the_original(): void
    {
        $announcement = $this->announcement(self::TRIX);
        $updatedAt = $announcement->updated_at?->toDateTimeString();

        $this->migration->up();

        $announcement->refresh();
        $this->assertSame(self::CONVERTED, $announcement->content);
        $this->assertSame($updatedAt, $announcement->updated_at?->toDateTimeString());
        $this->assertSame(self::TRIX, DB::table('announcements_trix_content_backup')->where('announcement_id', $announcement->id)->value('content'));
    }

    public function test_content_already_in_the_new_format_is_left_alone(): void
    {
        $announcement = $this->announcement(self::CONVERTED);

        $this->migration->up();

        $this->assertSame(self::CONVERTED, $announcement->refresh()->content);
        $this->assertSame(0, DB::table('announcements_trix_content_backup')->count());
    }

    public function test_running_it_again_keeps_the_original_backup(): void
    {
        $announcement = $this->announcement(self::TRIX);

        $this->migration->up();
        $this->migration->up();

        $this->assertSame(self::CONVERTED, $announcement->refresh()->content);
        $this->assertSame(self::TRIX, DB::table('announcements_trix_content_backup')->where('announcement_id', $announcement->id)->value('content'));
    }

    public function test_down_restores_the_original_content(): void
    {
        $announcement = $this->announcement(self::TRIX);
        $this->migration->up();

        $this->migration->down();

        $this->assertSame(self::TRIX, $announcement->refresh()->content);
        $this->assertFalse(Schema::hasTable('announcements_trix_content_backup'));
    }

    private function announcement(string $content): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Aviso',
            'content' => $content,
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ])->refresh();
    }
}

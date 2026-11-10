<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Infrastructure\Content;

use App\Application\Media\Services\MediaIndexerInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Modules\Announcements\Application\Services\ContentImageMoverInterface;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use RuntimeException;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Images pasted in an announcement's text before the editor used Cloudinary live on the local "public"
 * disk: they move to "announcements/content/<year>/<month>", the HTML points at them there and the
 * local copy goes once every announcement using it is saved.
 */
final class ContentImageMoverTest extends ModuleTestCase
{
    private const string UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    private const string TABLE = 'announcements_announcements';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    private Filesystem $images;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-10 12:00:00');
        Storage::fake('public');
        $this->images = Storage::fake('images', ['url' => 'https://res.cloudinary.com/demo/image/upload/guildforge']);
    }

    public function test_an_image_moves_to_cloudinary(): void
    {
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes', '2025-03-05 10:00:00');
        $announcement = $this->announcement('<p>Hola</p><p><img src="'.$this->localUrl('3fJk2Lm9Qx.png').'" data-id="3fJk2Lm9Qx.png" alt="Cartel"></p>');
        $updatedAt = $this->updatedAt($announcement->id);

        $report = $this->mover()->move();

        $path = $this->onlyImage();
        $this->assertMatchesRegularExpression('~^announcements/content/2025/03/'.self::UUID.'\.png$~', $path);
        $this->assertSame('png-bytes', $this->images->get($path));
        $this->assertSame('<p>Hola</p><p><img src="'.$this->images->url($path).'" data-id="'.$path.'" alt="Cartel"></p>', $this->content($announcement->id));
        Storage::disk('public')->assertMissing('3fJk2Lm9Qx.png');
        $this->assertSame($updatedAt, $this->updatedAt($announcement->id));
        $this->assertSame(1, $report->checked);
        $this->assertSame(['announcements#'.$announcement->id.': '.$this->localUrl('3fJk2Lm9Qx.png').' → '.$path], $report->moved);
        $this->assertSame([], $report->broken);
        $this->assertSame([], $report->failed);
    }

    public function test_a_relative_url_without_id_moves_and_gains_its_id(): void
    {
        $this->localFile('trix/foto.jpg', 'jpg-bytes');
        $announcement = $this->announcement('<p><img src="/storage/trix/foto.jpg" alt="Foto"></p>');

        $this->mover()->move();

        $path = $this->onlyImage();
        $this->assertMatchesRegularExpression('~^announcements/content/2026/11/'.self::UUID.'\.jpg$~', $path);
        $this->assertSame('<p><img src="'.$this->images->url($path).'" alt="Foto" data-id="'.$path.'"></p>', $this->content($announcement->id));
        Storage::disk('public')->assertMissing('trix/foto.jpg');
    }

    public function test_a_moved_announcement_has_its_media_usages_refreshed(): void
    {
        $this->localFile('a.png', 'a');
        $announcement = $this->announcement('<p><img src="/storage/a.png"></p>');
        $indexer = Mockery::mock(MediaIndexerInterface::class);
        $indexer->shouldReceive('refresh')->once()->with('announcements.content', $announcement->id);
        $this->app->instance(MediaIndexerInterface::class, $indexer);

        $this->mover()->move();
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes');
        $html = '<p><img src="/storage/3fJk2Lm9Qx.png" data-id="3fJk2Lm9Qx.png"></p>';
        $announcement = $this->announcement($html);
        $indexer = Mockery::mock(MediaIndexerInterface::class);
        $indexer->shouldNotReceive('refresh');
        $this->app->instance(MediaIndexerInterface::class, $indexer);

        $report = $this->mover()->move(dryRun: true);

        $this->assertSame($html, $this->content($announcement->id));
        Storage::disk('public')->assertExists('3fJk2Lm9Qx.png');
        $this->assertSame([], $this->images->allFiles());
        $this->assertCount(1, $report->moved);
        $this->assertMatchesRegularExpression('~^announcements#'.$announcement->id.': /storage/3fJk2Lm9Qx\.png → announcements/content/2026/11/'.self::UUID.'\.png$~', $report->moved[0]);
        $this->assertSame([], $report->failed);
    }

    public function test_a_second_run_finds_nothing_to_move(): void
    {
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes');
        $announcement = $this->announcement('<p><img src="/storage/3fJk2Lm9Qx.png" data-id="3fJk2Lm9Qx.png"></p>');
        $this->mover()->move();
        $moved = $this->content($announcement->id);

        $report = $this->mover()->move();

        $this->assertSame($moved, $this->content($announcement->id));
        $this->assertSame([], $report->moved);
        $this->assertSame([], $report->broken);
        $this->assertSame([], $report->failed);
        $this->assertCount(1, $this->images->allFiles());
    }

    public function test_the_same_file_uploaded_again_lands_on_the_same_asset(): void
    {
        // A run cut between the upload and the save uploads again: the asset is overwritten, not duplicated
        $html = '<p><img src="/storage/3fJk2Lm9Qx.png"></p>';
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes');
        $announcement = $this->announcement($html);
        $this->mover()->move();
        $moved = $this->content($announcement->id);

        DB::table(self::TABLE)->where('id', $announcement->id)->update(['content' => $html]);
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes');
        $this->mover()->move();

        $this->assertSame($moved, $this->content($announcement->id));
        $this->assertCount(1, $this->images->allFiles());
    }

    public function test_a_failed_upload_keeps_the_html_and_the_local_copy(): void
    {
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes');
        $html = '<p><img src="/storage/3fJk2Lm9Qx.png" data-id="3fJk2Lm9Qx.png"></p>';
        $announcement = $this->announcement($html);
        $images = Mockery::mock($this->images);
        $images->shouldReceive('put')->andThrow(new RuntimeException('Upload refused'));
        Storage::set('images', $images);

        $report = $this->mover()->move();

        $this->assertSame($html, $this->content($announcement->id));
        Storage::disk('public')->assertExists('3fJk2Lm9Qx.png');
        $this->assertSame([], $report->moved);
        $this->assertSame(['announcements#'.$announcement->id.': /storage/3fJk2Lm9Qx.png (Upload refused)'], $report->failed);
    }

    public function test_an_upload_answering_false_is_a_failure(): void
    {
        $this->localFile('3fJk2Lm9Qx.png', 'png-bytes');
        $html = '<p><img src="/storage/3fJk2Lm9Qx.png"></p>';
        $announcement = $this->announcement($html);
        $images = Mockery::mock($this->images);
        $images->shouldReceive('put')->andReturnFalse();
        Storage::set('images', $images);

        $report = $this->mover()->move();

        $this->assertSame($html, $this->content($announcement->id));
        Storage::disk('public')->assertExists('3fJk2Lm9Qx.png');
        $this->assertCount(1, $report->failed);
    }

    public function test_a_shared_file_stays_until_every_announcement_using_it_is_saved(): void
    {
        $this->localFile('shared.png', 'png-bytes');
        $first = $this->announcement('<p><img src="/storage/shared.png"></p>');
        $second = $this->announcement('<p>Otro</p><p><img src="/storage/shared.png"></p>');
        $images = $this->images;
        $uploads = 0;
        $changing = Mockery::mock($images);
        // The file is uploaded once, while the first announcement moves; someone saves the second one meanwhile
        $changing->shouldReceive('put')->andReturnUsing(static function (string $path, string $contents) use ($images, $second, &$uploads): bool {
            $uploads++;
            DB::table(self::TABLE)->where('id', $second->id)->update(['content' => '<p>Cambiado</p>']);

            return $images->put($path, $contents);
        });
        Storage::set('images', $changing);

        $report = $this->mover()->move();

        $this->assertStringContainsString('/announcements/content/', $this->content($first->id));
        $this->assertSame('<p>Cambiado</p>', $this->content($second->id));
        Storage::disk('public')->assertExists('shared.png');
        $this->assertSame(1, $uploads);
        $this->assertCount(1, $report->moved);
        $this->assertSame(['announcements#'.$second->id.' (Changed while moving)'], $report->failed);
    }

    public function test_a_shared_file_goes_once_every_announcement_is_saved(): void
    {
        $this->localFile('shared.png', 'png-bytes');
        $first = $this->announcement('<p><img src="/storage/shared.png"></p>');
        $second = $this->announcement('<p><img src="/storage/shared.png"></p>');

        $report = $this->mover()->move();

        $this->assertSame($this->content($first->id), $this->content($second->id));
        Storage::disk('public')->assertMissing('shared.png');
        $this->assertCount(1, $this->images->allFiles());
        $this->assertCount(2, $report->moved);
    }

    public function test_broken_references_are_reported_and_left_alone(): void
    {
        $this->localFile('ok.png', 'png-bytes');
        $this->localFile('notes.txt', 'text');
        $announcement = $this->announcement('<p><img src="/storage/missing.png"><img src="/storage/../.env"><img src="/storage/notes.txt"><img src="/storage/ok.png"></p>');

        $report = $this->mover()->move();

        $content = $this->content($announcement->id);
        $this->assertStringContainsString('<img src="/storage/missing.png">', $content);
        $this->assertStringContainsString('<img src="/storage/../.env">', $content);
        $this->assertStringContainsString('<img src="/storage/notes.txt">', $content);
        $this->assertStringNotContainsString('/storage/ok.png', $content);
        $this->assertSame([
            'announcements#'.$announcement->id.': /storage/missing.png',
            'announcements#'.$announcement->id.': /storage/../.env',
            'announcements#'.$announcement->id.': /storage/notes.txt',
        ], $report->broken);
        $this->assertCount(1, $report->moved);
        Storage::disk('public')->assertExists('notes.txt');
    }

    public function test_other_images_are_not_touched(): void
    {
        $stored = 'announcements/content/2026/11/0b7e7c1e-6a0f-4c56-9d0e-1b2f3a4c5d6e.png';
        $html = '<p><img src="https://example.org/storage/foto.png"><img src="'.$this->images->url($stored).'" data-id="'.$stored.'">'
            .'<img src="data:image/png;base64,AAAA"><img alt="sin origen"></p>';
        $announcement = $this->announcement($html);

        $report = $this->mover()->move();

        $this->assertSame($html, $this->content($announcement->id));
        $this->assertSame([], $report->moved);
        $this->assertSame([], $report->broken);
    }

    private function mover(): ContentImageMoverInterface
    {
        return app(ContentImageMoverInterface::class);
    }

    private function localFile(string $path, string $contents, ?string $modifiedAt = null): void
    {
        Storage::disk('public')->put($path, $contents);

        if ($modifiedAt !== null) {
            touch(Storage::disk('public')->path($path), (int) strtotime($modifiedAt.' UTC'));
        }
    }

    private function localUrl(string $path): string
    {
        return rtrim((string) config('filesystems.disks.public.url'), '/').'/'.$path;
    }

    private function announcement(string $content): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => $content,
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);
    }

    private function onlyImage(): string
    {
        $files = $this->images->allFiles();
        $this->assertCount(1, $files);

        return $files[0];
    }

    private function content(string $id): string
    {
        return (string) DB::table(self::TABLE)->where('id', $id)->value('content');
    }

    private function updatedAt(string $id): mixed
    {
        return DB::table(self::TABLE)->where('id', $id)->value('updated_at');
    }
}

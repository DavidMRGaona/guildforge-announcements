<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Infrastructure\Content;

use App\Application\Content\DTOs\EditorTextDTO;
use App\Application\Content\Services\EditorImageMoverInterface;
use App\Application\Content\Services\EditorTextSourceRegistryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Announcements\Infrastructure\Content\AnnouncementContentTextSource;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The announcements' texts are an editor text source of the core (core#496): the core's
 * content:move-editor-images-to-cloudinary reads them and moves their local images.
 */
final class AnnouncementContentTextSourceTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    public function test_it_is_registered_with_the_announcements_folder(): void
    {
        $source = app(EditorTextSourceRegistryInterface::class)->source('announcements.content');

        $this->assertInstanceOf(AnnouncementContentTextSource::class, $source);
        $this->assertSame('announcements/content/', $source->folder()->claim());
    }

    public function test_texts_are_every_announcement_with_a_text(): void
    {
        $active = $this->announcement('<p>Abierto</p>');
        $inactive = $this->announcement('<p>Retirado</p>', ['is_active' => false]);
        $expired = $this->announcement('<p>Pasado</p>', ['ends_at' => Carbon::parse('2020-01-01 00:00:00')]);
        $this->announcement('');

        $texts = $this->texts();

        $this->assertSame([
            $active->id => '<p>Abierto</p>',
            $inactive->id => '<p>Retirado</p>',
            $expired->id => '<p>Pasado</p>',
        ], array_map(static fn (EditorTextDTO $text): string => $text->html, $texts));
        $this->assertSame('announcements_announcements.content#'.$active->id, $texts[$active->id]->label);
    }

    public function test_replace_writes_the_text_without_touching_updated_at(): void
    {
        $announcement = $this->announcement('<p><img src="/storage/a.png"></p>');
        DB::table('announcements_announcements')->where('id', $announcement->id)->update(['updated_at' => '2026-01-01 10:00:00']);

        $written = $this->source()->replace($this->texts()[$announcement->id], '<p><img src="https://cdn.example/a.png"></p>');

        $this->assertTrue($written);
        $row = DB::table('announcements_announcements')->where('id', $announcement->id)->first(['content', 'updated_at']);
        $this->assertNotNull($row);
        $this->assertSame('<p><img src="https://cdn.example/a.png"></p>', $row->content);
        $this->assertSame('2026-01-01 10:00:00', $row->updated_at);
    }

    public function test_replace_never_overwrites_a_text_saved_meanwhile(): void
    {
        $announcement = $this->announcement('<p><img src="/storage/a.png"></p>');
        $read = $this->texts()[$announcement->id];
        $announcement->update(['content' => '<p>Texto nuevo</p>']);

        $this->assertFalse($this->source()->replace($read, '<p><img src="https://cdn.example/a.png"></p>'));
        $this->assertSame('<p>Texto nuevo</p>', $announcement->refresh()->content);
    }

    public function test_the_core_mover_moves_the_local_images_of_this_source(): void
    {
        Storage::fake('public')->put('a.png', 'png-bytes');
        $images = Storage::fake('images', ['url' => 'https://res.cloudinary.com/demo/image/upload/guildforge']);
        $announcement = $this->announcement('<p><img src="/storage/a.png"></p>');
        $mover = app(EditorImageMoverInterface::class);

        $dryRun = $mover->move(dryRun: true, source: 'announcements.content');

        $this->assertCount(1, $dryRun->moved);
        $this->assertSame([], Storage::disk('images')->allFiles());

        $report = $mover->move(source: 'announcements.content');

        $this->assertCount(1, $report->moved);
        $this->assertSame([], $report->failed);
        $uploaded = $images->allFiles();
        $this->assertCount(1, $uploaded);
        $this->assertStringStartsWith('announcements/content/', $uploaded[0]);
        $this->assertStringContainsString('/announcements/content/', (string) DB::table('announcements_announcements')->where('id', $announcement->id)->value('content'));
    }

    private function source(): AnnouncementContentTextSource
    {
        return app(AnnouncementContentTextSource::class);
    }

    /**
     * @return array<string, EditorTextDTO>
     */
    private function texts(): array
    {
        $texts = [];

        foreach ($this->source()->texts() as $text) {
            $texts[$text->id] = $text;
        }

        return $texts;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function announcement(string $content, array $attributes = []): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Aviso',
            'content' => $content,
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            ...$attributes,
        ]);
    }
}

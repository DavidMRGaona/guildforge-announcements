<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Filament\Media;

use App\Application\Media\Contracts\ShowsMediaPreviews;
use App\Application\Media\Services\MediaCatalogInterface;
use App\Application\Media\Services\MediaDeletionServiceInterface;
use App\Application\Media\Services\MediaFileIdResolverInterface;
use App\Application\Media\Services\MediaIndexerInterface;
use App\Application\Media\Services\MediaSourceRegistryInterface;
use App\Domain\Media\Enums\MediaDeletionRefusal;
use App\Domain\Media\ValueObjects\MediaFileId;
use App\Infrastructure\Media\Persistence\Eloquent\Models\MediaDeletionModel;
use App\Infrastructure\Media\Persistence\Eloquent\Models\MediaFileModel;
use App\Infrastructure\Media\Persistence\Eloquent\Models\MediaUsageModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery;
use Modules\Announcements\Filament\Media\AnnouncementContentMediaSource;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Authorization\CreatesUsersWithPermissions;
use Tests\Support\Media\InMemoryMediaCatalog;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementContentMediaSourceTest extends ModuleTestCase
{
    use CreatesUsersWithPermissions;
    use InteractsWithModulePanel;

    /** Delivery URL base of this site (the test prefix is the default one, guildforge) */
    private const string BASE = 'https://res.cloudinary.com/demo/image/upload/';

    private const string FILE = 'announcements/content/2026/11/0b6f2c4e-5d1a-4c3b-9e7f-1a2b3c4d5e6f';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
    }

    public function test_declares_its_type_label_and_folder(): void
    {
        $source = $this->source();

        $this->assertSame('announcements.content', $source->type());
        $this->assertSame('Anuncios', $source->label());
        $this->assertSame(['announcements/content/'], $source->folders());
        $this->assertSame([], $source->excludedSubfolders());
        $this->assertFalse($source->hidden());
    }

    public function test_it_is_registered_and_claims_the_editor_images(): void
    {
        $registry = app(MediaSourceRegistryInterface::class);

        $this->assertInstanceOf(AnnouncementContentMediaSource::class, $registry->source('announcements.content'));
        $this->assertSame('announcements', $registry->moduleOf('announcements.content'));
        $this->assertSame('announcements.content', $registry->claimingSource(new MediaFileId(self::FILE))?->type());
        $this->assertNull($registry->claimingSource(new MediaFileId('announcements-old/a')));
        $this->assertSame([], $registry->unresolved());
    }

    public function test_it_shows_its_previews(): void
    {
        $this->assertInstanceOf(ShowsMediaPreviews::class, app(MediaSourceRegistryInterface::class)->source('announcements.content'));
    }

    public function test_the_images_of_a_members_only_announcement_are_hidden_under_a_neutral_title(): void
    {
        $announcement = $this->announcement('Asamblea extraordinaria', $this->image(), ['visibility' => 'members']);

        $usages = $this->source()->usagesFor($announcement->id);

        $this->assertCount(1, $usages);
        $this->assertTrue($usages[0]->hidden);
        $this->assertSame('Anuncio solo para socios', $usages[0]->title);
        // Still a use, and still edited by whoever may edit it
        $this->assertTrue($this->source()->usesFile(new MediaFileId(self::FILE)));
        $this->assertSame(
            AnnouncementResource::getUrl('edit', ['record' => $announcement]),
            $this->source()->editUrl($announcement->id, $this->editorWithPermissions(['announcements:announcements.view_any', 'announcements:announcements.update'])),
        );
    }

    /**
     * Every announcement is written by the team in the panel: one not shown yet, or no longer, is theirs
     * as much as an unpublished event, and the others may be seen by anybody signing up or signing out.
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function shownAnnouncements(): array
    {
        return [
            'public' => [['visibility' => 'public']],
            'signed-in users' => [['visibility' => 'authenticated']],
            'guests only' => [['visibility' => 'guests']],
            'inactive' => [['is_active' => false]],
            'scheduled' => [['starts_at' => '2099-01-01 10:00:00']],
            'expired' => [['ends_at' => '2020-01-01 10:00:00']],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    #[DataProvider('shownAnnouncements')]
    public function test_other_announcements_keep_their_images_in_sight(array $attributes): void
    {
        $announcement = $this->announcement('Torneo', $this->image(), $attributes);

        $usages = $this->source()->usagesFor($announcement->id);

        $this->assertCount(1, $usages);
        $this->assertFalse($usages[0]->hidden);
        $this->assertSame('Torneo', $usages[0]->title);
    }

    public function test_changing_the_visibility_refreshes_the_indexed_usages(): void
    {
        $announcement = $this->announcement('Asamblea', $this->image());
        $this->assertSame([['Asamblea', false]], $this->indexedUsages($announcement));

        $announcement->update(['visibility' => 'members']);
        $this->assertSame([['Anuncio solo para socios', true]], $this->indexedUsages($announcement));

        $announcement->update(['visibility' => 'guests']);
        $this->assertSame([['Asamblea', false]], $this->indexedUsages($announcement));
    }

    public function test_an_image_in_the_text_counts(): void
    {
        $announcement = $this->announcement('Cierre por vacaciones', '<p>Mira:</p><img src="'.self::BASE.'v1/guildforge/'.self::FILE.'?_a=BAAHWXH8" alt="">');

        $this->assertTrue($this->source()->usesFile(new MediaFileId(self::FILE)));
        $this->assertFalse($this->source()->usesFile(new MediaFileId(self::FILE.'2')));
        $this->assertSame([[self::FILE, $announcement->id, 'En el texto', 'Cierre por vacaciones']], $this->usages());
    }

    public function test_a_pasted_url_of_another_folder_counts(): void
    {
        $this->announcement('Torneo', '<p>El cartel: «'.self::BASE.'v12/guildforge/events/2026/11/cartel»&nbsp;ya está</p>');

        $this->assertTrue($this->source()->usesFile(new MediaFileId('events/2026/11/cartel')));
    }

    public function test_a_url_of_another_prefix_does_not_count(): void
    {
        $this->announcement('Torneo', '<img src="'.self::BASE.'guildforge-smoke/'.self::FILE.'.jpg"><img src="https://example.com/guildforge/'.self::FILE.'.jpg">');

        $this->assertFalse($this->source()->usesFile(new MediaFileId(self::FILE)));
        $this->assertSame([], $this->usages());
    }

    public function test_usages_for_one_record_and_unknown_records(): void
    {
        $announcement = $this->announcement('Torneo', '<img src="'.self::BASE.'guildforge/events/a.jpg"><img src="'.self::BASE.'guildforge/events/b.jpg">');
        $source = $this->source();

        $this->assertCount(2, $source->usagesFor($announcement->id));
        $this->assertSame([], $source->usagesFor(Str::uuid()->toString()));
        $this->assertSame([], $source->usagesFor('not-a-uuid'));
    }

    public function test_the_same_file_twice_in_one_text_is_one_usage(): void
    {
        $this->announcement('Torneo', '<img src="'.self::BASE.'guildforge/events/a.jpg"><a href="'.self::BASE.'v3/guildforge/events/a.jpg">ver</a>');

        $this->assertCount(1, $this->usages());
    }

    public function test_a_deleted_announcement_no_longer_uses_its_images(): void
    {
        // Announcements have no trash: a deleted one cannot come back
        $announcement = $this->announcement('Torneo', '<img src="'.self::BASE.'guildforge/'.self::FILE.'.jpg">');

        $announcement->delete();

        $this->assertFalse($this->source()->usesFile(new MediaFileId(self::FILE)));
        $this->assertSame([], $this->source()->usagesFor($announcement->id));
    }

    public function test_edit_url_follows_the_update_permission(): void
    {
        $announcement = $this->announcement('Torneo', '<p>x</p>');
        $source = $this->source();

        $this->assertSame(
            AnnouncementResource::getUrl('edit', ['record' => $announcement]),
            $source->editUrl($announcement->id, $this->editorWithPermissions(['announcements:announcements.view_any', 'announcements:announcements.update'])),
        );
        $this->assertNull($source->editUrl($announcement->id, $this->editorWithPermissions(['announcements:announcements.view_any'])));
        $this->assertNull($source->editUrl(Str::uuid()->toString(), $this->editorWithPermissions(['announcements:announcements.update'])));
        $this->assertNull($source->editUrl('not-a-uuid', $this->editorWithPermissions(['announcements:announcements.update'])));
    }

    public function test_the_deletion_service_refuses_a_file_in_an_announcement(): void
    {
        Carbon::setTestNow('2026-11-10 12:00:00');
        config(['media.delete_enabled' => true]);
        $catalog = new InMemoryMediaCatalog;
        $this->app->instance(MediaCatalogInterface::class, $catalog);
        $catalog->add(self::FILE, uploadedAt: '2026-10-01 10:00:00');
        MediaFileModel::query()->create([
            'file_id' => self::FILE,
            'resource_type' => 'image',
            'folder' => 'announcements/content/2026/11/',
            'format' => 'jpg',
            'bytes' => 1000,
            'uploaded_at' => Carbon::parse('2026-10-01 10:00:00'),
            'last_seen_at' => Carbon::now(),
        ]);
        $this->announcement('Torneo', '<p><img src="'.self::BASE.'guildforge/'.self::FILE.'.jpg"></p>');
        // The index lags behind: only the live check knows about the text
        MediaUsageModel::query()->delete();

        $result = app(MediaDeletionServiceInterface::class)->delete(
            [new MediaFileId(self::FILE)],
            $this->editorWithPermissions(['media.view_any', 'media.delete']),
        );

        $this->assertSame(0, $result->deleted);
        $this->assertSame([self::FILE => MediaDeletionRefusal::InUse], $result->refused);
        $this->assertArrayHasKey(self::FILE, $catalog->assets);
        $this->assertSame(0, MediaDeletionModel::query()->count());
    }

    public function test_saving_and_deleting_an_announcement_keeps_its_usages_indexed(): void
    {
        $announcement = $this->announcement('Torneo', '<img src="'.self::BASE.'guildforge/'.self::FILE.'.jpg">');

        $this->assertSame(['Torneo'], $this->indexedTitles($announcement));

        $announcement->update(['title' => 'Torneo de otoño']);
        $this->assertSame(['Torneo de otoño'], $this->indexedTitles($announcement));

        $announcement->update(['content' => '<p>Sin imagen</p>']);
        $this->assertSame([], $this->indexedTitles($announcement));

        $announcement->update(['content' => '<img src="'.self::BASE.'guildforge/'.self::FILE.'.jpg">']);
        $announcement->delete();
        $this->assertSame([], $this->indexedTitles($announcement));
    }

    public function test_saves_that_change_neither_title_nor_text_do_not_refresh(): void
    {
        $announcement = $this->announcement('Torneo', '<p>x</p>');
        $indexer = Mockery::mock(MediaIndexerInterface::class);
        $indexer->shouldNotReceive('refresh');
        $this->app->instance(MediaIndexerInterface::class, $indexer);

        $announcement->update(['is_active' => false, 'priority' => 9]);
    }

    private function source(): AnnouncementContentMediaSource
    {
        return app(AnnouncementContentMediaSource::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function announcement(string $title, string $content, array $attributes = []): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => $title,
            'content' => $content,
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function image(): string
    {
        return '<p><img src="'.self::BASE.'guildforge/'.self::FILE.'.jpg"></p>';
    }

    /**
     * @return list<array{0: string, 1: bool}> title and whether it is hidden
     */
    private function indexedUsages(AnnouncementModel $announcement): array
    {
        /** @var list<array{0: string, 1: bool}> */
        return MediaUsageModel::query()
            ->where('source_type', 'announcements.content')
            ->where('record_id', $announcement->id)
            ->where('file_id', self::FILE)
            ->get()
            ->map(static fn (MediaUsageModel $usage): array => [(string) $usage->title, (bool) $usage->is_hidden])
            ->values()
            ->all();
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}> file id, record id, role and title of every usage
     */
    private function usages(): array
    {
        $rows = [];

        foreach ($this->source()->usages() as $usage) {
            $id = app(MediaFileIdResolverInterface::class)->fromStoredPath($usage->storedPath);
            $rows[] = [$id->value ?? '', $usage->recordId, $usage->role, $usage->title];
        }

        sort($rows);

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function indexedTitles(AnnouncementModel $announcement): array
    {
        /** @var list<string> */
        return MediaUsageModel::query()
            ->where('source_type', 'announcements.content')
            ->where('record_id', $announcement->id)
            ->where('file_id', self::FILE)
            ->pluck('title')
            ->all();
    }
}

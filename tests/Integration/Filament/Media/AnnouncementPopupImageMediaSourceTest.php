<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Filament\Media;

use App\Application\Media\Contracts\ReleasesMediaThroughCoreInterface;
use App\Application\Media\Contracts\ShowsMediaPreviews;
use App\Application\Media\Services\MediaFileReleaserInterface;
use App\Application\Media\Services\MediaSourceRegistryInterface;
use App\Domain\Media\ValueObjects\MediaFileId;
use App\Infrastructure\Media\Persistence\Eloquent\Models\MediaUsageModel;
use Illuminate\Support\Str;
use Mockery;
use Modules\Announcements\Filament\Media\AnnouncementPopupImageMediaSource;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Authorization\CreatesUsersWithPermissions;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The image of a window (announcements#67) lives in the module's own folder, claimed by its own
 * source, so the media library tells what uses it and never deletes it while it is in use.
 */
final class AnnouncementPopupImageMediaSourceTest extends ModuleTestCase
{
    use CreatesUsersWithPermissions;
    use InteractsWithModulePanel;

    private const string FILE = 'announcements/popups/2026/11/0b6f2c4e-5d1a-4c3b-9e7f-1a2b3c4d5e6f';

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

        $this->assertSame('announcements.popup-image', $source->type());
        $this->assertSame('Anuncios', $source->label());
        $this->assertSame(['announcements/popups/'], $source->folders());
        $this->assertSame([], $source->excludedSubfolders());
        $this->assertFalse($source->hidden());
    }

    public function test_it_is_registered_and_claims_the_window_images(): void
    {
        $registry = app(MediaSourceRegistryInterface::class);

        // Its files may be chosen from the library, since it releases them through the core
        $this->assertInstanceOf(ReleasesMediaThroughCoreInterface::class, $registry->source('announcements.popup-image'));
        $this->assertInstanceOf(AnnouncementPopupImageMediaSource::class, $registry->source('announcements.popup-image'));
        $this->assertSame('announcements', $registry->moduleOf('announcements.popup-image'));
        $this->assertSame('announcements.popup-image', $registry->claimingSource(new MediaFileId(self::FILE))?->type());
        $this->assertSame('announcements.content', $registry->claimingSource(new MediaFileId('announcements/content/2026/11/a'))?->type());
        $this->assertSame([], $registry->unresolved());
    }

    public function test_it_shows_its_previews(): void
    {
        $this->assertInstanceOf(ShowsMediaPreviews::class, app(MediaSourceRegistryInterface::class)->source('announcements.popup-image'));
    }

    public function test_the_image_of_a_members_only_announcement_is_hidden_under_a_neutral_title(): void
    {
        $announcement = $this->announcement('Asamblea extraordinaria', self::FILE.'.webp', ['visibility' => 'members']);

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
    public function test_other_announcements_keep_their_image_in_sight(array $attributes): void
    {
        $announcement = $this->announcement('Hazte socio', self::FILE.'.webp', $attributes);

        $usages = $this->source()->usagesFor($announcement->id);

        $this->assertCount(1, $usages);
        $this->assertFalse($usages[0]->hidden);
        $this->assertSame('Hazte socio', $usages[0]->title);
    }

    public function test_changing_the_visibility_refreshes_the_indexed_usage(): void
    {
        $announcement = $this->announcement('Asamblea', self::FILE.'.webp');
        $this->assertSame([['Asamblea', false]], $this->indexedUsages($announcement));

        $announcement->update(['visibility' => 'members']);
        $this->assertSame([['Anuncio solo para socios', true]], $this->indexedUsages($announcement));

        $announcement->update(['visibility' => 'authenticated']);
        $this->assertSame([['Asamblea', false]], $this->indexedUsages($announcement));
    }

    public function test_the_window_image_counts_as_a_use(): void
    {
        $announcement = $this->announcement('Hazte socio', self::FILE.'.webp');
        $this->announcement('Sin imagen', null);

        $this->assertTrue($this->source()->usesFile(new MediaFileId(self::FILE)));
        $this->assertFalse($this->source()->usesFile(new MediaFileId(self::FILE.'2')));

        $usages = $this->source()->usagesFor($announcement->id);
        $this->assertCount(1, $usages);
        $this->assertSame('Imagen de la ventana', $usages[0]->role);
        $this->assertSame('Hazte socio', $usages[0]->title);
        $this->assertSame([], $this->source()->usagesFor(Str::uuid()->toString()));
        $this->assertSame([], $this->source()->usagesFor('not-a-uuid'));
    }

    public function test_an_announcement_turned_back_into_a_banner_still_uses_the_image_it_keeps(): void
    {
        $announcement = $this->announcement('Hazte socio', self::FILE.'.webp');

        $announcement->update(['format' => 'banner']);

        $this->assertTrue($this->source()->usesFile(new MediaFileId(self::FILE)));
    }

    public function test_saving_and_deleting_keeps_the_usages_indexed(): void
    {
        $announcement = $this->announcement('Hazte socio', self::FILE.'.webp');
        $this->assertSame(['Hazte socio'], $this->indexedTitles($announcement));

        $announcement->update(['title' => 'Únete']);
        $this->assertSame(['Únete'], $this->indexedTitles($announcement));

        $announcement->update(['popup_image_public_id' => null]);
        $this->assertSame([], $this->indexedTitles($announcement));

        $announcement->update(['popup_image_public_id' => self::FILE.'.webp']);
        $announcement->delete();
        $this->assertSame([], $this->indexedTitles($announcement));
    }

    public function test_a_replaced_image_is_released_through_the_core(): void
    {
        $announcement = $this->announcement('Hazte socio', self::FILE.'.webp');
        $releaser = Mockery::mock(MediaFileReleaserInterface::class);
        $releaser->shouldReceive('release')->once()->with(self::FILE.'.webp');
        $this->app->instance(MediaFileReleaserInterface::class, $releaser);

        $announcement->update(['popup_image_public_id' => 'announcements/popups/2026/11/nueva.webp']);
    }

    public function test_edit_url_follows_the_update_permission(): void
    {
        $announcement = $this->announcement('Hazte socio', self::FILE.'.webp');

        $this->assertSame(
            AnnouncementResource::getUrl('edit', ['record' => $announcement]),
            $this->source()->editUrl($announcement->id, $this->editorWithPermissions(['announcements:announcements.view_any', 'announcements:announcements.update'])),
        );
        $this->assertNull($this->source()->editUrl($announcement->id, $this->editorWithPermissions(['announcements:announcements.view_any'])));
    }

    private function source(): AnnouncementPopupImageMediaSource
    {
        return app(AnnouncementPopupImageMediaSource::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function announcement(string $title, ?string $image, array $attributes = []): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => $title,
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            'popup_image_public_id' => $image,
            ...$attributes,
        ]);
    }

    /**
     * @return list<array{0: string, 1: bool}> title and whether it is hidden
     */
    private function indexedUsages(AnnouncementModel $announcement): array
    {
        /** @var list<array{0: string, 1: bool}> */
        return MediaUsageModel::query()
            ->where('source_type', 'announcements.popup-image')
            ->where('record_id', $announcement->id)
            ->get()
            ->map(static fn (MediaUsageModel $usage): array => [(string) $usage->title, (bool) $usage->is_hidden])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function indexedTitles(AnnouncementModel $announcement): array
    {
        /** @var list<string> */
        return MediaUsageModel::query()
            ->where('source_type', 'announcements.popup-image')
            ->where('record_id', $announcement->id)
            ->pluck('title')
            ->all();
    }
}

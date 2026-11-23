<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament\Media;

use App\Application\Media\Services\MediaCatalogInterface;
use App\Application\Media\Services\MediaIndexerInterface;
use App\Infrastructure\Media\Persistence\Eloquent\Models\MediaFileModel;
use App\Infrastructure\Media\Services\MediaLibraryQueryInterface;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Media\InMemoryMediaCatalog;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The media library shows the module's images (core#837) except those of an announcement for members
 * only, which only some people may read (announcements#80).
 */
final class AnnouncementMediaPreviewsTest extends ModuleTestCase
{
    /** Delivery URL base of this site (the test prefix is the default one, guildforge) */
    private const string BASE = 'https://res.cloudinary.com/demo/image/upload/';

    private const string OPEN_POPUP = 'announcements/popups/2026/11/open';

    private const string MEMBERS_POPUP = 'announcements/popups/2026/11/members';

    private const string OPEN_TEXT = 'announcements/content/2026/11/open';

    private const string MEMBERS_TEXT = 'announcements/content/2026/11/members';

    private const string UNUSED = 'announcements/content/2026/11/unused';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    private InMemoryMediaCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = new InMemoryMediaCatalog;
        $this->app->instance(MediaCatalogInterface::class, $this->catalog);

        foreach ([self::OPEN_POPUP, self::MEMBERS_POPUP, self::OPEN_TEXT, self::MEMBERS_TEXT, self::UNUSED] as $file) {
            $this->catalog->add($file);
        }
    }

    public function test_images_of_announcements_anybody_may_see_are_shown_and_offered(): void
    {
        $this->announcement('public', self::OPEN_POPUP, self::OPEN_TEXT);
        $this->sync();

        foreach ([self::OPEN_POPUP, self::OPEN_TEXT, self::UNUSED] as $file) {
            $this->assertFalse($this->library()->conceals($this->file($file)), $file);
        }

        // Only the window images may be chosen again: the text images are never released through the core
        $this->assertContains(self::OPEN_POPUP, $this->reusable());
    }

    public function test_images_of_a_members_only_announcement_are_concealed_and_not_offered(): void
    {
        $this->announcement('members', self::MEMBERS_POPUP, self::MEMBERS_TEXT);
        $this->sync();

        $this->assertTrue($this->library()->conceals($this->file(self::MEMBERS_POPUP)));
        $this->assertTrue($this->library()->conceals($this->file(self::MEMBERS_TEXT)));
        $this->assertNotContains(self::MEMBERS_POPUP, $this->reusable());
        // The unused files of the folder stay in sight, to be reviewed before deleting them
        $this->assertFalse($this->library()->conceals($this->file(self::UNUSED)));
    }

    public function test_opening_a_members_only_announcement_to_everybody_shows_its_images(): void
    {
        $announcement = $this->announcement('members', self::MEMBERS_POPUP, self::MEMBERS_TEXT);
        $this->sync();

        $announcement->update(['visibility' => 'authenticated']);

        $this->assertFalse($this->library()->conceals($this->file(self::MEMBERS_POPUP)));
        $this->assertFalse($this->library()->conceals($this->file(self::MEMBERS_TEXT)));

        $announcement->update(['visibility' => 'members']);

        $this->assertTrue($this->library()->conceals($this->file(self::MEMBERS_POPUP)));
        $this->assertTrue($this->library()->conceals($this->file(self::MEMBERS_TEXT)));
    }

    private function announcement(string $visibility, string $popupImage, string $textImage): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Asamblea',
            'content' => '<p><img src="'.self::BASE.'guildforge/'.$textImage.'.jpg"></p>',
            'visibility' => $visibility,
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            'popup_image_public_id' => $popupImage.'.jpg',
        ]);
    }

    private function sync(): void
    {
        $this->assertSame([], app(MediaIndexerInterface::class)->sync()->failedSources);
    }

    /**
     * @return list<string>
     */
    private function reusable(): array
    {
        $library = $this->library();

        /** @var list<string> */
        return $library->applyReusable($library->query())->pluck('file_id')->all();
    }

    private function file(string $id): MediaFileModel
    {
        return MediaFileModel::query()->where('file_id', $id)->firstOrFail();
    }

    private function library(): MediaLibraryQueryInterface
    {
        return app(MediaLibraryQueryInterface::class);
    }
}

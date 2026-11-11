<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Application\Media\Services\MediaFileIdResolverInterface;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Images inserted in an announcement's text go to the "images" disk (Cloudinary), in
 * "announcements/content/<year>/<month>", with a UUID name, through the core's EditorImages (core#481, core#496).
 */
final class ContentEditorImagesTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    private const string UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    private const string PATH = 'announcements/content/2026/11/0b7e7c1e-6a0f-4c56-9d0e-1b2f3a4c5d6e.png';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-10 12:00:00');
        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_the_editor_uploads_images_to_cloudinary(): void
    {
        $editor = $this->editor();

        $this->assertTrue($editor->hasFileAttachments());
        $this->assertSame('images', $editor->getFileAttachmentsDiskName());
        $this->assertSame('announcements/content/2026/11', $editor->getFileAttachmentsDirectory());
        $this->assertSame('public', $editor->getFileAttachmentsVisibility());
        $this->assertSame(['image/jpeg', 'image/png', 'image/webp', 'image/gif'], $editor->getFileAttachmentsAcceptedFileTypes());
        $this->assertSame(2048, $editor->getFileAttachmentsMaxSize());
    }

    public function test_an_upload_is_stored_with_a_uuid_name_on_the_images_disk(): void
    {
        Storage::fake('images');
        Storage::fake('public');

        $path = $this->editor()->saveUploadedFileAttachment($this->upload('Cartel del torneo.png'));

        $this->assertIsString($path);
        $this->assertMatchesRegularExpression('~^announcements/content/2026/11/'.self::UUID.'\.png$~', $path);
        Storage::disk('images')->assertExists($path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_an_image_url_is_the_full_cloudinary_delivery_url_built_without_the_admin_api(): void
    {
        $url = $this->editor()->getFileAttachmentUrl(self::PATH);

        $this->assertSame(Storage::disk('images')->url(self::PATH), $url);
        $this->assertStringStartsWith('https://res.cloudinary.com/', (string) $url);
        $this->assertStringContainsString('/guildforge/announcements/content/2026/11/0b7e7c1e-6a0f-4c56-9d0e-1b2f3a4c5d6e', (string) $url);
    }

    public function test_the_media_library_reads_the_stored_url_as_the_uploaded_file(): void
    {
        $url = (string) $this->editor()->getFileAttachmentUrl(self::PATH);
        $resolver = app(MediaFileIdResolverInterface::class);

        $fromUrl = $resolver->fromStoredPath($url);

        $this->assertNotNull($fromUrl);
        $this->assertSame($resolver->fromStoredPath(self::PATH)?->value, $fromUrl->value);
    }

    public function test_an_image_still_on_the_local_disk_keeps_its_stored_url(): void
    {
        $editor = $this->editor();

        // null makes Filament keep the src saved in the HTML until the images are moved
        $this->assertNull($editor->getFileAttachmentUrl('3fJk2Lm9Qx.png'));
    }

    public function test_an_image_of_another_content_folder_gets_its_delivery_url(): void
    {
        // An image copied from an article keeps pointing at the article's upload
        $path = 'articles/content/2026/11/0b7e7c1e-6a0f-4c56-9d0e-1b2f3a4c5d6e.png';

        $this->assertSame(Storage::disk('images')->url($path), $this->editor()->getFileAttachmentUrl($path));
    }

    public function test_saved_html_holds_the_cloudinary_url(): void
    {
        $announcement = AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->fillForm(['content' => '<p>Texto</p><p><img src="blob:temporal" data-id="'.self::PATH.'" alt="Cartel"></p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $content = $announcement->refresh()->content;
        $this->assertStringContainsString('src="'.Storage::disk('images')->url(self::PATH).'"', $content);
        $this->assertStringContainsString('data-id="'.self::PATH.'"', $content);
    }

    public function test_the_editor_keeps_its_toolbar(): void
    {
        $buttons = array_merge(...array_map(static fn (mixed $group): array => (array) $group, $this->editor()->getToolbarButtons()));

        // Images reach this editor only by pasting or dropping them
        $this->assertNotContains('attachFiles', $buttons);
        $this->assertContains('link', $buttons);
    }

    private function editor(): RichEditor
    {
        $instance = Livewire::test(CreateAnnouncement::class)->instance();
        $this->assertInstanceOf(HasSchemas::class, $instance);
        $form = $instance->getSchema('form');
        $this->assertNotNull($form);
        $editor = $form->getFlatFields(withHidden: true)['content'] ?? null;
        $this->assertInstanceOf(RichEditor::class, $editor);

        return $editor;
    }

    private function upload(string $name): TemporaryUploadedFile
    {
        Storage::fake('tmp-for-tests');
        $stored = 'upload-'.bin2hex(random_bytes(4)).'.png';
        Storage::disk('tmp-for-tests')->putFileAs('livewire-tmp', UploadedFile::fake()->image($name, 8, 8), $stored);

        return new TemporaryUploadedFile($stored, 'tmp-for-tests');
    }
}

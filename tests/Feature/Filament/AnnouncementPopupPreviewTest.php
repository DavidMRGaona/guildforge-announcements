<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupMetricModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupViewModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * «Vista previa» of the form (announcements#69): the window as it would look with what is in the
 * form now, saved or not. It counts nothing and remembers nothing.
 */
final class AnnouncementPopupPreviewTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_the_preview_shows_what_is_in_the_form_without_saving(): void
    {
        $page = $this->previewOf([
            'title' => 'Hazte socio',
            'content' => '<p>Únete a la <strong>asociación</strong>.</p>',
            'popup_shape' => 'corner',
            'popup_action_label' => 'Apúntate',
            'popup_action_url' => '/socios',
        ]);

        $html = $this->previewHtml($page);

        $this->assertStringContainsString('Hazte socio', $html);
        $this->assertStringContainsString('<strong>asociación</strong>', $html);
        $this->assertStringContainsString('Apúntate', $html);
        $this->assertStringContainsString('href="/socios"', $html);
        $this->assertStringContainsString('data-shape="corner"', $html);
        $this->assertSame(0, AnnouncementModel::query()->count());
    }

    public function test_the_centred_shape_is_the_default(): void
    {
        $html = $this->previewHtml($this->previewOf(['title' => 'Hola', 'content' => '<p>x</p>']));

        $this->assertStringContainsString('data-shape="centered"', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function test_the_content_is_sanitized_as_the_site_does(): void
    {
        $html = $this->previewHtml($this->previewOf([
            'title' => '<script>alert(1)</script>',
            'content' => '<p onclick="steal()">Texto</p><script>alert(2)</script><img src=x onerror="alert(3)">',
        ]));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('Texto', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_a_button_to_somewhere_unsafe_is_not_drawn(): void
    {
        $html = $this->previewHtml($this->previewOf([
            'title' => 'Hola',
            'content' => '<p>x</p>',
            'popup_action_label' => 'Pulsa',
            'popup_action_url' => 'javascript:alert(1)',
        ]));

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('Pulsa', $html);
    }

    public function test_the_preview_counts_nothing_and_remembers_nothing(): void
    {
        $this->previewHtml($this->previewOf(['title' => 'Hola', 'content' => '<p>x</p>']));

        $this->assertSame(0, PopupMetricModel::query()->count());
        $this->assertSame(0, PopupViewModel::query()->count());
    }

    public function test_the_preview_of_the_window_being_edited_uses_the_unsaved_values(): void
    {
        $model = $this->savedPopup();

        $page = Livewire::test(EditAnnouncement::class, ['record' => $model->getKey()])
            ->fillForm(['title' => 'Título sin guardar'])
            ->mountAction('popupPreview');

        $this->assertStringContainsString('Título sin guardar', $this->previewHtml($page));
        $this->assertSame('Hazte socio', $model->fresh()?->title);
        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    public function test_it_is_offered_only_for_a_window(): void
    {
        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['format' => 'banner'])
            ->assertActionHidden('popupPreview')
            ->fillForm(['format' => 'popup'])
            ->assertActionVisible('popupPreview');

        $model = $this->savedPopup();

        Livewire::test(EditAnnouncement::class, ['record' => $model->getKey()])
            ->assertActionVisible('popupPreview');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Testable<CreateAnnouncement>
     */
    private function previewOf(array $data): Testable
    {
        return Livewire::test(CreateAnnouncement::class)
            ->fillForm(['format' => 'popup', ...$data])
            ->mountAction('popupPreview');
    }

    /**
     * @param  Testable<CreateAnnouncement>|Testable<EditAnnouncement>  $page
     */
    private function previewHtml(Testable $page): string
    {
        $action = $page->instance()->getMountedAction();
        $this->assertInstanceOf(Action::class, $action);

        $content = $action->getModalContent();
        $this->assertInstanceOf(Htmlable::class, $content);

        return $content->toHtml();
    }

    private function savedPopup(): AnnouncementModel
    {
        $model = new AnnouncementModel;
        $model->forceFill([
            'title' => 'Hazte socio',
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
        ]);
        $model->save();

        return $model;
    }
}

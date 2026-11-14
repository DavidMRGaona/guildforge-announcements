<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Filament\Forms\Components\MediaLibraryPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The panel form of the popup format (announcements#67): the window fields show only for a
 * window, the position and «Permite cerrar» only for a banner, and every window value is checked
 * before it is saved.
 */
final class AnnouncementPopupFormTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    /** @var list<string> */
    private const array POPUP_FIELDS = [
        'popup_shape',
        'popup_image_public_id',
        'popup_image_alt',
        'popup_action_label',
        'popup_action_url',
        'popup_trigger_delay',
        'popup_trigger_scroll',
        'popup_trigger_exit_intent',
        'popup_trigger_page_views',
        'popup_pages',
        'popup_device',
        'popup_frequency',
    ];

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_a_new_announcement_is_a_banner_and_shows_no_window_fields(): void
    {
        $form = Livewire::test(CreateAnnouncement::class)
            ->assertFormSet(['format' => 'banner'])
            ->assertFormFieldIsVisible('position')
            ->assertFormFieldIsVisible('is_dismissible');

        foreach (self::POPUP_FIELDS as $field) {
            $form->assertFormFieldIsHidden($field);
        }
    }

    public function test_choosing_the_window_format_shows_its_fields_and_hides_the_banner_ones(): void
    {
        $form = Livewire::test(CreateAnnouncement::class)
            ->fillForm(['format' => 'popup'])
            ->assertFormFieldIsHidden('position')
            ->assertFormFieldIsHidden('is_dismissible')
            ->assertFormFieldIsHidden('background_color')
            // The paths only once the window is limited to them, and the days once it returns every few
            ->assertFormFieldIsHidden('popup_paths')
            ->assertFormFieldIsHidden('popup_frequency_days');

        foreach (self::POPUP_FIELDS as $field) {
            $form->assertFormFieldIsVisible($field);
        }

        $form->fillForm(['popup_pages' => 'paths'])->assertFormFieldIsVisible('popup_paths');
        $form->fillForm(['popup_frequency' => 'days'])->assertFormFieldIsVisible('popup_frequency_days');
    }

    public function test_a_window_is_created_with_all_its_settings(): void
    {
        $this->popupForm([
            'visibility' => 'guests',
            'popup_shape' => 'corner',
            'popup_image_alt' => 'Cartel de la campaña',
            'popup_action_label' => 'Apúntate',
            'popup_action_url' => '/socios',
            'popup_trigger_delay' => 5,
            'popup_trigger_scroll' => 50,
            'popup_trigger_exit_intent' => true,
            'popup_trigger_page_views' => 3,
            'popup_pages' => 'paths',
            'popup_paths' => ['/eventos*', '/articulos'],
            'popup_device' => 'desktop',
            'popup_frequency' => 'days',
            'popup_frequency_days' => 14,
        ])
            ->call('create')
            ->assertHasNoFormErrors();

        $model = AnnouncementModel::query()->sole();
        $this->assertSame(AnnouncementFormat::Popup, $model->format);
        $this->assertSame('guests', $model->visibility->value);
        $this->assertSame('corner', $model->popup_shape);
        $this->assertSame('Cartel de la campaña', $model->popup_image_alt);
        $this->assertSame('Apúntate', $model->popup_action_label);
        $this->assertSame('/socios', $model->popup_action_url);
        $this->assertSame([5, 50, true, 3], [$model->popup_trigger_delay, $model->popup_trigger_scroll, $model->popup_trigger_exit_intent, $model->popup_trigger_page_views]);
        $this->assertSame('paths', $model->popup_pages);
        $this->assertSame(['/eventos*', '/articulos'], $model->popup_paths);
        $this->assertSame('desktop', $model->popup_device);
        $this->assertSame('days', $model->popup_frequency);
        $this->assertSame(14, $model->popup_frequency_days);
    }

    public function test_a_new_window_opens_once_unless_told_otherwise(): void
    {
        $this->popupForm()->assertFormSet(['popup_frequency' => 'once'])->call('create')->assertHasNoFormErrors();

        $model = AnnouncementModel::query()->sole();
        $this->assertSame('once', $model->popup_frequency);
        $this->assertNull($model->popup_frequency_days);
    }

    public function test_a_window_that_returns_every_session_needs_no_days(): void
    {
        $this->popupForm(['popup_frequency' => 'session'])
            ->assertFormFieldIsHidden('popup_frequency_days')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('session', AnnouncementModel::query()->sole()->popup_frequency);
    }

    public function test_a_window_with_only_the_defaults_opens_on_load(): void
    {
        $this->popupForm()->call('create')->assertHasNoFormErrors();

        // Saved without any trigger, so that one set later is not beaten by a delay nobody chose
        $this->assertNull(AnnouncementModel::query()->sole()->popup_trigger_delay);
        $offered = $this->app->make(AnnouncementServiceInterface::class)->getActiveForUser(null, AnnouncementFormat::Popup);
        $this->assertSame(0, $offered[0]->popup['triggers']['delay_seconds'] ?? null);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidWindows(): array
    {
        return [
            'http link' => [['popup_action_label' => 'Ver', 'popup_action_url' => 'http://example.org'], 'popup_action_url'],
            'javascript link' => [['popup_action_label' => 'Ver', 'popup_action_url' => 'javascript:alert(1)'], 'popup_action_url'],
            'link without text' => [['popup_action_url' => '/eventos'], 'popup_action_label'],
            'text without link' => [['popup_action_label' => 'Ver'], 'popup_action_url'],
            'delay over a minute' => [['popup_trigger_delay' => 61], 'popup_trigger_delay'],
            'scroll under 10 %' => [['popup_trigger_scroll' => 5], 'popup_trigger_scroll'],
            'one page view' => [['popup_trigger_page_views' => 1], 'popup_trigger_page_views'],
            'paths without any' => [['popup_pages' => 'paths', 'popup_paths' => []], 'popup_paths'],
            'a path with a host' => [['popup_pages' => 'paths', 'popup_paths' => ['https://example.org/eventos']], 'popup_paths.0'],
            'a star in the middle' => [['popup_pages' => 'paths', 'popup_paths' => ['/eventos/*/fotos']], 'popup_paths.0'],
            'unknown shape' => [['popup_shape' => 'fullscreen'], 'popup_shape'],
            'unknown frequency' => [['popup_frequency' => 'always'], 'popup_frequency'],
            'every few days without the days' => [['popup_frequency' => 'days', 'popup_frequency_days' => null], 'popup_frequency_days'],
            'zero days' => [['popup_frequency' => 'days', 'popup_frequency_days' => 0], 'popup_frequency_days'],
            'more than a year' => [['popup_frequency' => 'days', 'popup_frequency_days' => 366], 'popup_frequency_days'],
            'days that are not a number' => [['popup_frequency' => 'days', 'popup_frequency_days' => 'una semana'], 'popup_frequency_days'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[DataProvider('invalidWindows')]
    public function test_it_refuses_a_window_value_it_cannot_use(array $data, string $field): void
    {
        $this->popupForm($data)
            ->call('create')
            ->assertHasFormErrors([$field]);

        $this->assertSame(0, AnnouncementModel::query()->count());
    }

    public function test_the_window_fields_are_not_checked_for_a_banner(): void
    {
        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Banda',
                'content' => '<p>Texto</p>',
                'format' => 'popup',
                'popup_trigger_delay' => 500,
            ])
            ->fillForm(['format' => 'banner'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(AnnouncementFormat::Banner, AnnouncementModel::query()->sole()->format);
    }

    public function test_the_window_image_goes_to_the_module_folder_and_can_come_from_the_library(): void
    {
        $instance = Livewire::test(CreateAnnouncement::class)->instance();
        $this->assertInstanceOf(HasSchemas::class, $instance);
        $upload = $instance->getSchema('form')?->getFlatFields(withHidden: true)['popup_image_public_id'] ?? null;

        $this->assertInstanceOf(FileUpload::class, $upload);
        $this->assertSame('images', $upload->getDiskName());
        $this->assertSame('announcements/popups/'.now()->format('Y/m'), $upload->getDirectory());
        $this->assertContains(
            MediaLibraryPicker::ACTION,
            array_map(static fn ($action): ?string => $action->getName(), $upload->getHintActions()),
        );
    }

    public function test_the_edit_page_loads_a_window(): void
    {
        $model = AnnouncementModel::create([
            'title' => 'Hazte socio',
            'content' => '<p>Únete</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            'popup_pages' => 'paths',
            'popup_paths' => ['/eventos*'],
            'popup_frequency' => 'days',
            'popup_frequency_days' => 30,
        ]);

        Livewire::test(EditAnnouncement::class, ['record' => $model->getRouteKey()])
            ->assertOk()
            ->assertFormSet(['format' => 'popup', 'popup_pages' => 'paths', 'popup_paths' => ['/eventos*'], 'popup_frequency' => 'days', 'popup_frequency_days' => 30])
            ->assertFormFieldIsVisible('popup_paths')
            ->assertFormFieldIsVisible('popup_frequency_days');
    }

    public function test_the_list_can_be_filtered_by_format(): void
    {
        $banner = AnnouncementModel::create(['title' => 'Banda', 'content' => '<p>x</p>', 'visibility' => 'public', 'priority' => 5]);
        $popup = AnnouncementModel::create(['title' => 'Ventana', 'content' => '<p>x</p>', 'visibility' => 'public', 'priority' => 5, 'format' => 'popup']);

        Livewire::test(ListAnnouncements::class)
            ->filterTable('format', 'popup')
            ->assertCanSeeTableRecords([$popup])
            ->assertCanNotSeeTableRecords([$banner]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Testable<CreateAnnouncement>
     */
    private function popupForm(array $data = []): Testable
    {
        return Livewire::test(CreateAnnouncement::class)->fillForm([
            'title' => 'Hazte socio',
            'content' => '<p>Únete a la asociación.</p>',
            'format' => 'popup',
            'visibility' => 'public',
            'priority' => 5,
            ...$data,
        ]);
    }
}

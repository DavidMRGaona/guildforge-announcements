<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Application\SiteBlocks\Services\SiteBlockRegistryInterface;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\SiteBlocks\FakeSiteBlock;

/**
 * The «Bloque» of a window (announcements#70): chosen among the blocks the core offers for
 * popups, with the settings of the chosen one below.
 */
final class AnnouncementPopupBlockFormTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    private const string OWNER = 'example-module';

    private const string KEY = 'example-module.subscribe';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_the_block_fields_show_only_for_a_window(): void
    {
        $this->register(true);

        Livewire::test(CreateAnnouncement::class)
            ->assertFormFieldIsHidden('popup_block.key')
            ->assertFormFieldIsHidden('popup_block_hide_when_unavailable')
            ->fillForm(['format' => 'popup'])
            ->assertFormFieldIsVisible('popup_block.key');
    }

    public function test_it_offers_the_blocks_of_the_popup_placement(): void
    {
        $this->register(true);

        $options = $this->blockOptions(Livewire::test(CreateAnnouncement::class)->fillForm(['format' => 'popup']));

        $this->assertSame([self::KEY => 'Bloque de ejemplo'], $options);
    }

    public function test_it_does_not_offer_a_block_that_is_not_available_now(): void
    {
        $this->register(false);

        $this->assertSame([], $this->blockOptions(Livewire::test(CreateAnnouncement::class)->fillForm(['format' => 'popup'])));
    }

    public function test_choosing_a_block_shows_its_settings_and_the_hide_checkbox(): void
    {
        $this->register(true);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['format' => 'popup'])
            ->assertFormFieldIsHidden('popup_block_hide_when_unavailable')
            ->fillForm(['popup_block' => ['key' => self::KEY]])
            ->assertFormFieldIsVisible('popup_block_hide_when_unavailable')
            ->assertFormSet(['popup_block_hide_when_unavailable' => true]);
    }

    public function test_a_window_is_saved_with_its_block_and_settings(): void
    {
        $this->register(true);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Hazte socio',
                'content' => '<p>Texto</p>',
                'format' => 'popup',
                'popup_block' => ['key' => self::KEY, 'settings' => ['heading' => 'Suscríbete']],
                'popup_block_hide_when_unavailable' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $model = AnnouncementModel::query()->sole();
        $this->assertSame(['key' => self::KEY, 'settings' => ['heading' => 'Suscríbete']], $model->popup_block);
        $this->assertFalse($model->popup_block_hide_when_unavailable);
    }

    public function test_a_window_without_a_block_stores_none_and_hides_without_one_by_default(): void
    {
        $this->register(true);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Hazte socio', 'content' => '<p>Texto</p>', 'format' => 'popup'])
            ->call('create')
            ->assertHasNoFormErrors();

        $model = AnnouncementModel::query()->sole();
        $this->assertNull($model->popup_block);
        $this->assertTrue($model->popup_block_hide_when_unavailable);
    }

    public function test_it_refuses_a_block_that_cannot_be_added(): void
    {
        $this->register(false);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Hazte socio',
                'content' => '<p>Texto</p>',
                'format' => 'popup',
                'popup_block' => ['key' => self::KEY],
            ])
            ->call('create')
            ->assertHasFormErrors(['popup_block.key']);

        $this->assertSame(0, AnnouncementModel::query()->count());
    }

    public function test_a_stored_block_that_is_not_available_now_is_kept_when_saving(): void
    {
        $this->register(false);
        $window = $this->window(['key' => self::KEY, 'settings' => ['heading' => 'Suscríbete']]);

        $page = Livewire::test(EditAnnouncement::class, ['record' => $window->getKey()]);
        $this->assertSame([self::KEY => 'Bloque de ejemplo (no disponible)'], $this->blockOptions($page));

        $page->fillForm(['title' => 'Otro título'])->call('save')->assertHasNoFormErrors();

        $window->refresh();
        $this->assertSame('Otro título', $window->title);
        $this->assertSame(['key' => self::KEY, 'settings' => ['heading' => 'Suscríbete']], $window->popup_block);
    }

    public function test_a_stored_block_nobody_registers_any_more_keeps_its_settings(): void
    {
        $window = $this->window(['key' => 'gone-module.subscribe', 'settings' => ['heading' => 'Suscríbete', 'list' => 3]]);

        $page = Livewire::test(EditAnnouncement::class, ['record' => $window->getKey()]);
        $this->assertSame(['gone-module.subscribe' => 'gone-module.subscribe (no disponible)'], $this->blockOptions($page));

        $page->fillForm(['title' => 'Otro título'])->call('save')->assertHasNoFormErrors();

        $this->assertSame(
            ['key' => 'gone-module.subscribe', 'settings' => ['heading' => 'Suscríbete', 'list' => 3]],
            $window->refresh()->popup_block,
        );
    }

    public function test_the_settings_of_the_chosen_block_are_checked_by_the_block(): void
    {
        app(SiteBlockRegistryInterface::class)->register((new class extends FakeSiteBlock
        {
            public function key(): string
            {
                return 'example-module.subscribe';
            }

            public function settingsSchema(): array
            {
                return [TextInput::make('heading')->required()];
            }
        })::class, self::OWNER);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Hazte socio',
                'content' => '<p>Texto</p>',
                'format' => 'popup',
                'popup_block' => ['key' => self::KEY, 'settings' => ['heading' => '']],
            ])
            ->call('create')
            ->assertHasFormErrors(['popup_block.settings.heading']);
    }

    public function test_choosing_a_block_fills_its_defaults(): void
    {
        $this->registerClass(new class extends FakeSiteBlock
        {
            public function key(): string
            {
                return 'example-module.subscribe';
            }

            public function settingsSchema(): array
            {
                return [Toggle::make('x')->default(true)];
            }
        });

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['format' => 'popup'])
            ->fillForm(['popup_block' => ['key' => self::KEY]])
            ->assertFormSet(['popup_block.settings.x' => true]);
    }

    public function test_changing_the_block_leaves_the_settings_of_the_old_one_behind(): void
    {
        $this->registerClass(new class extends FakeSiteBlock
        {
            public function key(): string
            {
                return 'example-module.a';
            }

            public function settingsSchema(): array
            {
                return [TextInput::make('heading')];
            }
        });
        $this->registerClass(new class extends FakeSiteBlock
        {
            public function key(): string
            {
                return 'example-module.b';
            }

            public function settingsSchema(): array
            {
                return [Toggle::make('x')->default(true)];
            }
        });

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Hazte socio', 'content' => '<p>Texto</p>', 'format' => 'popup'])
            ->fillForm(['popup_block' => ['key' => 'example-module.a']])
            ->fillForm(['popup_block.settings.heading' => 'Antiguo'])
            ->fillForm(['popup_block' => ['key' => 'example-module.b']])
            ->assertFormSet(['popup_block.settings.x' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['key' => 'example-module.b', 'settings' => ['x' => true]], AnnouncementModel::query()->sole()->popup_block);
    }

    private function registerClass(FakeSiteBlock $block): void
    {
        app(SiteBlockRegistryInterface::class)->register($block::class, self::OWNER);
    }

    private function register(bool $available): void
    {
        $block = $available
            ? new class extends FakeSiteBlock
            {
                public function key(): string
                {
                    return 'example-module.subscribe';
                }

                public function settingsSchema(): array
                {
                    return [TextInput::make('heading')];
                }

                public function props(array $settings, ?Authenticatable $user): array
                {
                    return $settings;
                }
            }
        : new class extends FakeSiteBlock
        {
            public function key(): string
            {
                return 'example-module.subscribe';
            }

            public function settingsSchema(): array
            {
                return [TextInput::make('heading')];
            }

            public function isAvailable(): bool
            {
                return false;
            }
        };

        app(SiteBlockRegistryInterface::class)->register($block::class, self::OWNER);
    }

    /**
     * @param  array{key: string, settings: array<string, mixed>}  $block
     */
    private function window(array $block): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Hazte socio',
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            'popup_block' => $block,
        ]);
    }

    /**
     * @param  Testable<CreateAnnouncement>|Testable<EditAnnouncement>  $page
     * @return array<string, string>
     */
    private function blockOptions(Testable $page): array
    {
        $select = $page->instance()->form->getFlatComponents()['popup_block.key'] ?? null;
        $this->assertInstanceOf(Select::class, $select);

        return array_map(static fn (mixed $label): string => is_string($label) ? $label : '', $select->getOptions());
    }
}

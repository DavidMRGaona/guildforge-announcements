<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Providers;

use App\Application\SiteBlocks\Services\SiteBlockRegistryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Auth\Authenticatable;
use Inertia\Testing\AssertableInertia;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use RuntimeException;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\SiteBlocks\FakeSiteBlock;

/**
 * A window can carry a block of another module or of the core (announcements#70): the server
 * resolves it for the person and shares only what it resolved, never the stored settings.
 */
final class PopupBlockSharingTest extends ModuleTestCase
{
    private const string OWNER = 'example-module';

    private const string KEY = 'example-module.subscribe';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    public function test_an_available_block_is_shared_resolved_inside_its_window(): void
    {
        $this->register($this->block());
        $this->popup(['heading' => 'Suscríbete', 'secret' => 'no']);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.popup.block.key', self::KEY)
                ->where('announcementPopups.0.popup.block.module', self::OWNER)
                ->where('announcementPopups.0.popup.block.component', 'components/ExampleBlock.vue')
                ->where('announcementPopups.0.popup.block.props.settings', ['heading' => 'Suscríbete'])
                ->where('announcementPopups.0.popup.block.props.signedIn', false));
    }

    public function test_the_block_is_resolved_for_the_person_viewing_the_page(): void
    {
        $this->register($this->block());
        $this->popup(['heading' => 'Suscríbete']);

        $this->actingAs(UserModel::factory()->create())
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('announcementPopups.0.popup.block.props.signedIn', true));
    }

    public function test_the_stored_settings_and_the_flag_never_reach_the_browser(): void
    {
        $this->register($this->block());
        $this->popup(['heading' => 'Suscríbete']);

        $content = (string) $this->get('/')->getContent();

        $this->assertStringNotContainsString('popup_block', $content);
        $this->assertStringNotContainsString('hide_when_block_unavailable', $content);
    }

    public function test_a_window_without_a_block_shares_none(): void
    {
        $this->register($this->block());
        $this->popup(null);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.popup.block', null));
    }

    public function test_a_window_whose_block_is_not_available_is_not_offered_by_default(): void
    {
        $this->register($this->block(available: false));
        $this->popup(['heading' => 'Suscríbete']);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('announcementPopups', 0));
    }

    public function test_a_window_whose_block_is_not_available_is_offered_without_it_when_told_so(): void
    {
        $this->register($this->block(available: false));
        $this->popup(['heading' => 'Suscríbete'], hide: false);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.popup.block', null));
    }

    public function test_a_block_that_throws_counts_as_not_available(): void
    {
        $this->register($this->throwingBlock());
        $this->popup([]);
        $this->popup([], hide: false, title: 'Sin bloque');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.title', 'Sin bloque')
                ->where('announcementPopups.0.popup.block', null));
    }

    public function test_a_block_nobody_registers_any_more_counts_as_not_available(): void
    {
        $this->popup(['heading' => 'Suscríbete']);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('announcementPopups', 0));
    }

    public function test_one_window_hidden_by_its_block_leaves_the_others(): void
    {
        $this->register($this->block(available: false));
        $this->popup([], title: 'Con boletín');
        $this->popup(null, title: 'Sin bloque');

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.title', 'Sin bloque'));
    }

    private function register(FakeSiteBlock $block): void
    {
        app(SiteBlockRegistryInterface::class)->register($block::class, self::OWNER);
    }

    private function block(bool $available = true): FakeSiteBlock
    {
        return $available
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
                    return ['settings' => $settings, 'signedIn' => $user instanceof Authenticatable];
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
    }

    private function throwingBlock(): FakeSiteBlock
    {
        return new class extends FakeSiteBlock
        {
            public function key(): string
            {
                return 'example-module.subscribe';
            }

            public function props(array $settings, ?Authenticatable $user): array
            {
                throw new RuntimeException('Proveedor caído');
            }
        };
    }

    /**
     * @param  array<string, scalar|null>|null  $settings  null for a window without a block
     */
    private function popup(?array $settings, bool $hide = true, string $title = 'Ventana'): void
    {
        AnnouncementModel::create([
            'title' => $title,
            'content' => '<p>Ventana.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            'popup_block' => $settings === null ? null : ['key' => self::KEY, 'settings' => $settings],
            'popup_block_hide_when_unavailable' => $hide,
        ]);
    }
}

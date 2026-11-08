<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Filament\Support\PanelNavigationGroup;
use Modules\Announcements\AnnouncementsServiceProvider;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The panel menu is grouped by task (core#409): announcements sit in the core's «Comunicación»
 * group, in their own slot, and the module declares no group of its own.
 */
final class PanelNavigationTest extends ModuleTestCase
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

    public function test_announcements_sit_in_the_communication_group_in_their_own_slot(): void
    {
        $this->assertSame(PanelNavigationGroup::Communication->getLabel(), AnnouncementResource::getNavigationGroup());
        $this->assertSame(10, AnnouncementResource::getNavigationSort());
        $this->assertSame('Anuncios', AnnouncementResource::getNavigationLabel());
        $this->assertNull(AnnouncementResource::getNavigationParentItem());
    }

    public function test_the_navigation_entry_uses_the_core_group_and_slot(): void
    {
        $provider = new AnnouncementsServiceProvider($this->app);
        $entry = $provider->registerNavigation()[0];

        $this->assertSame(PanelNavigationGroup::Communication->getLabel(), $entry->group);
        $this->assertSame(10, $entry->sort);
    }

    public function test_the_panel_menu_shows_announcements_under_the_communication_group(): void
    {
        $this->get(AnnouncementResource::getUrl())
            ->assertOk()
            ->assertSeeInOrder([PanelNavigationGroup::Communication->getLabel(), 'Anuncios']);
    }
}

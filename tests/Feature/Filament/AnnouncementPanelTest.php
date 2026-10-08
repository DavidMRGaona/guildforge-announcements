<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Filament\Pages\ModuleSettingsPage;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementPanelTest extends ModuleTestCase
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

    public function test_list_page_shows_announcements(): void
    {
        $announcement = $this->createAnnouncement();

        Livewire::test(ListAnnouncements::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$announcement]);
    }

    public function test_create_page_renders(): void
    {
        Livewire::test(CreateAnnouncement::class)->assertOk();
    }

    public function test_edit_page_loads_the_record(): void
    {
        $announcement = $this->createAnnouncement();

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->assertOk()
            ->assertFormSet(['title' => 'Cierre por vacaciones']);
    }

    public function test_settings_page_renders(): void
    {
        Livewire::test(ModuleSettingsPage::class, ['module' => 'announcements'])->assertOk();
    }

    private function createAnnouncement(): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p>El local cierra en agosto.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);
    }
}

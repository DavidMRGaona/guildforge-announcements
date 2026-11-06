<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Infrastructure\Knowledge;

use App\Application\Knowledge\Services\KnowledgeQueryServiceInterface;
use App\Domain\Knowledge\Enums\GuideAudience;
use App\Domain\Knowledge\ValueObjects\GuideId;
use App\Filament\Pages\KnowledgeBasePage;
use App\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Who sees each of the module's guides, and the help button on its panel pages.
 */
final class AnnouncementGuidesTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    private const array SITE_GUIDES = [
        'announcements.read-announcements',
    ];

    private const array PANEL_GUIDES = [
        'announcements.panel.banner-settings',
        'announcements.panel.create-announcement',
        'announcements.panel.manage-announcements',
        'announcements.panel.overview',
    ];

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->artisanCommand('knowledge:sync')->assertSuccessful()->run();
    }

    public function test_an_admin_sees_every_panel_guide_rendered_without_raw_markup(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->assertSame(self::PANEL_GUIDES, $this->visibleGuides($admin, GuideAudience::Panel));
        foreach (self::PANEL_GUIDES as $id) {
            $guide = $this->queries()->guide($admin, GuideAudience::Panel, GuideId::fromString($id));
            $this->assertNotNull($guide, $id);
            $this->assertStringNotContainsString('[[', $guide->html, $id);
            $this->assertStringNotContainsString('{{', $guide->html, $id);
            $this->assertStringNotContainsString('[!', $guide->html, $id);
        }
    }

    public function test_each_permission_shows_only_its_panel_guides(): void
    {
        $this->assertSame(
            ['announcements.panel.overview'],
            $this->visibleGuides($this->userWith('announcements:announcements.view_any'), GuideAudience::Panel),
        );
        $this->assertSame(
            ['announcements.panel.create-announcement'],
            $this->visibleGuides($this->userWith('announcements:announcements.create'), GuideAudience::Panel),
        );
        $this->assertSame(
            ['announcements.panel.manage-announcements'],
            $this->visibleGuides($this->userWith('announcements:announcements.update'), GuideAudience::Panel),
        );
        $this->assertSame(
            ['announcements.panel.manage-announcements'],
            $this->visibleGuides($this->userWith('announcements:announcements.delete'), GuideAudience::Panel),
        );
        $this->assertSame(
            ['announcements.panel.banner-settings'],
            $this->visibleGuides($this->userWith('modules.configure'), GuideAudience::Panel),
        );
        // Module permissions are never granted on their own: an editor without them sees none
        $this->assertSame([], $this->visibleGuides(UserModel::factory()->editor()->create(), GuideAudience::Panel));
    }

    public function test_every_account_sees_the_site_guide_rendered_without_raw_markup(): void
    {
        $user = UserModel::factory()->create();

        $this->assertSame(self::SITE_GUIDES, $this->visibleGuides($user, GuideAudience::Site));
        foreach (self::SITE_GUIDES as $id) {
            $guide = $this->queries()->guide($user, GuideAudience::Site, GuideId::fromString($id));
            $this->assertNotNull($guide, $id);
            $this->assertStringNotContainsString('[[', $guide->html, $id);
            $this->assertStringNotContainsString('{{', $guide->html, $id);
            $this->assertStringNotContainsString('[!', $guide->html, $id);
        }
    }

    public function test_the_panel_pages_open_their_guide(): void
    {
        $this->actingAsPanelAdmin();
        $announcement = AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p>El local cierra en agosto.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        $pages = [
            [ListAnnouncements::class, [], 'announcements.panel.overview'],
            [CreateAnnouncement::class, [], 'announcements.panel.create-announcement'],
            [EditAnnouncement::class, ['record' => $announcement->getRouteKey()], 'announcements.panel.manage-announcements'],
        ];

        foreach ($pages as [$page, $parameters, $guide]) {
            Livewire::test($page, $parameters)
                ->assertActionVisible('contextualHelp')
                ->assertActionHasUrl('contextualHelp', KnowledgeBasePage::getUrl(['guia' => $guide]));
        }
    }

    /**
     * @return list<string>
     */
    private function visibleGuides(UserModel $user, GuideAudience $audience): array
    {
        $ids = array_values(array_filter(
            $this->queries()->visibleIds($user, $audience),
            static fn (string $id): bool => str_starts_with($id, 'announcements.'),
        ));
        sort($ids);

        return $ids;
    }

    private function userWith(string $permissionKey): UserModel
    {
        $role = RoleModel::query()->create(['name' => 'tester_'.uniqid(), 'display_name' => 'Permissions tester']);
        $permission = PermissionModel::query()->firstOrCreate(
            ['key' => $permissionKey],
            ['label' => $permissionKey, 'resource' => 'announcements', 'action' => 'test', 'module' => 'announcements'],
        );
        $role->permissions()->attach($permission->id);
        $user = UserModel::factory()->editor()->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    private function queries(): KnowledgeQueryServiceInterface
    {
        return $this->app->make(KnowledgeQueryServiceInterface::class);
    }
}

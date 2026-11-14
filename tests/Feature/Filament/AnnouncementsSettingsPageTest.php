<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Application\Modules\Services\ModuleManagerServiceInterface;
use App\Domain\Modules\ValueObjects\ModuleName;
use App\Filament\Clusters\SettingsCluster;
use App\Filament\Support\ModuleSettingsPages;
use App\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Announcements\AnnouncementsServiceProvider;
use Modules\Announcements\Filament\Pages\AnnouncementsSettings;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementsSettingsPageTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel(pages: [AnnouncementsSettings::class]);
    }

    public function test_the_provider_declares_the_page_and_registers_it(): void
    {
        $provider = new AnnouncementsServiceProvider($this->app);

        $this->assertSame(AnnouncementsSettings::class, $provider->getSettingsPage());
        $this->assertContains(AnnouncementsSettings::class, $provider->registerFilamentPages());
        $this->assertSame([], $provider->getSettingsSchema());
    }

    public function test_the_page_sits_in_the_settings_cluster_with_its_slot(): void
    {
        $this->assertSame(SettingsCluster::class, AnnouncementsSettings::getCluster());
        $this->assertSame(105, AnnouncementsSettings::getNavigationSort());
        $this->assertSame('Anuncios', AnnouncementsSettings::getNavigationLabel());
    }

    public function test_the_permission_is_registered_and_not_given_to_any_role(): void
    {
        $permission = collect((new AnnouncementsServiceProvider($this->app))->registerPermissions())
            ->first(fn ($dto): bool => $dto->name === 'settings.manage');

        $this->assertNotNull($permission);
        $this->assertSame([], $permission->roles);
        $this->assertFalse($permission->adminOnly);
    }

    public function test_an_administrator_can_open_the_page(): void
    {
        $this->actingAsPanelAdmin();

        $this->assertTrue(AnnouncementsSettings::canAccess());
        Livewire::test(AnnouncementsSettings::class)
            ->assertOk()
            ->assertFormFieldExists('show_banner')
            ->assertFormFieldExists('auto_rotate')
            ->assertFormFieldExists('rotate_interval');
    }

    public function test_the_module_permission_opens_the_page(): void
    {
        $this->actingAs($this->userWith(AnnouncementsSettings::PERMISSION));

        $this->assertTrue(AnnouncementsSettings::canAccess());
        Livewire::test(AnnouncementsSettings::class)->assertOk();
    }

    public function test_modules_configure_alone_is_not_enough(): void
    {
        $this->actingAs($this->userWith('modules.configure'));

        $this->assertFalse(AnnouncementsSettings::canAccess());
    }

    public function test_the_module_page_shows_the_button_only_to_those_who_may_enter(): void
    {
        $resolver = $this->app->make(ModuleSettingsPages::class);

        // The core accepts the declared page: it is usable and has a URL
        $this->assertNotNull($resolver->pageUrl('announcements'));

        $this->actingAs($this->userWith('modules.configure'));
        $this->assertNull($resolver->accessibleUrl('announcements'));

        $this->actingAs($this->userWith(AnnouncementsSettings::PERMISSION));
        $this->assertNotNull($resolver->accessibleUrl('announcements'));
    }

    public function test_saving_without_the_permission_is_refused(): void
    {
        $this->actingAsPanelAdmin();
        $component = Livewire::test(AnnouncementsSettings::class);

        $this->actingAs($this->userWith('modules.configure'));

        $component->call('save')->assertForbidden();

        $this->assertNull(SettingModel::query()->where('key', 'module_settings:announcements')->first());
    }

    public function test_values_saved_before_the_page_existed_are_loaded(): void
    {
        $this->actingAsPanelAdmin();
        $this->storeSettings(['show_banner' => false, 'auto_rotate' => true, 'rotate_interval' => 8000]);

        Livewire::test(AnnouncementsSettings::class)
            ->assertFormSet([
                'show_banner' => false,
                'auto_rotate' => true,
                'rotate_interval' => 8000,
            ]);
    }

    public function test_saving_writes_the_same_row_the_module_reads(): void
    {
        $this->actingAsPanelAdmin();

        Livewire::test(AnnouncementsSettings::class)
            ->fillForm(['show_banner' => false, 'auto_rotate' => true, 'rotate_interval' => 12000])
            ->call('save')
            ->assertHasNoFormErrors();

        $stored = $this->app->make(ModuleManagerServiceInterface::class)->getSettings(new ModuleName('announcements'));
        $this->assertFalse($stored['show_banner']);
        $this->assertTrue($stored['auto_rotate']);
        $this->assertEquals(12000, $stored['rotate_interval']);

        $row = DB::table('settings')->where('key', 'module_settings:announcements')->first();
        $this->assertNotNull($row);
    }

    public function test_the_interval_is_kept_within_its_limits(): void
    {
        $this->actingAsPanelAdmin();

        Livewire::test(AnnouncementsSettings::class)
            ->fillForm(['auto_rotate' => true, 'rotate_interval' => 500])
            ->call('save')
            ->assertHasFormErrors(['rotate_interval']);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function storeSettings(array $values): void
    {
        $this->app->make(ModuleManagerServiceInterface::class)->updateSettings(new ModuleName('announcements'), $values);
    }

    private function userWith(string $permissionKey): UserModel
    {
        $role = RoleModel::query()->create(['name' => 'tester_'.uniqid(), 'display_name' => 'Permissions tester']);
        $permission = PermissionModel::query()->firstOrCreate(
            ['key' => $permissionKey],
            ['label' => $permissionKey, 'resource' => 'announcements', 'action' => 'test', 'module' => 'announcements'],
        );
        $role->permissions()->attach($permission->id);
        $user = UserModel::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Providers;

use App\Application\Modules\DTOs\SlotRegistrationDTO;
use App\Filament\Pages\ModuleSettingsPage;
use Livewire\Livewire;
use Modules\Announcements\AnnouncementsServiceProvider;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementSlotsTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    public function test_a_banner_is_registered_for_every_position(): void
    {
        $slots = $this->slots();

        $this->assertSame(
            array_map(fn (AnnouncementPosition $position): string => $position->value, AnnouncementPosition::cases()),
            array_map(fn (SlotRegistrationDTO $slot): string => $slot->slot, $slots),
        );
    }

    public function test_banner_slots_receive_the_saved_display_settings(): void
    {
        config()->set('modules.settings.announcements', [
            'show_banner' => false,
            'banner_position' => 'bottom',
            'auto_rotate' => true,
            'rotate_interval' => 8000,
        ]);

        foreach ($this->slots() as $slot) {
            $this->assertSame(
                ['show_banner' => false, 'auto_rotate' => true, 'rotate_interval' => 8000],
                $slot->props['settings'],
            );
        }
    }

    public function test_the_rotate_interval_is_passed_as_a_number(): void
    {
        config()->set('modules.settings.announcements', [
            'auto_rotate' => true,
            'rotate_interval' => '3000',
        ]);

        foreach ($this->slots() as $slot) {
            $this->assertSame(['auto_rotate' => true, 'rotate_interval' => 3000], $slot->props['settings']);
        }
    }

    public function test_banner_slots_receive_no_settings_when_none_are_saved(): void
    {
        config()->set('modules.settings.announcements');

        foreach ($this->slots() as $slot) {
            $this->assertArrayNotHasKey('settings', $slot->props);
        }
    }

    public function test_the_settings_form_has_no_banner_position(): void
    {
        $this->actingAsPanelAdmin();

        Livewire::test(ModuleSettingsPage::class, ['module' => 'announcements'])
            ->assertOk()
            ->assertFormFieldExists('show_banner')
            ->assertFormFieldDoesNotExist('banner_position');
    }

    /**
     * @return array<SlotRegistrationDTO>
     */
    private function slots(): array
    {
        return (new AnnouncementsServiceProvider($this->app))->registerSlots();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Widgets\PopupMetricsWidget;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * The summary on the announcement page (announcements#69): the last 30 display days of a window,
 * only for windows and only for whoever may edit the announcement.
 */
final class PopupMetricsWidgetTest extends ModuleTestCase
{
    use InteractsWithModulePanel;
    use UsesUtcStorage;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useUtcStorage();
        Carbon::setTestNow('2026-11-15 10:00:00');
        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_shows_shown_closed_clicks_and_the_click_rate(): void
    {
        $popup = $this->announcement('popup');
        $this->counter($popup, 'shown', 40);
        $this->counter($popup, 'dismissed', 25);
        $this->counter($popup, 'action_clicked', 10);

        Livewire::test(PopupMetricsWidget::class, ['record' => $popup])
            ->assertSee('40')
            ->assertSee('25')
            ->assertSee('10')
            ->assertSee('25 %')
            ->assertSee(__('announcements::announcements.metrics.shown'))
            ->assertSee(__('announcements::announcements.metrics.dismissed'))
            ->assertSee(__('announcements::announcements.metrics.action_clicked'))
            ->assertSee(__('announcements::announcements.metrics.click_rate'));
    }

    public function test_the_rate_is_a_dash_until_it_was_shown(): void
    {
        Livewire::test(PopupMetricsWidget::class, ['record' => $this->announcement('popup')])
            ->assertSee('—');
    }

    public function test_it_is_on_the_edit_page_of_a_window_only(): void
    {
        $this->assertTrue(PopupMetricsWidget::canView());

        $popup = $this->announcement('popup');
        $banner = $this->announcement('banner');

        Livewire::test(EditAnnouncement::class, ['record' => $popup->getKey()])
            ->assertSee(__('announcements::announcements.metrics.click_rate'));
        Livewire::test(EditAnnouncement::class, ['record' => $banner->getKey()])
            ->assertDontSee(__('announcements::announcements.metrics.click_rate'));
    }

    public function test_it_draws_nothing_for_a_banner_or_without_the_permission(): void
    {
        $banner = $this->announcement('banner');
        $popup = $this->announcement('popup');
        $this->counter($popup, 'shown', 7);

        Livewire::test(PopupMetricsWidget::class, ['record' => $banner])
            ->assertDontSee(__('announcements::announcements.metrics.shown'));

        // Someone who cannot edit announcements never reads the numbers
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(PopupMetricsWidget::class, ['record' => $popup])
            ->assertDontSee(__('announcements::announcements.metrics.shown'));
    }

    private function counter(AnnouncementModel $popup, string $metric, int $count): void
    {
        DB::table('announcements_popup_metrics')->insert([
            'id' => (string) Str::uuid(),
            'announcement_id' => $popup->id,
            'day' => '2026-11-15',
            'metric' => $metric,
            'count' => $count,
        ]);
    }

    private function announcement(string $format): AnnouncementModel
    {
        $model = new AnnouncementModel;
        $model->forceFill([
            'title' => 'Hazte socio',
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => $format,
        ]);
        $model->save();

        return $model;
    }
}

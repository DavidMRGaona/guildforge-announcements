<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Providers;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use Modules\Announcements\Application\Services\PopupMetricServiceInterface;
use Modules\Announcements\Console\Commands\PrunePopupMetricsCommand;
use Modules\Announcements\Domain\Enums\PopupMetric;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupMetricModel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * What the panel shows of a window (announcements#69): the last 30 display days, and the pruning
 * of old counters.
 */
final class PopupMetricsSummaryTest extends ModuleTestCase
{
    use UsesUtcStorage;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useUtcStorage();
        config()->set('app.display_timezone', 'Europe/Madrid');
        Carbon::setTestNow('2026-11-15 10:00:00');
        // A module enabled during the test boots after the console was built
        $this->app->make(Kernel::class)->registerCommand($this->app->make(PrunePopupMetricsCommand::class));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_adds_up_the_last_30_display_days(): void
    {
        $popup = $this->popup();
        $this->row($popup, '2026-11-15', PopupMetric::Shown, 10);
        $this->row($popup, '2026-11-15', PopupMetric::Dismissed, 6);
        $this->row($popup, '2026-11-15', PopupMetric::ActionClicked, 2);
        // The 30th day back is still inside (today counts as the first), the 31st is not
        $this->row($popup, '2026-10-17', PopupMetric::Shown, 5);
        $this->row($popup, '2026-10-16', PopupMetric::Shown, 100);

        $summary = $this->service()->summary($popup->id);

        $this->assertSame(15, $summary->shown);
        $this->assertSame(6, $summary->dismissed);
        $this->assertSame(2, $summary->actionClicked);
        $this->assertSame(13.3, $summary->clickRate);
    }

    public function test_the_window_of_days_follows_the_display_zone(): void
    {
        $popup = $this->popup();
        // 23:30 UTC on the 15th is the 16th in Madrid: the 30 days end on the 16th
        Carbon::setTestNow('2026-11-15 23:30:00');
        $this->row($popup, '2026-10-18', PopupMetric::Shown, 4);
        $this->row($popup, '2026-10-17', PopupMetric::Shown, 9);

        $this->assertSame(4, $this->service()->summary($popup->id)->shown);
    }

    public function test_the_click_rate_is_unknown_until_it_was_shown(): void
    {
        $popup = $this->popup();

        $summary = $this->service()->summary($popup->id);

        $this->assertSame(0, $summary->shown);
        $this->assertNull($summary->clickRate);
    }

    public function test_the_click_rate_never_goes_above_100(): void
    {
        $popup = $this->popup();
        $this->row($popup, '2026-11-15', PopupMetric::Shown, 3);
        $this->row($popup, '2026-11-15', PopupMetric::ActionClicked, 9);

        $this->assertSame(100.0, $this->service()->summary($popup->id)->clickRate);
    }

    public function test_it_only_counts_its_own_window(): void
    {
        $popup = $this->popup();
        $other = $this->popup();
        $this->row($other, '2026-11-15', PopupMetric::Shown, 50);

        $this->assertSame(0, $this->service()->summary($popup->id)->shown);
    }

    public function test_pruning_drops_the_counters_older_than_the_retention(): void
    {
        $popup = $this->popup();
        $this->row($popup, '2025-10-10', PopupMetric::Shown, 1);
        $this->row($popup, '2025-10-11', PopupMetric::Shown, 1);
        $this->row($popup, '2026-11-15', PopupMetric::Shown, 1);

        // 400 days before the 15th of November 2026 is the 11th of October 2025
        $this->assertSame(1, $this->service()->prune(400));

        $this->assertSame(
            ['2025-10-11', '2026-11-15'],
            PopupMetricModel::query()->orderBy('day')->pluck('day')->all(),
        );
    }

    public function test_the_command_prunes_with_the_configured_retention(): void
    {
        $popup = $this->popup();
        $this->row($popup, '2025-01-01', PopupMetric::Shown, 1);
        $this->row($popup, '2026-11-15', PopupMetric::Shown, 1);

        $this->prune(0);

        $this->assertSame(1, PopupMetricModel::query()->count());
    }

    public function test_the_command_takes_a_number_of_days_and_refuses_anything_else(): void
    {
        $popup = $this->popup();
        $this->row($popup, '2026-11-10', PopupMetric::Shown, 1);

        $this->prune(1, ['--days' => 'abc']);
        $this->prune(1, ['--days' => '-3']);
        $this->assertSame(1, PopupMetricModel::query()->count());

        $this->prune(0, ['--days' => '2']);
        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    public function test_the_command_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'announcements:prune-metrics'));

        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('0 0 * * *', $event->expression);
    }

    public function test_the_retention_ships_in_the_module_config(): void
    {
        $this->assertSame(400, config('announcements.metrics_retention_days'));
    }

    /**
     * @param  array<string, string>  $options
     */
    private function prune(int $exitCode, array $options = []): void
    {
        $command = $this->artisan('announcements:prune-metrics', $options);
        $this->assertInstanceOf(PendingCommand::class, $command);
        $command->assertExitCode($exitCode);
    }

    private function service(): PopupMetricServiceInterface
    {
        return app(PopupMetricServiceInterface::class);
    }

    private function row(AnnouncementModel $popup, string $day, PopupMetric $metric, int $count): void
    {
        DB::table('announcements_popup_metrics')->insert([
            'id' => (string) Str::uuid(),
            'announcement_id' => $popup->id,
            'day' => $day,
            'metric' => $metric->value,
            'count' => $count,
        ]);
    }

    private function popup(): AnnouncementModel
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

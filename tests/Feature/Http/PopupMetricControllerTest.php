<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Http;

use App\Application\Impersonation\Services\ImpersonationContextInterface;
use App\Domain\Impersonation\Enums\ImpersonationMode;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Announcements\Http\Support\PopupMetricLimits;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupMetricModel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * The browser counts what happens to a window (announcements#69): shown, closed and button
 * pressed. Anonymous counters per display day: no account, session or address is kept, and the
 * route is open to visitors without a session.
 */
final class PopupMetricControllerTest extends ModuleTestCase
{
    use UsesUtcStorage;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useUtcStorage();
        Carbon::setTestNow('2026-11-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_visitor_without_a_session_counts_a_window(): void
    {
        $popup = $this->popup();

        $this->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->postJson($this->url($popup, 'dismissed'))->assertNoContent();
        $this->postJson($this->url($popup, 'action_clicked'))->assertNoContent();

        $this->assertSame(2, $this->counted($popup, 'shown'));
        $this->assertSame(1, $this->counted($popup, 'dismissed'));
        $this->assertSame(1, $this->counted($popup, 'action_clicked'));
    }

    public function test_a_signed_in_account_counts_too_and_nothing_personal_is_kept(): void
    {
        $popup = $this->popup();

        $this->actingAs(UserModel::factory()->create())->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->assertSame(1, $this->counted($popup, 'shown'));
        $this->assertEqualsCanonicalizing(
            ['id', 'announcement_id', 'day', 'metric', 'count'],
            Schema::getColumnListing('announcements_popup_metrics'),
        );
    }

    public function test_the_day_is_the_display_day_not_the_storage_day(): void
    {
        $popup = $this->popup();
        // 23:30 UTC is already the 16th in Madrid, the display zone
        config()->set('app.display_timezone', 'Europe/Madrid');
        Carbon::setTestNow('2026-11-15 23:30:00');

        $this->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->assertSame('2026-11-16', PopupMetricModel::query()->sole()->day);

        Carbon::setTestNow('2026-11-16 22:59:00');
        $this->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->assertSame(2, PopupMetricModel::query()->where('day', '2026-11-16')->sole()->count);
    }

    public function test_each_day_and_metric_has_its_own_row(): void
    {
        $popup = $this->popup();

        $this->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->postJson($this->url($popup, 'dismissed'))->assertNoContent();
        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->assertSame(3, PopupMetricModel::query()->count());
    }

    public function test_only_an_active_window_is_counted(): void
    {
        $inactive = $this->popup(['is_active' => false]);
        $banner = $this->popup(['format' => 'banner']);
        $ended = $this->popup(['ends_at' => Carbon::parse('2020-01-01 00:00:00')]);
        $members = $this->popup(['visibility' => 'members']);

        foreach (['inactive' => $inactive, 'banner' => $banner, 'ended' => $ended, 'members' => $members] as $case => $announcement) {
            $this->assertSame(404, $this->postJson($this->url($announcement, 'shown'))->status(), $case);
        }

        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    public function test_an_unknown_id_is_not_counted(): void
    {
        $this->postJson('/anuncios/ventanas/'.Str::uuid()->toString().'/metrica/shown')->assertNotFound();
        $this->postJson('/anuncios/ventanas/no-es-un-id/metrica/shown')->assertNotFound();

        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    public function test_only_the_three_metrics_exist(): void
    {
        $popup = $this->popup();

        $this->postJson($this->url($popup, 'clicks'))->assertNotFound();
        $this->postJson($this->url($popup, 'SHOWN'))->assertNotFound();

        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    public function test_nothing_is_counted_during_an_impersonation(): void
    {
        $popup = $this->popup();
        $this->app->instance(ImpersonationContextInterface::class, new class implements ImpersonationContextInterface
        {
            public function isActive(): bool
            {
                return true;
            }

            public function impersonator(): ?Authenticatable
            {
                return null;
            }

            public function target(): ?Authenticatable
            {
                return null;
            }

            public function mode(): ImpersonationMode
            {
                return ImpersonationMode::Simulate;
            }
        });

        $this->actingAs(UserModel::factory()->create())->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    public function test_the_route_is_limited_and_protected_like_the_rest_of_the_site(): void
    {
        $route = Route::getRoutes()->getByName('announcements.popups.metric');

        $this->assertInstanceOf(IlluminateRoute::class, $route);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertNotContains('auth', $route->gatherMiddleware());
        $this->assertTrue(collect($route->gatherMiddleware())->contains(fn (string $m): bool => str_starts_with($m, 'throttle:')));
    }

    public function test_the_limits_are_fixed(): void
    {
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '203.0.113.7']);
        $limit = RateLimiter::limiter(PopupMetricLimits::PER_ADDRESS);
        $this->assertIsCallable($limit);
        $result = $limit($request);
        $this->assertInstanceOf(Limit::class, $result);
        $this->assertSame(30, $result->maxAttempts);
        $this->assertSame(60, $result->decaySeconds);
    }

    public function test_the_same_event_on_the_same_window_is_limited_per_minute(): void
    {
        $popup = $this->popup();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->url($popup, 'shown'))->assertNoContent();
        }

        $this->postJson($this->url($popup, 'shown'))->assertTooManyRequests();
        // Another event on the same window is its own count
        $this->postJson($this->url($popup, 'dismissed'))->assertNoContent();
        $this->assertSame(5, $this->counted($popup, 'shown'));
    }

    public function test_every_counter_together_is_limited_per_address(): void
    {
        $status = [];

        for ($i = 0; $i < 7; $i++) {
            $popup = $this->popup();

            for ($j = 0; $j < 5; $j++) {
                $status[] = $this->postJson($this->url($popup, 'shown'))->status();
            }
        }

        $this->assertSame(30, count(array_filter($status, static fn (int $code): bool => $code === 204)));
        $this->assertSame(5, count(array_filter($status, static fn (int $code): bool => $code === 429)));
    }

    public function test_the_counters_go_with_the_announcement(): void
    {
        $popup = $this->popup();
        $this->postJson($this->url($popup, 'shown'))->assertNoContent();

        $popup->delete();

        $this->assertSame(0, PopupMetricModel::query()->count());
    }

    private function counted(AnnouncementModel $popup, string $metric): int
    {
        return (int) (PopupMetricModel::query()
            ->where('announcement_id', $popup->id)
            ->where('metric', $metric)
            ->sum('count'));
    }

    private function url(AnnouncementModel $popup, string $metric): string
    {
        return "/anuncios/ventanas/{$popup->id}/metrica/{$metric}";
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function popup(array $attributes = []): AnnouncementModel
    {
        $model = new AnnouncementModel;
        $model->forceFill([
            'title' => 'Hazte socio',
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            ...$attributes,
        ]);
        $model->save();

        return $model;
    }
}

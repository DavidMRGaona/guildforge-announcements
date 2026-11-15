<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Http;

use App\Application\Impersonation\Services\ImpersonationContextInterface;
use App\Application\Services\UserServiceInterface;
use App\Domain\Events\UserAnonymized;
use App\Domain\Impersonation\Enums\ImpersonationMode;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupViewModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The browser tells the server when it shows or closes a window (announcements#68), so an account
 * does not see it again on another device. Only ids travel; nothing is written for a guest, for
 * a window the account cannot see or during an impersonation.
 */
final class PopupViewControllerTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_showing_a_window_is_remembered_for_the_account(): void
    {
        Carbon::setTestNow('2026-11-15 10:00:00');
        $user = UserModel::factory()->create();
        $popup = $this->popup();

        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();

        $view = PopupViewModel::query()->where('user_id', $user->id)->where('announcement_id', $popup->id)->sole();
        $this->assertInstant(Carbon::now(), $view->last_shown_at);
        $this->assertNull($view->dismissed_at);
    }

    public function test_showing_it_again_moves_the_shown_time_without_a_second_row(): void
    {
        $user = UserModel::factory()->create();
        $popup = $this->popup();

        Carbon::setTestNow('2026-11-15 10:00:00');
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();
        Carbon::setTestNow('2026-11-20 18:30:00');
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();

        $view = PopupViewModel::query()->sole();
        $this->assertInstant(Carbon::parse('2026-11-20 18:30:00'), $view->last_shown_at);
    }

    public function test_closing_a_window_is_remembered_and_counts_as_shown(): void
    {
        Carbon::setTestNow('2026-11-15 10:00:00');
        $user = UserModel::factory()->create();
        $popup = $this->popup();

        $this->actingAs($user)->postJson($this->url($popup, 'dismissed'))->assertNoContent();

        $view = PopupViewModel::query()->sole();
        $this->assertInstant(Carbon::now(), $view->dismissed_at);
        $this->assertInstant(Carbon::now(), $view->last_shown_at);
    }

    public function test_closing_keeps_the_time_it_was_shown(): void
    {
        $user = UserModel::factory()->create();
        $popup = $this->popup();

        Carbon::setTestNow('2026-11-15 10:00:00');
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();
        Carbon::setTestNow('2026-11-15 10:05:00');
        $this->actingAs($user)->postJson($this->url($popup, 'dismissed'))->assertNoContent();

        $view = PopupViewModel::query()->sole();
        $this->assertInstant(Carbon::parse('2026-11-15 10:00:00'), $view->last_shown_at);
        $this->assertInstant(Carbon::parse('2026-11-15 10:05:00'), $view->dismissed_at);
    }

    public function test_each_account_has_its_own_memory(): void
    {
        $popup = $this->popup();
        $first = UserModel::factory()->create();
        $second = UserModel::factory()->create();

        $this->actingAs($first)->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->actingAs($second)->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->assertSame(2, PopupViewModel::query()->count());
    }

    public function test_a_guest_cannot_leave_notices(): void
    {
        $popup = $this->popup();

        $this->postJson($this->url($popup, 'shown'))->assertUnauthorized();

        $this->assertSame(0, PopupViewModel::query()->count());
    }

    public function test_only_a_window_the_account_can_see_is_remembered(): void
    {
        $user = UserModel::factory()->create();

        $unknown = (string) Str::uuid();
        $banner = $this->popup(['format' => 'banner']);
        $inactive = $this->popup(['is_active' => false]);
        $members = $this->popup(['visibility' => 'members']);
        $guestsOnly = $this->popup(['visibility' => 'guests']);

        foreach ([$unknown, $banner->id, $inactive->id, $members->id, $guestsOnly->id] as $id) {
            $this->actingAs($user)->postJson("/anuncios/ventanas/{$id}/mostrada")->assertNotFound();
            $this->actingAs($user)->postJson("/anuncios/ventanas/{$id}/cerrada")->assertNotFound();
        }

        $this->assertSame(0, PopupViewModel::query()->count());
    }

    public function test_something_that_is_not_an_id_is_not_a_route(): void
    {
        $this->actingAs(UserModel::factory()->create())
            ->postJson('/anuncios/ventanas/no-es-un-id/mostrada')
            ->assertNotFound();
    }

    public function test_nothing_is_written_during_an_impersonation(): void
    {
        $user = UserModel::factory()->create();
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

        // No visible error: the page behaves the same, the server just writes nothing
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->actingAs($user)->postJson($this->url($popup, 'dismissed'))->assertNoContent();

        $this->assertSame(0, PopupViewModel::query()->count());
    }

    public function test_the_notices_are_limited_per_account(): void
    {
        $user = UserModel::factory()->create();
        $popup = $this->popup();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();
        }

        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertTooManyRequests();
    }

    public function test_the_routes_are_protected_like_the_rest_of_the_site(): void
    {
        foreach (['announcements.popups.shown', 'announcements.popups.dismissed'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertInstanceOf(IlluminateRoute::class, $route);

            $middleware = $route->gatherMiddleware();
            $this->assertContains('web', $middleware);
            $this->assertContains('auth', $middleware);
            $this->assertContains('email.verified-if-required', $middleware);
            $this->assertNotEmpty(array_filter($middleware, static fn (mixed $item): bool => is_string($item) && str_starts_with($item, 'throttle:')));
            $this->assertSame(['POST'], $route->methods());
        }
    }

    public function test_what_an_account_remembers_goes_with_the_announcement(): void
    {
        $user = UserModel::factory()->create();
        $popup = $this->popup();
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();

        $popup->delete();

        $this->assertSame(0, PopupViewModel::query()->count());
    }

    public function test_what_an_account_remembers_goes_with_the_account(): void
    {
        $user = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $popup = $this->popup();
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->actingAs($other)->postJson($this->url($popup, 'shown'))->assertNoContent();

        // Anonymizing keeps the row of the account: the memory is dropped through the core's event
        event(new UserAnonymized($user->id));

        $this->assertSame([$other->id], PopupViewModel::query()->pluck('user_id')->all());
    }

    public function test_anonymizing_through_the_core_drops_what_the_account_remembers(): void
    {
        $user = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $popup = $this->popup();
        $this->actingAs($user)->postJson($this->url($popup, 'shown'))->assertNoContent();
        $this->actingAs($other)->postJson($this->url($popup, 'shown'))->assertNoContent();

        $this->app->make(UserServiceInterface::class)->anonymize($user->id);

        $this->assertSame([$other->id], PopupViewModel::query()->pluck('user_id')->all());
    }

    private function assertInstant(Carbon $expected, ?Carbon $actual): void
    {
        $this->assertNotNull($actual);
        $this->assertTrue($expected->equalTo($actual));
    }

    private function url(AnnouncementModel $popup, string $notice): string
    {
        return "/anuncios/ventanas/{$popup->id}/".($notice === 'shown' ? 'mostrada' : 'cerrada');
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

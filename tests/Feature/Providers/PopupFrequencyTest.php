<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Providers;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupViewModel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * What an account has already seen decides which windows the server shares with it
 * (announcements#68): once never again, every N days after the last time it was shown, and once
 * per session left to the browser.
 */
final class PopupFrequencyTest extends ModuleTestCase
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

    public function test_a_window_the_account_never_saw_is_shared(): void
    {
        $this->popup('Única', ['popup_frequency' => 'once']);
        $this->popup('Cada semana', ['popup_frequency' => 'days', 'popup_frequency_days' => 7, 'priority' => 4]);
        $this->popup('Cada sesión', ['popup_frequency' => 'session', 'priority' => 3]);

        $this->assertSame(['Única', 'Cada semana', 'Cada sesión'], $this->titlesFor($this->member()));
    }

    public function test_a_window_seen_once_is_not_shared_again_with_that_account_only(): void
    {
        $popup = $this->popup('Única', ['popup_frequency' => 'once']);
        $seen = $this->member();
        $other = $this->member();
        $this->remembers($seen, $popup, shown: '-1 year');

        $this->assertSame([], $this->titlesFor($seen));
        $this->assertSame(['Única'], $this->titlesFor($other));
    }

    public function test_a_guest_always_gets_the_window_because_only_the_browser_knows_it(): void
    {
        $popup = $this->popup('Única', ['popup_frequency' => 'once']);
        $this->remembers($this->member(), $popup, shown: '-1 day');

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1));
    }

    public function test_closing_counts_as_seeing_it(): void
    {
        $popup = $this->popup('Única', ['popup_frequency' => 'once']);
        $user = $this->member();
        $this->remembers($user, $popup, dismissed: '-1 hour');

        $this->assertSame([], $this->titlesFor($user));
    }

    public function test_a_window_every_n_days_returns_when_the_days_have_passed(): void
    {
        $popup = $this->popup('Cada semana', ['popup_frequency' => 'days', 'popup_frequency_days' => 7]);
        $user = $this->member();
        $view = $this->remembers($user, $popup, shown: '-6 days -23 hours');

        $this->assertSame([], $this->titlesFor($user));

        // Time passes: the same memory, an hour later
        Carbon::setTestNow(Carbon::now()->addHour());
        $this->assertSame(['Cada semana'], $this->titlesFor($user));

        // Shown again: the days count from the new time
        $view->update(['last_shown_at' => Carbon::now()]);
        $this->assertSame([], $this->titlesFor($user));

        Carbon::setTestNow(Carbon::now()->addDays(7));
        $this->assertSame(['Cada semana'], $this->titlesFor($user));
    }

    public function test_a_window_every_session_is_shared_whatever_the_account_saw(): void
    {
        $popup = $this->popup('Cada sesión', ['popup_frequency' => 'session']);
        $user = $this->member();
        $this->remembers($user, $popup, shown: '-1 minute', dismissed: '-1 minute');

        $this->assertSame(['Cada sesión'], $this->titlesFor($user));
    }

    public function test_the_windows_before_the_frequency_existed_open_once(): void
    {
        // A row as the migration leaves the windows saved before this setting
        $popup = $this->popup('Antigua');
        $user = $this->member();
        $this->remembers($user, $popup, shown: '-2 years');

        $this->assertSame('once', DB::table('announcements_announcements')->where('id', $popup->id)->value('popup_frequency'));
        $this->assertSame([], $this->titlesFor($user));
    }

    public function test_banners_are_not_affected_by_what_an_account_saw(): void
    {
        $banner = $this->popup('Banda', ['format' => 'banner']);
        $user = $this->member();
        $this->remembers($user, $banner, shown: '-1 day');

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcements', 1)
                ->where('announcements.0.title', 'Banda'));
    }

    public function test_the_memory_is_read_once_per_request(): void
    {
        $this->popup('Una');
        $this->popup('Otra');
        $user = $this->member();
        $queries = 0;
        DB::listen(static function ($query) use (&$queries): void {
            if (str_contains($query->sql, 'announcements_popup_views')) {
                $queries++;
            }
        });

        $this->actingAs($user)->get('/')->assertOk();

        $this->assertSame(1, $queries);
    }

    public function test_the_instants_are_stored_in_the_storage_zone_and_compared_as_instants(): void
    {
        // 10:00 UTC is 11:00 in Madrid in November: the row holds the UTC wall time
        $popup = $this->popup('Cada día', ['popup_frequency' => 'days', 'popup_frequency_days' => 1]);
        $user = $this->member();
        $this->actingAs($user)->postJson("/anuncios/ventanas/{$popup->id}/mostrada")->assertNoContent();

        $this->assertSame('2026-11-15 10:00:00', substr((string) DB::table('announcements_popup_views')->value('last_shown_at'), 0, 19));
        $this->assertSame([], $this->titlesFor($user));

        Carbon::setTestNow('2026-11-16 09:59:59');
        $this->assertSame([], $this->titlesFor($user));

        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->assertSame(['Cada día'], $this->titlesFor($user));
    }

    /**
     * @return list<string>
     */
    private function titlesFor(UserModel $user): array
    {
        $titles = [];

        $this->actingAs($user)->get('/')->assertInertia(function (AssertableInertia $page) use (&$titles): AssertableInertia {
            $popups = $page->toArray()['props']['announcementPopups'] ?? [];
            $titles = array_values(array_map(static fn (array $popup): string => (string) $popup['title'], $popups));

            return $page;
        });

        return $titles;
    }

    private function member(): UserModel
    {
        return UserModel::factory()->create();
    }

    private function remembers(UserModel $user, AnnouncementModel $popup, ?string $shown = null, ?string $dismissed = null): PopupViewModel
    {
        $view = new PopupViewModel;
        $view->forceFill([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'announcement_id' => $popup->id,
            'last_shown_at' => $shown !== null ? Carbon::now()->modify($shown) : null,
            'dismissed_at' => $dismissed !== null ? Carbon::now()->modify($dismissed) : null,
        ])->save();

        return $view;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function popup(string $title, array $attributes = []): AnnouncementModel
    {
        $model = new AnnouncementModel;
        $model->forceFill([
            'title' => $title,
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

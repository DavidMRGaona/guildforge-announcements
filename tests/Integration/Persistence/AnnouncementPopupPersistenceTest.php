<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Persistence;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Announcements\Application\DTOs\CreateAnnouncementDTO;
use Modules\Announcements\Application\DTOs\UpdateAnnouncementDTO;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\Enums\PopupDevice;
use Modules\Announcements\Domain\Enums\PopupFrequency;
use Modules\Announcements\Domain\Enums\PopupPages;
use Modules\Announcements\Domain\Enums\PopupShape;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use Modules\Announcements\Domain\ValueObjects\PopupBlock;
use Modules\Announcements\Domain\ValueObjects\PopupTriggers;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementPopupPersistenceTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_an_announcement_saved_without_a_format_is_a_banner(): void
    {
        $id = (string) Str::uuid();
        DB::table('announcements_announcements')->insert([
            'id' => $id,
            'title' => 'Cierre por vacaciones',
            'content' => '<p>Agosto.</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('announcements_announcements')->where('id', $id)->first();
        $this->assertNotNull($row);
        $this->assertSame('banner', $row->format);
        $this->assertSame('centered', $row->popup_shape);
        $this->assertSame('all', $row->popup_pages);
        $this->assertSame('all', $row->popup_device);
        // Windows saved before the frequency existed open once
        $this->assertSame('once', $row->popup_frequency);
        $this->assertNull($row->popup_frequency_days);

        $announcement = $this->repository()->findOrFail(AnnouncementId::fromString($id));
        $this->assertSame(AnnouncementFormat::Banner, $announcement->format());
        $this->assertNull($announcement->popup());
    }

    public function test_a_window_is_saved_and_read_back_whole(): void
    {
        $popup = new AnnouncementPopup(
            shape: PopupShape::Corner,
            imagePublicId: 'announcements/popups/2026/11/9a2f.webp',
            action: new PopupAction('Hazte socio', 'https://example.org/alta'),
            triggers: new PopupTriggers(delaySeconds: 8, scrollPercent: 40, exitIntent: true, pageViews: 3),
            pages: PopupPages::Paths,
            paths: ['/eventos*', '/articulos'],
            device: PopupDevice::Desktop,
            imageAlt: 'Cartel de la campaña de socios',
            frequency: PopupFrequency::Days,
            frequencyDays: 14,
        );

        $created = $this->service()->create(new CreateAnnouncementDTO(
            title: 'Únete',
            content: '<p>Texto</p>',
            format: AnnouncementFormat::Popup,
            popup: $popup,
        ));

        $model = AnnouncementModel::findOrFail($created->id);
        $this->assertSame(AnnouncementFormat::Popup, $model->format);
        $this->assertSame(['/eventos*', '/articulos'], $model->popup_paths);
        $this->assertSame(8, $model->popup_trigger_delay);
        $this->assertSame('days', $model->popup_frequency);
        $this->assertSame(14, $model->popup_frequency_days);

        $read = $this->repository()->findOrFail(AnnouncementId::fromString($created->id));
        $this->assertEquals($popup, $read->popup());
    }

    public function test_a_window_keeps_its_block_and_whether_it_hides_without_it(): void
    {
        $popup = new AnnouncementPopup(
            block: new PopupBlock('newsletter.subscribe', ['heading' => 'Suscríbete', 'list' => 3]),
            hideWhenBlockUnavailable: false,
        );

        $created = $this->service()->create(new CreateAnnouncementDTO(
            title: 'Únete',
            content: '<p>Texto</p>',
            format: AnnouncementFormat::Popup,
            popup: $popup,
        ));

        $model = AnnouncementModel::findOrFail($created->id);
        $this->assertSame(['key' => 'newsletter.subscribe', 'settings' => ['heading' => 'Suscríbete', 'list' => 3]], $model->popup_block);
        $this->assertFalse($model->popup_block_hide_when_unavailable);
        $this->assertEquals($popup, $this->repository()->findOrFail(AnnouncementId::fromString($created->id))->popup());
    }

    public function test_a_window_without_a_block_stores_none_and_hides_without_one(): void
    {
        $created = $this->service()->create(new CreateAnnouncementDTO(
            title: 'Únete',
            content: '<p>Texto</p>',
            format: AnnouncementFormat::Popup,
            popup: new AnnouncementPopup,
        ));

        $model = AnnouncementModel::findOrFail($created->id);
        $this->assertNull($model->popup_block);
        $this->assertTrue($model->popup_block_hide_when_unavailable);
        $this->assertNull($this->repository()->findOrFail(AnnouncementId::fromString($created->id))->popup()?->block);
    }

    public function test_the_form_state_of_a_block_without_a_key_is_no_block(): void
    {
        $dto = CreateAnnouncementDTO::fromArray([
            'title' => 'Únete',
            'content' => '<p>Texto</p>',
            'format' => 'popup',
            'popup_block' => ['key' => null, 'settings' => ['heading' => 'Suscríbete']],
        ]);

        $this->assertNull($dto->popup?->block);
    }

    public function test_the_block_columns_default_to_no_block_and_hiding_the_window(): void
    {
        $id = (string) Str::uuid();
        DB::table('announcements_announcements')->insert([
            'id' => $id,
            'title' => 'Antigua',
            'content' => '<p>Texto</p>',
            'format' => 'popup',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('announcements_announcements')->where('id', $id)->first();
        $this->assertNull($row?->popup_block);
        $this->assertTrue((bool) $row?->popup_block_hide_when_unavailable);
    }

    public function test_switching_a_window_back_to_a_banner_keeps_its_window_settings(): void
    {
        $created = $this->service()->create(new CreateAnnouncementDTO(
            title: 'Únete',
            content: '<p>Texto</p>',
            format: AnnouncementFormat::Popup,
            popup: new AnnouncementPopup(shape: PopupShape::Corner, device: PopupDevice::Mobile),
        ));

        $this->service()->update(new UpdateAnnouncementDTO(
            id: $created->id,
            title: 'Únete',
            content: '<p>Texto</p>',
            visibility: AnnouncementVisibility::Public,
            position: AnnouncementPosition::AfterHeader,
            priority: 5,
        ));

        $model = AnnouncementModel::findOrFail($created->id);
        $this->assertSame(AnnouncementFormat::Banner, $model->format);
        $this->assertSame('corner', $model->popup_shape);
        $this->assertSame('mobile', $model->popup_device);
    }

    public function test_a_window_whose_settings_cannot_be_read_is_never_offered(): void
    {
        $this->popupRow('Rota', ['popup_pages' => 'paths', 'popup_paths' => null]);
        $this->popupRow('Retardo imposible', ['popup_trigger_delay' => 500]);
        $this->popupRow('Enlace peligroso', ['popup_action_label' => 'Pulsa', 'popup_action_url' => 'javascript:alert(1)']);
        $this->popupRow('Frecuencia desconocida', ['popup_frequency' => 'always']);
        $this->popupRow('Sin días', ['popup_frequency' => 'days', 'popup_frequency_days' => null]);
        $this->popupRow('Demasiados días', ['popup_frequency' => 'days', 'popup_frequency_days' => 400]);
        $this->popupRow('Buena');

        $titles = array_map(
            static fn ($dto): string => $dto->title,
            $this->service()->getActiveForUser(null, AnnouncementFormat::Popup),
        );

        $this->assertSame(['Buena'], $titles);
    }

    public function test_the_list_can_be_limited_to_one_format(): void
    {
        $this->bannerRow('Banda');
        $this->popupRow('Ventana');

        $this->assertSame(['Banda'], $this->titles(AnnouncementFormat::Banner));
        $this->assertSame(['Ventana'], $this->titles(AnnouncementFormat::Popup));
        $this->assertEqualsCanonicalizing(['Banda', 'Ventana'], $this->titles(null));
    }

    public function test_windows_come_by_priority_and_then_by_the_most_recent_start(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');

        $this->popupRow('Baja reciente', ['priority' => 3, 'starts_at' => now()->subHour()]);
        $this->popupRow('Alta antigua', ['priority' => 8, 'starts_at' => now()->subDays(5)]);
        $this->popupRow('Alta reciente', ['priority' => 8, 'starts_at' => now()->subDay()]);
        $this->popupRow('Alta sin fecha, creada hace dos días', ['priority' => 8, 'created_at' => now()->subDays(2)]);

        $this->assertSame(
            ['Alta reciente', 'Alta sin fecha, creada hace dos días', 'Alta antigua', 'Baja reciente'],
            $this->titles(AnnouncementFormat::Popup),
        );
    }

    public function test_guests_only_is_offered_to_visitors_without_a_session(): void
    {
        $this->popupRow('Para visitas', ['visibility' => 'guests']);

        $this->assertSame(['Para visitas'], $this->titles(AnnouncementFormat::Popup));
        $this->assertSame([], array_map(
            static fn ($dto): string => $dto->title,
            $this->service()->getActiveForUser(UserModel::factory()->create(), AnnouncementFormat::Popup),
        ));
    }

    public function test_the_response_carries_the_window_and_it_can_always_be_closed(): void
    {
        $this->popupRow('Ventana', [
            'is_dismissible' => false,
            'popup_shape' => 'corner',
            'popup_image_public_id' => 'announcements/popups/2026/11/a.webp',
            'popup_image_alt' => 'Mesa de rol con dados',
            'popup_action_label' => 'Ver eventos',
            'popup_action_url' => '/eventos',
            'popup_trigger_delay' => null,
            'popup_trigger_scroll' => 50,
            'popup_trigger_exit_intent' => true,
            'popup_pages' => 'home',
            'popup_device' => 'mobile',
        ]);
        $this->bannerRow('Banda', ['is_dismissible' => false]);

        $popup = $this->service()->getActiveForUser(null, AnnouncementFormat::Popup)[0]->toArray();
        $banner = $this->service()->getActiveForUser(null, AnnouncementFormat::Banner)[0]->toArray();

        $this->assertSame('popup', $popup['format']);
        $this->assertTrue($popup['is_dismissible']);
        $this->assertSame([
            'shape' => 'corner',
            'image_public_id' => 'announcements/popups/2026/11/a.webp',
            'image_alt' => 'Mesa de rol con dados',
            'action' => ['label' => 'Ver eventos', 'url' => '/eventos'],
            'triggers' => ['delay_seconds' => null, 'scroll_percent' => 50, 'exit_intent' => true, 'page_views' => null],
            'pages' => 'home',
            'paths' => [],
            'device' => 'mobile',
            'frequency' => 'once',
            'frequency_days' => null,
            'block' => null,
        ], $popup['popup']);

        $this->assertSame('banner', $banner['format']);
        $this->assertFalse($banner['is_dismissible']);
        $this->assertNull($banner['popup']);
    }

    /**
     * @return list<string>
     */
    private function titles(?AnnouncementFormat $format): array
    {
        return array_values(array_map(
            static fn ($dto): string => $dto->title,
            $this->service()->getActiveForUser(null, $format),
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function popupRow(string $title, array $attributes = []): AnnouncementModel
    {
        return $this->bannerRow($title, ['format' => 'popup', ...$attributes]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bannerRow(string $title, array $attributes = []): AnnouncementModel
    {
        $model = new AnnouncementModel;
        $model->forceFill([
            'title' => $title,
            'content' => '<p>Texto</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            ...$attributes,
        ]);
        $model->save();

        return $model;
    }

    private function service(): AnnouncementServiceInterface
    {
        return $this->app->make(AnnouncementServiceInterface::class);
    }

    private function repository(): AnnouncementRepositoryInterface
    {
        return $this->app->make(AnnouncementRepositoryInterface::class);
    }

    public function test_the_response_carries_the_frequency_and_its_days(): void
    {
        $this->popupRow('Cada semana', ['popup_frequency' => 'days', 'popup_frequency_days' => 7]);
        $this->popupRow('Cada sesión', ['popup_frequency' => 'session', 'popup_frequency_days' => 7, 'priority' => 1]);

        [$weekly, $session] = $this->service()->getActiveForUser(null, AnnouncementFormat::Popup);

        $this->assertSame('days', $weekly->popup['frequency'] ?? null);
        $this->assertSame(7, $weekly->popup['frequency_days'] ?? null);
        // The days of a window that returns every session are not kept
        $this->assertSame('session', $session->popup['frequency'] ?? null);
        $this->assertArrayHasKey('frequency_days', $session->popup ?? []);
        $this->assertNull($session->popup['frequency_days'] ?? null);
    }
}

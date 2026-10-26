<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Time;

use App\Infrastructure\Persistence\Eloquent\Concerns\StoresInstantsInStorageTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnnouncementRepository;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * With UTC storage the instants in the database are UTC, but the panel and the JSON show the
 * site's Madrid wall time.
 */
final class UtcStorageTest extends ModuleTestCase
{
    use InteractsWithModulePanel;
    use UsesUtcStorage;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useUtcStorage();
        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_every_model_stores_instants_in_the_storage_timezone(): void
    {
        $files = glob(dirname(__DIR__, 3).'/src/Infrastructure/Persistence/Eloquent/Models/*.php') ?: [];
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $class = 'Modules\\Announcements\\Infrastructure\\Persistence\\Eloquent\\Models\\'.basename($file, '.php');

            $this->assertContains(StoresInstantsInStorageTimezone::class, class_uses_recursive($class), $class);
        }
    }

    public function test_the_panel_table_shows_the_schedule_in_madrid_time(): void
    {
        $announcement = $this->announcement();

        $this->assertSame('2026-07-01 10:00:00', substr((string) DB::table('announcements_announcements')->where('id', $announcement->id)->value('starts_at'), 0, 19));

        Livewire::test(ListAnnouncements::class)
            ->assertTableColumnFormattedStateSet('starts_at', '01/07/2026 12:00', $announcement)
            ->assertTableColumnFormattedStateSet('ends_at', '01/07/2026 16:00', $announcement);
    }

    public function test_saving_the_edit_form_keeps_the_stored_instants(): void
    {
        $announcement = $this->announcement();

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $row = DB::table('announcements_announcements')->where('id', $announcement->id)->first();
        $this->assertSame('2026-07-01 10:00:00', substr((string) $row?->starts_at, 0, 19));
        $this->assertSame('2026-07-01 14:00:00', substr((string) $row?->ends_at, 0, 19));
    }

    public function test_the_response_serializes_the_schedule_in_the_display_zone(): void
    {
        $announcement = $this->announcement();
        $entity = app(EloquentAnnouncementRepository::class)->find(new AnnouncementId($announcement->id));
        $this->assertNotNull($entity);

        $dto = AnnouncementResponseDTO::fromEntity($entity);

        $this->assertSame('2026-07-01T12:00:00+02:00', $dto->startsAt);
        $this->assertSame('2026-07-01T16:00:00+02:00', $dto->endsAt);
    }

    private function announcement(): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p>El local cierra en agosto.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'starts_at' => CarbonImmutable::parse('2026-07-01T10:00:00Z'),
            'ends_at' => CarbonImmutable::parse('2026-07-01T14:00:00Z'),
        ])->refresh();
    }
}

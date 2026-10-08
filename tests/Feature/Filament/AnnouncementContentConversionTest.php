<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * With the host's real converter: once converted, saving the announcement in the panel
 * without touching the editor must not rewrite its content again.
 */
final class AnnouncementContentConversionTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    // Trix markup as the Filament 3 editor saved it, with every tool the resource offers
    private const TRIX = '<div>Primera línea<br>segunda línea</div><div><br></div><h1>Título</h1>'
        .'<div><strong>negrita</strong> <em>cursiva</em> <del>tachado</del> <a href="https://example.test">enlace</a></div>'
        .'<ul><li>uno</li><li>dos</li></ul><ol><li>primero</li></ol><blockquote>cita</blockquote><pre>código</pre>';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    public function test_converted_content_survives_a_save_in_the_panel(): void
    {
        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
        $announcement = AnnouncementModel::create([
            'title' => 'Aviso',
            'content' => self::TRIX,
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);
        $migration = require dirname(__DIR__, 3).'/database/migrations/2026_10_08_000001_convert_announcement_content_to_tiptap.php';

        $migration->up();
        $converted = $announcement->refresh()->content;

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotSame(self::TRIX, $converted);
        $this->assertStringContainsString('segunda línea', $converted);
        $this->assertSame($converted, $announcement->refresh()->content);
    }
}

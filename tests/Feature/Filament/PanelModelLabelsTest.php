<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Filament builds the list title and the breadcrumb from the plural model label, and the
 * create button («Crear anuncio») and the delete modal («Borrar anuncio») from the singular
 * one, as they are written. The plural starts with a capital, as in the core («Eventos»); the
 * singular stays in lowercase because it sits inside a sentence.
 */
final class PanelModelLabelsTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function labels(): array
    {
        return [
            'es' => ['es', 'anuncio', 'Anuncios'],
            'en' => ['en', 'announcement', 'Announcements'],
        ];
    }

    #[DataProvider('labels')]
    public function test_the_singular_label_is_in_lowercase_and_the_plural_starts_with_a_capital(string $locale, string $singular, string $plural): void
    {
        $this->app->setLocale($locale);

        $this->assertSame($singular, AnnouncementResource::getModelLabel());
        $this->assertSame($plural, AnnouncementResource::getPluralModelLabel());
    }

    #[DataProvider('labels')]
    public function test_the_breadcrumb_of_the_list_is_the_plural_with_a_capital(string $locale, string $singular, string $plural): void
    {
        $this->app->setLocale($locale);

        $this->assertSame($plural, AnnouncementResource::getBreadcrumb());
    }

    #[DataProvider('labels')]
    public function test_the_list_title_starts_with_a_capital(string $locale, string $singular, string $plural): void
    {
        $this->app->setLocale($locale);

        $instance = Livewire::test(ListAnnouncements::class)->instance();
        $this->assertInstanceOf(Page::class, $instance);

        $this->assertSame($plural, $this->plain($instance->getTitle()));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function createButtons(): array
    {
        return [
            'es' => ['es', 'Crear anuncio'],
            'en' => ['en', 'New announcement'],
        ];
    }

    #[DataProvider('createButtons')]
    public function test_the_create_button_keeps_the_singular_in_lowercase(string $locale, string $label): void
    {
        $this->app->setLocale($locale);

        Livewire::test(ListAnnouncements::class)->assertSee($label);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function deleteHeadings(): array
    {
        return [
            'es' => ['es', 'Borrar anuncio'],
            'en' => ['en', 'Delete announcement'],
        ];
    }

    #[DataProvider('deleteHeadings')]
    public function test_deleting_an_announcement_keeps_the_singular_in_lowercase(string $locale, string $heading): void
    {
        $this->app->setLocale($locale);
        $announcement = AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p>El local cierra en agosto.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        $instance = Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->mountAction(DeleteAction::class)
            ->instance();
        $this->assertInstanceOf(EditAnnouncement::class, $instance);

        $this->assertSame($heading, $this->plain($instance->getMountedAction()?->getModalHeading()));
    }

    #[DataProvider('deleteHeadings')]
    public function test_deleting_from_the_table_keeps_the_singular_in_lowercase(string $locale, string $heading): void
    {
        $this->app->setLocale($locale);
        $announcement = AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p>El local cierra en agosto.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        $instance = Livewire::test(ListAnnouncements::class)
            ->mountTableAction('delete', $announcement)
            ->instance();
        $this->assertInstanceOf(ListAnnouncements::class, $instance);

        $this->assertSame($heading, $this->plain($instance->getMountedAction()?->getModalHeading()));
    }

    private function plain(Htmlable|string|null $text): string
    {
        return $text instanceof Htmlable ? $text->toHtml() : (string) $text;
    }
}

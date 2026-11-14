<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Pages;

use App\Application\Authorization\Services\AuthorizationServiceInterface;
use App\Application\Modules\Services\ModuleManagerServiceInterface;
use App\Domain\Modules\ValueObjects\ModuleName;
use App\Filament\Clusters\SettingsCluster;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * How the banner of announcements is shown, in «Sistema → Configuración» (announcements#66).
 * The values live in the same `module_settings:announcements` row the generic module page used.
 *
 * @property Schema $form
 */
final class AnnouncementsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public const string PERMISSION = 'announcements:settings.manage';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 105;

    protected static ?string $slug = 'anuncios';

    protected string $view = 'filament.pages.simple-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('announcements::announcements.settings.navigation');
    }

    public function getTitle(): string
    {
        return __('announcements::announcements.settings.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && app(AuthorizationServiceInterface::class)->can($user, self::PERMISSION);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill(app(ModuleManagerServiceInterface::class)->getSettings(new ModuleName('announcements')));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('announcements::announcements.settings.display'))
                    ->description(__('announcements::announcements.settings.display_description'))
                    ->schema([
                        Toggle::make('show_banner')
                            ->label(__('announcements::announcements.settings.show_banner'))
                            ->helperText(__('announcements::announcements.settings.show_banner_help'))
                            ->default(true),

                        Toggle::make('auto_rotate')
                            ->label(__('announcements::announcements.settings.auto_rotate'))
                            ->helperText(__('announcements::announcements.settings.auto_rotate_help'))
                            ->default(true)
                            ->live(),

                        TextInput::make('rotate_interval')
                            ->label(__('announcements::announcements.settings.rotate_interval'))
                            ->helperText(__('announcements::announcements.settings.rotate_interval_help'))
                            ->numeric()
                            ->suffix('ms')
                            ->default(5000)
                            ->minValue(1000)
                            ->maxValue(30000)
                            ->visible(fn (Get $get): bool => $get('auto_rotate') === true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        // Livewire methods can be called without the page's own checks
        abort_unless(static::canAccess(), 403);

        app(ModuleManagerServiceInterface::class)->updateSettings(
            new ModuleName('announcements'),
            $this->form->getState(),
        );

        Notification::make()
            ->title(__('announcements::announcements.settings.saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('announcements::announcements.settings.save'))
                ->submit('save'),
        ];
    }
}

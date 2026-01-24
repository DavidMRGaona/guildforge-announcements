<?php

declare(strict_types=1);

namespace Modules\Announcements;

use App\Application\Modules\DTOs\NavigationItemDTO;
use App\Application\Modules\DTOs\PermissionDTO;
use App\Application\Modules\DTOs\SlotRegistrationDTO;
use App\Modules\ModuleServiceProvider;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Inertia\Inertia;
use Modules\Announcements\Application\Services\AnnouncementService;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnnouncementRepository;
use Modules\Announcements\Policies\AnnouncementPolicy;

final class AnnouncementsServiceProvider extends ModuleServiceProvider
{
    public function moduleName(): string
    {
        return 'announcements';
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            $this->modulePath('config/module.php'),
            'announcements'
        );

        // Bind repository interface to implementation
        $this->app->bind(
            AnnouncementRepositoryInterface::class,
            EloquentAnnouncementRepository::class
        );

        // Bind service interface to implementation
        $this->app->bind(
            AnnouncementServiceInterface::class,
            AnnouncementService::class
        );
    }

    public function boot(): void
    {
        parent::boot();

        // Share announcements with Inertia if available
        if (class_exists(Inertia::class)) {
            Inertia::share('announcements', function () {
                if (! $this->app->bound(AnnouncementServiceInterface::class)) {
                    return [];
                }

                $service = $this->app->make(AnnouncementServiceInterface::class);
                $user = auth()->user();

                return array_map(
                    fn ($dto) => $dto->toArray(),
                    $service->getActiveForUser($user)
                );
            });
        }
    }

    public function onEnable(): void
    {
        // Migration is handled by the module system
    }

    public function onDisable(): void
    {
        // Cleanup if needed
    }

    /**
     * Register Filament resources provided by this module.
     *
     * @return array<class-string<\Filament\Resources\Resource>>
     */
    public function registerFilamentResources(): array
    {
        return [
            AnnouncementResource::class,
        ];
    }

    /**
     * Register policies provided by this module.
     *
     * @return array<class-string, class-string>
     */
    public function registerPolicies(): array
    {
        return [
            AnnouncementModel::class => AnnouncementPolicy::class,
        ];
    }

    /**
     * Register navigation groups provided by this module.
     *
     * @return array<string, array{icon?: string, sort?: int}>
     */
    public function registerNavigationGroups(): array
    {
        return [
            __('announcements::announcements.navigation_group') => [
                'sort' => 20, // After 'Contenido' (10), before 'Páginas' (30)
            ],
        ];
    }

    /**
     * @return array<PermissionDTO>
     */
    public function registerPermissions(): array
    {
        return [
            new PermissionDTO(
                name: 'view',
                label: __('announcements::announcements.permissions.view'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['admin', 'editor', 'member'],
            ),
            new PermissionDTO(
                name: 'create',
                label: __('announcements::announcements.permissions.create'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['admin', 'editor'],
            ),
            new PermissionDTO(
                name: 'update',
                label: __('announcements::announcements.permissions.update'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['admin', 'editor'],
            ),
            new PermissionDTO(
                name: 'delete',
                label: __('announcements::announcements.permissions.delete'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['admin'],
            ),
        ];
    }

    /**
     * @return array<NavigationItemDTO>
     */
    public function registerNavigation(): array
    {
        return [
            new NavigationItemDTO(
                label: __('announcements::announcements.navigation'),
                route: 'filament.admin.resources.announcements.index',
                icon: 'heroicon-o-megaphone',
                group: __('announcements::announcements.navigation_group'),
                sort: 1,
                permissions: ['announcements.view'],
                module: 'announcements',
            ),
        ];
    }

    /**
     * Register slot components for the frontend layout.
     * Register a banner component for each possible position.
     *
     * @return array<SlotRegistrationDTO>
     */
    public function registerSlots(): array
    {
        $slots = [];

        foreach (AnnouncementPosition::cases() as $position) {
            $slots[] = new SlotRegistrationDTO(
                slot: $position->value,
                component: 'components/AnnouncementBanner.vue',
                module: $this->moduleName(),
                order: 0,
                props: ['slotPosition' => $position->value],
                dataKeys: ['announcements'],
            );
        }

        return $slots;
    }

    /**
     * Get the Filament form schema for module settings.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public function getSettingsSchema(): array
    {
        return [
            Section::make(__('announcements::announcements.settings.display'))
                ->description(__('announcements::announcements.settings.display_description'))
                ->schema([
                    Toggle::make('show_banner')
                        ->label(__('announcements::announcements.settings.show_banner'))
                        ->helperText(__('announcements::announcements.settings.show_banner_help'))
                        ->default(true),

                    Select::make('banner_position')
                        ->label(__('announcements::announcements.settings.banner_position'))
                        ->options([
                            'top' => __('announcements::announcements.settings.position_top'),
                            'bottom' => __('announcements::announcements.settings.position_bottom'),
                        ])
                        ->default('top'),

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
        ];
    }
}

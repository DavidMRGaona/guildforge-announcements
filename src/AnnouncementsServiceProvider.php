<?php

declare(strict_types=1);

namespace Modules\Announcements;

use App\Application\Modules\DTOs\NavigationItemDTO;
use App\Application\Modules\DTOs\PermissionDTO;
use App\Application\Modules\DTOs\SlotRegistrationDTO;
use App\Modules\ModuleServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
     * @return array<class-string<FilamentResource>>
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
                name: 'announcements.view_any',
                label: __('announcements::announcements.permissions.view_any'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['editor'],
            ),
            new PermissionDTO(
                name: 'announcements.view',
                label: __('announcements::announcements.permissions.view'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['editor'],
            ),
            new PermissionDTO(
                name: 'announcements.create',
                label: __('announcements::announcements.permissions.create'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['editor'],
            ),
            new PermissionDTO(
                name: 'announcements.update',
                label: __('announcements::announcements.permissions.update'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: ['editor'],
            ),
            new PermissionDTO(
                name: 'announcements.delete',
                label: __('announcements::announcements.permissions.delete'),
                group: __('announcements::announcements.navigation'),
                module: 'announcements',
                roles: [],
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
                permissions: ['announcements:announcements.view_any'],
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
        $settings = $this->bannerSettings();

        foreach (AnnouncementPosition::cases() as $position) {
            $props = ['slotPosition' => $position->value];

            // An empty array would reach the component as a JSON list, not an object
            if ($settings !== []) {
                $props['settings'] = $settings;
            }

            $slots[] = new SlotRegistrationDTO(
                slot: $position->value,
                component: 'components/AnnouncementBanner.vue',
                module: $this->moduleName(),
                order: 0,
                props: $props,
                dataKeys: ['announcements'],
            );
        }

        return $slots;
    }

    /**
     * Display settings saved in the panel, as the banner component expects them.
     * Keys that were never saved are left out so the banner uses its own defaults.
     *
     * @return array{show_banner?: bool, auto_rotate?: bool, rotate_interval?: int}
     */
    private function bannerSettings(): array
    {
        $saved = config('modules.settings.announcements');

        if (! is_array($saved)) {
            return [];
        }

        $settings = [];

        if (array_key_exists('show_banner', $saved)) {
            $settings['show_banner'] = (bool) $saved['show_banner'];
        }

        if (array_key_exists('auto_rotate', $saved)) {
            $settings['auto_rotate'] = (bool) $saved['auto_rotate'];
        }

        if (isset($saved['rotate_interval']) && is_numeric($saved['rotate_interval'])) {
            $settings['rotate_interval'] = (int) $saved['rotate_interval'];
        }

        return $settings;
    }

    /**
     * Get the Filament form schema for module settings.
     *
     * @return array<Component>
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

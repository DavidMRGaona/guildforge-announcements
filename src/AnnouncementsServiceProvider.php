<?php

declare(strict_types=1);

namespace Modules\Announcements;

use App\Application\Content\Contracts\EditorTextSourceInterface;
use App\Application\Mail\DTOs\MailCategoryDTO;
use App\Application\Media\Contracts\MediaUsageSourceInterface;
use App\Application\Modules\DTOs\NavigationItemDTO;
use App\Application\Modules\DTOs\PermissionDTO;
use App\Application\Modules\DTOs\SlotRegistrationDTO;
use App\Filament\Support\PanelNavigationGroup;
use App\Infrastructure\Media\Observers\RefreshMediaUsagesObserver;
use App\Modules\ModuleServiceProvider;
use Filament\Pages\Page;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Inertia\Inertia;
use Modules\Announcements\Application\Services\AnnouncementService;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Repositories\AnnouncementRepositoryInterface;
use Modules\Announcements\Filament\Media\AnnouncementContentMediaSource;
use Modules\Announcements\Filament\Media\AnnouncementPopupImageMediaSource;
use Modules\Announcements\Filament\Pages\AnnouncementsSettings;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Http\Support\SharedAnnouncements;
use Modules\Announcements\Infrastructure\Content\AnnouncementContentTextSource;
use Modules\Announcements\Infrastructure\Notifications\AnnouncementNoticeObserver;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnnouncementRepository;
use Modules\Announcements\Notifications\AnnouncementMailCategories;
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

        // Banners and windows of the page, read once per request
        $this->app->singleton(SharedAnnouncements::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->keepMediaUsagesUpToDate();

        // A new announcement is told in the bell once it is shown (announcements#63)
        AnnouncementModel::observe(AnnouncementNoticeObserver::class);

        // Banners and windows go to the page apart: the banners keep the prop they always had
        if (class_exists(Inertia::class)) {
            Inertia::share('announcements', fn (): array => $this->shared(AnnouncementFormat::Banner));
            Inertia::share('announcementPopups', fn (): array => $this->shared(AnnouncementFormat::Popup));
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shared(AnnouncementFormat $format): array
    {
        if (! $this->app->bound(AnnouncementServiceInterface::class)) {
            return [];
        }

        return $this->app->make(SharedAnnouncements::class)->of($format, $this->app->make('request'));
    }

    /**
     * Creating or deleting an announcement refreshes its usages in the media library. An update refreshes
     * them only when the title or the text changed (a usage carries the title); dates or colours do not.
     * The "updated" event is used, not "saved": wasRecentlyCreated stays true for the instance's whole life,
     * and a save with nothing dirty keeps the changes of the previous one.
     */
    private function keepMediaUsagesUpToDate(): void
    {
        $always = new RefreshMediaUsagesObserver(
            AnnouncementContentMediaSource::TYPE,
            static fn (Model $model): array => [(string) $model->getKey()],
        );
        $whenShownChanges = new RefreshMediaUsagesObserver(
            AnnouncementContentMediaSource::TYPE,
            static fn (Model $model): array => $model->wasChanged(['title', 'content']) ? [(string) $model->getKey()] : [],
        );

        AnnouncementModel::created($always->saved(...));
        AnnouncementModel::updated($whenShownChanges->saved(...));
        AnnouncementModel::deleted($always->deleted(...));

        $image = new RefreshMediaUsagesObserver(
            AnnouncementPopupImageMediaSource::TYPE,
            static fn (Model $model): array => [(string) $model->getKey()],
        );
        $whenImageChanges = new RefreshMediaUsagesObserver(
            AnnouncementPopupImageMediaSource::TYPE,
            static fn (Model $model): array => $model->wasChanged(['title', 'popup_image_public_id']) ? [(string) $model->getKey()] : [],
        );

        AnnouncementModel::created($image->saved(...));
        AnnouncementModel::updated($whenImageChanges->saved(...));
        AnnouncementModel::deleted($image->deleted(...));
    }

    /**
     * @return array<class-string<MediaUsageSourceInterface>>
     */
    public function registerMediaSources(): array
    {
        return [AnnouncementContentMediaSource::class, AnnouncementPopupImageMediaSource::class];
    }

    /**
     * The core's content:move-editor-images-to-cloudinary moves the local images of these texts.
     *
     * @return array<class-string<EditorTextSourceInterface>>
     */
    public function registerEditorTextSources(): array
    {
        return [AnnouncementContentTextSource::class];
    }

    /**
     * The bell notice of new announcements: site only, optional, on by default.
     *
     * @return list<MailCategoryDTO>
     */
    public function registerMailCategories(): array
    {
        return AnnouncementMailCategories::all();
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
            // Site-wide display of the banner, so it is not suggested for the editor
            new PermissionDTO(
                name: 'settings.manage',
                label: __('announcements::announcements.permissions.settings_manage'),
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
                group: PanelNavigationGroup::Communication->getLabel(),
                sort: 10,
                permissions: ['announcements:announcements.view_any'],
                module: 'announcements',
            ),
        ];
    }

    /**
     * Register slot components for the frontend layout: a banner component for each possible
     * position, and the window component once.
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

        // Windows float over the page: any slot of the layout would do, this one is drawn last
        $slots[] = new SlotRegistrationDTO(
            slot: AnnouncementPosition::AfterFooter->value,
            component: 'components/AnnouncementPopup.vue',
            module: $this->moduleName(),
            order: 10,
            dataKeys: ['announcementPopups'],
        );

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
     * The settings live on their own page of the «Configuración» cluster, not on the generic one.
     *
     * @return array<Component>
     */
    public function getSettingsSchema(): array
    {
        return [];
    }

    /**
     * @return class-string<Page>
     */
    public function getSettingsPage(): string
    {
        return AnnouncementsSettings::class;
    }

    /**
     * @return array<class-string<Page>>
     */
    public function registerFilamentPages(): array
    {
        return [AnnouncementsSettings::class];
    }
}

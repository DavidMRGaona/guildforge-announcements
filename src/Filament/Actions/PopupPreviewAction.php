<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Actions;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\View;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Filament\Support\PopupPreview;

/**
 * «Vista previa» (announcements#69): opens the window with what the form holds right now. It is
 * a picture of the window inside the panel: nothing is saved, counted or remembered, and the
 * real window (which belongs to the site) is not loaded.
 */
final class PopupPreviewAction
{
    public const string NAME = 'popupPreview';

    public static function make(): Action
    {
        return Action::make(self::NAME)
            ->label(__('announcements::announcements.preview.action'))
            ->icon(Heroicon::Eye)
            ->color('gray')
            ->visible(static fn (CreateRecord|EditRecord $livewire): bool => self::isPopup($livewire))
            ->modalHeading(__('announcements::announcements.preview.heading'))
            ->modalDescription(__('announcements::announcements.preview.description'))
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('announcements::announcements.preview.close'))
            ->modalContent(static function (CreateRecord|EditRecord $livewire): View {
                /** @var view-string $view */
                $view = 'announcements::filament.popup-preview';

                return view($view, (new PopupPreview)->fromState(self::formState($livewire)));
            });
    }

    /**
     * What the form holds now, valid or not: a preview never asks the form to validate.
     *
     * @return array<string, mixed>
     */
    private static function formState(CreateRecord|EditRecord $livewire): array
    {
        $state = $livewire->form->getRawState();

        return $state instanceof Arrayable ? $state->toArray() : $state;
    }

    private static function isPopup(CreateRecord|EditRecord $livewire): bool
    {
        $format = self::formState($livewire)['format'] ?? null;

        if ($format instanceof \BackedEnum) {
            $format = $format->value;
        }

        return $format === AnnouncementFormat::Popup->value;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources;

use App\Domain\Content\ValueObjects\EditorImageFolder;
use App\Filament\Actions\RowDetailsAction;
use App\Filament\Forms\Components\MediaLibraryPicker;
use App\Filament\Forms\RichEditor\EditorImages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\PanelNavigationGroup;
use BackedEnum;
use Closure;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\Enums\PopupDevice;
use Modules\Announcements\Domain\Enums\PopupFrequency;
use Modules\Announcements\Domain\Enums\PopupPages;
use Modules\Announcements\Domain\Enums\PopupShape;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use Modules\Announcements\Domain\ValueObjects\PopupPagePattern;
use Modules\Announcements\Domain\ValueObjects\PopupTriggers;
use Modules\Announcements\Filament\Media\AnnouncementPopupImageMediaSource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Modules\Announcements\Filament\Support\PopupBlockCatalog;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

class AnnouncementResource extends BaseResource
{
    protected static ?string $model = AnnouncementModel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return PanelNavigationGroup::Communication->getLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('announcements::announcements.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('announcements::announcements.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('announcements::announcements.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('announcements::announcements.sections.content'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('announcements::announcements.fields.title'))
                            ->placeholder(__('announcements::announcements.placeholders.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        EditorImages::configure(RichEditor::make('content'), EditorImageFolder::of('announcements'))
                            ->label(__('announcements::announcements.fields.content'))
                            ->placeholder(__('announcements::announcements.placeholders.content'))
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('announcements::announcements.sections.display'))
                    ->description(__('announcements::announcements.sections.display_description'))
                    ->schema([
                        ToggleButtons::make('format')
                            ->label(__('announcements::announcements.fields.format'))
                            ->helperText(__('announcements::announcements.fields.format_help'))
                            ->options(AnnouncementFormat::options())
                            ->in(array_keys(AnnouncementFormat::options()))
                            ->default(AnnouncementFormat::Banner->value)
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label(__('announcements::announcements.fields.is_active'))
                            ->default(true),

                        // A window can always be closed, so the choice is only a banner's
                        Toggle::make('is_dismissible')
                            ->label(__('announcements::announcements.fields.is_dismissible'))
                            ->helperText(__('announcements::announcements.fields.is_dismissible_help'))
                            ->default(true)
                            ->visible(fn (Get $get): bool => ! self::isPopup($get)),

                        Select::make('visibility')
                            ->label(__('announcements::announcements.fields.visibility'))
                            ->options(AnnouncementVisibility::options())
                            ->in(array_keys(AnnouncementVisibility::options()))
                            ->default(AnnouncementVisibility::Public->value)
                            ->helperText(__('announcements::announcements.fields.visibility_help'))
                            ->required(),

                        Select::make('position')
                            ->label(__('announcements::announcements.fields.position'))
                            ->options(AnnouncementPosition::options())
                            ->default(AnnouncementPosition::BeforeHeader->value)
                            ->visible(fn (Get $get): bool => ! self::isPopup($get))
                            ->required(),

                        TextInput::make('priority')
                            ->label(__('announcements::announcements.fields.priority'))
                            ->helperText(__('announcements::announcements.fields.priority_help'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(5)
                            ->required(),
                    ])
                    ->columns(2),

                ...self::popupSections(),

                Section::make(__('announcements::announcements.sections.styling'))
                    ->description(__('announcements::announcements.sections.styling_description'))
                    ->schema([
                        ColorPicker::make('background_color')
                            ->label(__('announcements::announcements.fields.background_color'))
                            ->helperText(__('announcements::announcements.fields.background_color_help')),

                        ColorPicker::make('text_color')
                            ->label(__('announcements::announcements.fields.text_color'))
                            ->helperText(__('announcements::announcements.fields.text_color_help')),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->visible(fn (Get $get): bool => ! self::isPopup($get)),

                Section::make(__('announcements::announcements.sections.scheduling'))
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('announcements::announcements.fields.starts_at'))
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->nullable(),

                        DateTimePicker::make('ends_at')
                            ->label(__('announcements::announcements.fields.ends_at'))
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->nullable()
                            ->after('starts_at'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    /**
     * The window settings (announcements#67), shown only for the popup format. Hidden fields are
     * neither checked nor saved, so a banner keeps the window settings it had.
     *
     * @return list<Section>
     */
    private static function popupSections(): array
    {
        $isPopup = fn (Get $get): bool => self::isPopup($get);

        return [
            Section::make(__('announcements::announcements.sections.popup'))
                ->description(__('announcements::announcements.sections.popup_description'))
                ->schema([
                    ToggleButtons::make('popup_shape')
                        ->label(__('announcements::announcements.popup.shape.label'))
                        ->helperText(__('announcements::announcements.popup.shape.help'))
                        ->options(PopupShape::options())
                        ->in(array_keys(PopupShape::options()))
                        ->default(PopupShape::Centered->value)
                        ->inline()
                        ->required()
                        ->columnSpanFull(),

                    MediaLibraryPicker::attachTo(FileUpload::make('popup_image_public_id')
                        ->label(__('announcements::announcements.popup.image'))
                        ->helperText(__('announcements::announcements.popup.image_help'))
                        ->image()
                        ->disk('images')
                        ->directory(fn (): string => AnnouncementPopupImageMediaSource::FOLDER.now()->format('Y/m'))
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => Str::uuid()->toString().'.'.$file->getClientOriginalExtension()
                        )
                        ->maxSize(2048)
                        ->nullable()
                        ->columnSpanFull()),

                    TextInput::make('popup_image_alt')
                        ->label(__('announcements::announcements.popup.image_alt'))
                        ->helperText(__('announcements::announcements.popup.image_alt_help'))
                        ->maxLength(AnnouncementPopup::IMAGE_ALT_MAX_LENGTH)
                        ->columnSpanFull(),

                    TextInput::make('popup_action_label')
                        ->label(__('announcements::announcements.popup.action_label'))
                        ->helperText(__('announcements::announcements.popup.action_label_help'))
                        ->maxLength(PopupAction::LABEL_MAX_LENGTH)
                        ->requiredWith('popup_action_url'),

                    TextInput::make('popup_action_url')
                        ->label(__('announcements::announcements.popup.action_url'))
                        ->helperText(__('announcements::announcements.popup.action_url_help'))
                        ->maxLength(PopupAction::URL_MAX_LENGTH)
                        ->requiredWith('popup_action_label')
                        ->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                            if (is_string($value) && $value !== '' && ! PopupAction::isValidUrl($value)) {
                                $fail(__('announcements::announcements.popup.action_url_invalid'));
                            }
                        }),
                ])
                ->columns(2)
                ->visible($isPopup),

            Section::make(__('announcements::announcements.sections.popup_block'))
                ->description(__('announcements::announcements.sections.popup_block_description'))
                ->schema([
                    Select::make('popup_block.key')
                        ->label(__('announcements::announcements.popup.block.label'))
                        ->helperText(__('announcements::announcements.popup.block.help'))
                        ->placeholder(__('announcements::announcements.popup.block.none'))
                        // Only what can be added now, plus the block the window has SAVED (never the one just
                        // submitted): it stays listed while its provider is down, so saving does not lose it
                        ->options(fn (?Model $record): array => resolve(PopupBlockCatalog::class)->options(self::storedBlockKey($record)))
                        ->native(false)
                        ->live()
                        ->nullable()
                        ->columnSpanFull(),

                    Group::make(fn (Get $get): array => resolve(PopupBlockCatalog::class)->settingsSchema(self::blockKey($get)))
                        ->statePath('popup_block.settings')
                        ->columns(2)
                        ->columnSpanFull(),

                    Checkbox::make('popup_block_hide_when_unavailable')
                        ->label(__('announcements::announcements.popup.block.hide_when_unavailable'))
                        ->helperText(__('announcements::announcements.popup.block.hide_when_unavailable_help'))
                        ->default(true)
                        ->visible(fn (Get $get): bool => self::blockKey($get) !== null)
                        ->columnSpanFull(),
                ])
                ->visible($isPopup),

            Section::make(__('announcements::announcements.sections.popup_triggers'))
                ->description(__('announcements::announcements.sections.popup_triggers_description'))
                ->schema([
                    TextInput::make('popup_trigger_delay')
                        ->label(__('announcements::announcements.popup.trigger_delay'))
                        ->helperText(__('announcements::announcements.popup.trigger_delay_help'))
                        ->integer()
                        ->minValue(PopupTriggers::DELAY_MIN)
                        ->maxValue(PopupTriggers::DELAY_MAX)
                        ->suffix(__('announcements::announcements.popup.seconds'))
                        ->nullable(),

                    TextInput::make('popup_trigger_scroll')
                        ->label(__('announcements::announcements.popup.trigger_scroll'))
                        ->helperText(__('announcements::announcements.popup.trigger_scroll_help'))
                        ->integer()
                        ->minValue(PopupTriggers::SCROLL_MIN)
                        ->maxValue(PopupTriggers::SCROLL_MAX)
                        ->suffix('%')
                        ->nullable(),

                    Toggle::make('popup_trigger_exit_intent')
                        ->label(__('announcements::announcements.popup.trigger_exit_intent'))
                        ->helperText(__('announcements::announcements.popup.trigger_exit_intent_help'))
                        ->default(false),

                    TextInput::make('popup_trigger_page_views')
                        ->label(__('announcements::announcements.popup.trigger_page_views'))
                        ->helperText(__('announcements::announcements.popup.trigger_page_views_help'))
                        ->integer()
                        ->minValue(PopupTriggers::PAGE_VIEWS_MIN)
                        ->maxValue(PopupTriggers::PAGE_VIEWS_MAX)
                        ->nullable(),

                    Select::make('popup_frequency')
                        ->label(__('announcements::announcements.popup.frequency.label'))
                        ->helperText(__('announcements::announcements.popup.frequency.help'))
                        ->options(PopupFrequency::options())
                        ->in(array_keys(PopupFrequency::options()))
                        ->default(PopupFrequency::Once->value)
                        ->native(false)
                        ->live()
                        ->required(),

                    TextInput::make('popup_frequency_days')
                        ->label(__('announcements::announcements.popup.frequency.days_label'))
                        ->helperText(__('announcements::announcements.popup.frequency.days_help'))
                        ->integer()
                        ->minValue(AnnouncementPopup::FREQUENCY_DAYS_MIN)
                        ->maxValue(AnnouncementPopup::FREQUENCY_DAYS_MAX)
                        ->suffix(__('announcements::announcements.popup.days'))
                        ->required()
                        ->visible(fn (Get $get): bool => self::isPopup($get) && self::enumValue($get('popup_frequency')) === PopupFrequency::Days->value),
                ])
                ->columns(2)
                ->visible($isPopup),

            Section::make(__('announcements::announcements.sections.popup_where'))
                ->schema([
                    Select::make('popup_pages')
                        ->label(__('announcements::announcements.popup.pages.label'))
                        ->helperText(__('announcements::announcements.popup.pages.help'))
                        ->options(PopupPages::options())
                        ->in(array_keys(PopupPages::options()))
                        ->default(PopupPages::All->value)
                        ->native(false)
                        ->live()
                        ->required(),

                    Select::make('popup_device')
                        ->label(__('announcements::announcements.popup.device.label'))
                        ->helperText(__('announcements::announcements.popup.device.help'))
                        ->options(PopupDevice::options())
                        ->in(array_keys(PopupDevice::options()))
                        ->default(PopupDevice::All->value)
                        ->native(false)
                        ->required(),

                    TagsInput::make('popup_paths')
                        ->label(__('announcements::announcements.popup.paths'))
                        ->helperText(__('announcements::announcements.popup.paths_help'))
                        ->placeholder('/eventos*')
                        ->required()
                        ->rules(['array', 'max:'.AnnouncementPopup::MAX_PATHS])
                        ->nestedRecursiveRules([
                            'string',
                            static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_string($value) || ! PopupPagePattern::isValid($value)) {
                                    $fail(__('announcements::announcements.popup.paths_invalid'));
                                }
                            },
                        ])
                        ->visible(fn (Get $get): bool => self::isPopup($get) && self::enumValue($get('popup_pages')) === PopupPages::Paths->value)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible($isPopup),
        ];
    }

    private static function storedBlockKey(?Model $record): ?string
    {
        $key = $record instanceof AnnouncementModel ? ($record->popup_block['key'] ?? null) : null;

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    /**
     * The block chosen in the form, if any (the state is read from the root of the form).
     */
    private static function blockKey(Get $get): ?string
    {
        $key = $get('popup_block.key');

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    private static function isPopup(Get $get): bool
    {
        return self::enumValue($get('format')) === AnnouncementFormat::Popup->value;
    }

    /**
     * A field holds the enum once filled from a record, and the plain value once edited.
     */
    private static function enumValue(mixed $state): ?string
    {
        if ($state instanceof BackedEnum) {
            return (string) $state->value;
        }

        return is_string($state) ? $state : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('announcements::announcements.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('format')
                    ->label(__('announcements::announcements.fields.format'))
                    ->badge()
                    ->formatStateUsing(fn (AnnouncementFormat $state): string => $state->label())
                    ->color(fn (AnnouncementFormat $state): string => $state === AnnouncementFormat::Popup ? 'primary' : 'gray')
                    ->visibleFrom('sm'),

                TextColumn::make('position')
                    ->label(__('announcements::announcements.fields.position'))
                    ->badge()
                    // A window has no position in the layout
                    ->formatStateUsing(fn (AnnouncementPosition $state, AnnouncementModel $record): string => $record->format === AnnouncementFormat::Popup ? '—' : $state->label())
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('visibility')
                    ->label(__('announcements::announcements.fields.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (AnnouncementVisibility $state): string => $state->label())
                    ->color(fn (AnnouncementVisibility $state): string => match ($state) {
                        AnnouncementVisibility::Public => 'success',
                        AnnouncementVisibility::Authenticated => 'warning',
                        AnnouncementVisibility::Members => 'info',
                        AnnouncementVisibility::Guests => 'gray',
                    })
                    ->visibleFrom('md'),

                TextColumn::make('priority')
                    ->label(__('announcements::announcements.fields.priority'))
                    ->sortable()
                    ->alignCenter()
                    ->visibleFrom('sm'),

                ColorColumn::make('background_color')
                    ->label(__('announcements::announcements.fields.background_color'))
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label(__('announcements::announcements.fields.is_active'))
                    ->boolean(),

                TextColumn::make('starts_at')
                    ->label(__('announcements::announcements.fields.starts_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('announcements::announcements.placeholders.no_date'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ends_at')
                    ->label(__('announcements::announcements.fields.ends_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('announcements::announcements.placeholders.no_date'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('announcements::announcements.fields.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('format')
                    ->label(__('announcements::announcements.filters.format'))
                    ->options(AnnouncementFormat::options()),

                SelectFilter::make('visibility')
                    ->label(__('announcements::announcements.filters.visibility'))
                    ->options(AnnouncementVisibility::options()),

                SelectFilter::make('position')
                    ->label(__('announcements::announcements.filters.position'))
                    ->options(AnnouncementPosition::options()),

                TernaryFilter::make('is_active')
                    ->label(__('announcements::announcements.filters.is_active'))
                    ->trueLabel(__('announcements::announcements.filters.active'))
                    ->falseLabel(__('announcements::announcements.filters.inactive')),
            ])
            // One menu: two labelled actions made the table overflow its container
            ->recordActions([
                ActionGroup::make([
                    RowDetailsAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}

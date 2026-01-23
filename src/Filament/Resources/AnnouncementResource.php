<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

class AnnouncementResource extends Resource
{
    protected static ?string $model = AnnouncementModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('announcements::announcements.navigation_group');
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('announcements::announcements.sections.content'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('announcements::announcements.fields.title'))
                            ->placeholder(__('announcements::announcements.placeholders.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('content')
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
                        Toggle::make('is_active')
                            ->label(__('announcements::announcements.fields.is_active'))
                            ->default(true),

                        Toggle::make('is_dismissible')
                            ->label(__('announcements::announcements.fields.is_dismissible'))
                            ->helperText(__('announcements::announcements.fields.is_dismissible_help'))
                            ->default(true),

                        Select::make('visibility')
                            ->label(__('announcements::announcements.fields.visibility'))
                            ->options([
                                AnnouncementVisibility::Public->value => __('announcements::announcements.visibility.public'),
                                AnnouncementVisibility::Authenticated->value => __('announcements::announcements.visibility.authenticated'),
                                AnnouncementVisibility::Members->value => __('announcements::announcements.visibility.members'),
                            ])
                            ->default(AnnouncementVisibility::Public->value)
                            ->required(),

                        Select::make('position')
                            ->label(__('announcements::announcements.fields.position'))
                            ->options(AnnouncementPosition::options())
                            ->default(AnnouncementPosition::BeforeHeader->value)
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
                    ->collapsed(),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('announcements::announcements.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('position')
                    ->label(__('announcements::announcements.fields.position'))
                    ->badge()
                    ->formatStateUsing(fn (AnnouncementPosition $state): string => $state->label())
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('visibility')
                    ->label(__('announcements::announcements.fields.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (AnnouncementVisibility $state): string => match ($state) {
                        AnnouncementVisibility::Public => __('announcements::announcements.visibility.public'),
                        AnnouncementVisibility::Authenticated => __('announcements::announcements.visibility.authenticated'),
                        AnnouncementVisibility::Members => __('announcements::announcements.visibility.members'),
                    })
                    ->color(fn (AnnouncementVisibility $state): string => match ($state) {
                        AnnouncementVisibility::Public => 'success',
                        AnnouncementVisibility::Authenticated => 'warning',
                        AnnouncementVisibility::Members => 'info',
                    }),

                TextColumn::make('priority')
                    ->label(__('announcements::announcements.fields.priority'))
                    ->sortable()
                    ->alignCenter(),

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
                SelectFilter::make('visibility')
                    ->label(__('announcements::announcements.filters.visibility'))
                    ->options([
                        AnnouncementVisibility::Public->value => __('announcements::announcements.visibility.public'),
                        AnnouncementVisibility::Authenticated->value => __('announcements::announcements.visibility.authenticated'),
                        AnnouncementVisibility::Members->value => __('announcements::announcements.visibility.members'),
                    ]),

                SelectFilter::make('position')
                    ->label(__('announcements::announcements.filters.position'))
                    ->options(AnnouncementPosition::options()),

                TernaryFilter::make('is_active')
                    ->label(__('announcements::announcements.filters.is_active'))
                    ->trueLabel(__('announcements::announcements.filters.active'))
                    ->falseLabel(__('announcements::announcements.filters.inactive')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}

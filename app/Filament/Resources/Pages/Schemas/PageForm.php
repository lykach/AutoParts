<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Page;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основне')
                ->schema([
                    Grid::make(1)->schema([
                        TextInput::make('name')
                            ->label('Внутрішня назва')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                $currentSlug = (string) ($get('slug') ?? '');

                                if (blank($currentSlug) && filled($state)) {
                                    $set(
                                        'slug',
                                        Page::generateUniqueSlug(
                                            (string) $state,
                                            $record?->getKey()
                                        )
                                    );
                                }
                            })
                            ->columnSpan(5),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->disabled(fn ($record) => (bool) $record?->is_system)
                            ->dehydrated(fn ($record) => ! $record?->is_system)
                            ->helperText('Можна не заповнювати — згенерується автоматично. Для системних сторінок slug заблокований.')
                            ->dehydrateStateUsing(function ($state, callable $get, $record) {
                                $state = trim((string) $state);

                                if (filled($state)) {
                                    return Page::generateUniqueSlug(Str::slug($state), $record?->getKey(), true);
                                }

                                return Page::generateUniqueSlug(
                                    (string) $get('name'),
                                    $record?->getKey()
                                );
                            })
                            ->unique(ignoreRecord: true)
                            ->columnSpan(4),

                        Select::make('template')
                            ->label('Шаблон')
                            ->options(PageTemplate::options())
                            ->default(PageTemplate::Default->value)
                            ->required()
                            ->native(false)
                            ->columnSpan(5),

                        Select::make('status')
                            ->label('Статус')
                            ->options(PageStatus::options())
                            ->default(PageStatus::Draft->value)
                            ->required()
                            ->native(false)
                            ->columnSpan(5),

                        TextInput::make('sort')
                            ->label('Сортування')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        DateTimePicker::make('published_at')
                            ->label('Дата публікації')
                            ->seconds(false)
                            ->columnSpan(5),

                        Toggle::make('is_system')
                            ->label('Системна сторінка')
                            ->default(false)
                            ->inline(false)
                            ->disabled(fn ($record) => (bool) $record?->is_system)
                            ->dehydrated(fn ($record) => ! $record?->is_system)
                            ->columnSpan(3),

                        Toggle::make('show_in_sitemap')
                            ->label('Показувати в sitemap')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(3),

                        Placeholder::make('page_url_info')
                            ->label('URL')
                            ->content(fn ($record, callable $get) => '/' . ltrim((string) ($record?->slug ?? $get('slug') ?? ''), '/'))
                            ->columnSpan(5),
                    ]),
                ]),

            Tabs::make('Контент')
                ->tabs([
                    Tab::make('Українська')->schema([
                        TextInput::make('title_uk')
                            ->label('Заголовок (UK)')
                            ->maxLength(255),

                        Textarea::make('excerpt_uk')
                            ->label('Короткий опис (UK)')
                            ->rows(3),

                        RichEditor::make('content_uk')
                            ->label('Контент (UK)')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('pages')
                            ->columnSpanFull(),
                    ]),

                    Tab::make('English')->schema([
                        TextInput::make('title_en')
                            ->label('Заголовок (EN)')
                            ->maxLength(255),

                        Textarea::make('excerpt_en')
                            ->label('Короткий опис (EN)')
                            ->rows(3),

                        RichEditor::make('content_en')
                            ->label('Контент (EN)')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('pages')
                            ->columnSpanFull(),
                    ]),

                    Tab::make('Русский')->schema([
                        TextInput::make('title_ru')
                            ->label('Заголовок (RU)')
                            ->maxLength(255),

                        Textarea::make('excerpt_ru')
                            ->label('Короткий опис (RU)')
                            ->rows(3),

                        RichEditor::make('content_ru')
                            ->label('Контент (RU)')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('pages')
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),

            Section::make('SEO')
                ->schema([
                    Tabs::make('SEO tabs')
                        ->tabs([
                            Tab::make('UK')->schema([
                                TextInput::make('seo_title_uk')
                                    ->label('SEO title (UK)')
                                    ->maxLength(255),

                                Textarea::make('seo_description_uk')
                                    ->label('SEO description (UK)')
                                    ->rows(3),

                                TagsInput::make('seo_keywords_uk')
                                    ->label('SEO keywords (UK)'),
                            ]),
                            Tab::make('EN')->schema([
                                TextInput::make('seo_title_en')
                                    ->label('SEO title (EN)')
                                    ->maxLength(255),

                                Textarea::make('seo_description_en')
                                    ->label('SEO description (EN)')
                                    ->rows(3),

                                TagsInput::make('seo_keywords_en')
                                    ->label('SEO keywords (EN)'),
                            ]),
                            Tab::make('RU')->schema([
                                TextInput::make('seo_title_ru')
                                    ->label('SEO title (RU)')
                                    ->maxLength(255),

                                Textarea::make('seo_description_ru')
                                    ->label('SEO description (RU)')
                                    ->rows(3),

                                TagsInput::make('seo_keywords_ru')
                                    ->label('SEO keywords (RU)'),
                            ]),
                        ]),
                ]),

            Section::make('Медіа')
                ->schema([
                    FileUpload::make('cover_image')
                        ->label('Обкладинка')
                        ->disk('public')
                        ->directory('pages/covers')
                        ->image()
                        ->imageEditor(),
                ]),
        ]);
    }
}
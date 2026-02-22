<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function schema(): array
    {
        return [
            Section::make('Основна інформація')
                ->schema([
                    Select::make('parent_id')
                        ->label('Батьківська категорія')
                        ->placeholder('Коренева категорія (без батька)')
                        ->options(function ($record) {
                            $query = Category::query();

                            if ($record instanceof Category && $record->exists) {
                                $query->where('id', '!=', $record->id);

                                $descendants = $record->descendantIds();
                                if (! empty($descendants)) {
                                    $query->whereNotIn('id', $descendants);
                                }
                            }

                            return $query->orderBy('name_uk')->pluck('name_uk', 'id')->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->getSearchResultsUsing(function (string $search) {
                            return Category::query()
                                ->where('name_uk', 'like', "%{$search}%")
                                ->orderBy('name_uk')
                                ->limit(50)
                                ->pluck('name_uk', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            if (! $value) return null;
                            $category = Category::find($value);
                            return $category ? $category->name_uk : null;
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) return;

                            $parent = Category::find($state);

                            if ($parent && ! $parent->canHaveChildren()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Помилка')
                                    ->body("Категорія '{$parent->name_uk}' має товари і не може мати підкатегорій!")
                                    ->send();

                                $set('parent_id', null);
                            }
                        }),

                    TextInput::make('name_uk')
                        ->label('Назва (Українська)')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if ($get('auto_slug') !== false) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    Checkbox::make('auto_slug')
                        ->label('Автоматичний slug')
                        ->default(true)
                        ->live()
                        ->dehydrated(false),

                    TextInput::make('slug')
                        ->label('URL slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn ($get) => $get('auto_slug') !== false)
                        ->dehydrated(),

                    TextInput::make('tecdoc_id')
                        ->label('TecDoc ID')
                        ->numeric()
                        ->unique(ignoreRecord: true),

                    FileUpload::make('image')
                        ->label('Зображення')
                        ->image()
                        ->directory('categories')
                        ->disk('public')
                        ->visibility('public')
                        ->imageEditor()
                        ->maxSize(2048),

                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),

                    Toggle::make('is_container')
                        ->label('Контейнерна категорія (вітрина / дзеркала)')
                        ->helperText('У контейнерну категорію НЕ можна додавати товари. Використовується як “вітрина” для CategoryMirrors.')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) {
                                return;
                            }

                            // Якщо намагаються зробити контейнером категорію, яка має товари — відкат
                            $id = $get('id') ?? null;
                            if ($id) {
                                $cat = Category::find((int) $id);
                                if ($cat && $cat->hasProducts()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Неможливо')
                                        ->body("Категорія '{$cat->name_uk}' має товари — спочатку перенесіть товари в кінцеві категорії.")
                                        ->send();

                                    $set('is_container', false);
                                }
                            }
                        }),
                ])
                ->columns(2),

            Tabs::make('Мови')
                ->tabs([
                    Tabs\Tab::make('Українська')
                        ->schema([
                            Textarea::make('description_uk')
                                ->label('Опис')
                                ->rows(3)
                                ->maxLength(1000),

                            TextInput::make('meta_title_uk')
                                ->label('Meta Title')
                                ->maxLength(255),

                            Textarea::make('meta_description_uk')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(500),
                        ]),

                    Tabs\Tab::make('English')
                        ->schema([
                            TextInput::make('name_en')
                                ->label('Name')
                                ->maxLength(255),

                            Textarea::make('description_en')
                                ->label('Description')
                                ->rows(3)
                                ->maxLength(1000),

                            TextInput::make('meta_title_en')
                                ->label('Meta Title')
                                ->maxLength(255),

                            Textarea::make('meta_description_en')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(500),
                        ]),

                    Tabs\Tab::make('Русский')
                        ->schema([
                            TextInput::make('name_ru')
                                ->label('Название')
                                ->maxLength(255),

                            Textarea::make('description_ru')
                                ->label('Описание')
                                ->rows(3)
                                ->maxLength(1000),

                            TextInput::make('meta_title_ru')
                                ->label('Meta Title')
                                ->maxLength(255),

                            Textarea::make('meta_description_ru')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(500),
                        ]),
                ]),
        ];
    }
}
<?php

namespace App\Filament\Resources\CategoryMirrors\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class CategoryMirrorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('parent_category_id')
                    ->label('Під якою категорією показати (контейнер)')
                    ->helperText('Тільки контейнерні категорії (вітрини). У контейнер не додаються товари.')
                    ->relationship(
                        name: 'parentCategory',
                        titleAttribute: 'name_uk',
                        modifyQueryUsing: fn (Builder $q) => $q
                            ->where('is_container', 1)
                            ->where('is_active', 1)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Select::make('source_category_id')
                    ->label('Яку категорію дублювати (кінцева)')
                    ->helperText('Виберіть кінцеву категорію (leaf), в яку додаються товари.')
                    ->relationship(
                        name: 'sourceCategory',
                        titleAttribute: 'name_uk',
                        modifyQueryUsing: fn (Builder $q) => $q
                            ->where('is_leaf', 1)
                            ->where('is_container', 0)
                            ->where('is_active', 1)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state && $state === $get('parent_category_id')) {
                            $set('source_category_id', null);

                            Notification::make()
                                ->danger()
                                ->title('Помилка')
                                ->body('Неможливо дублювати категорію саму під себе!')
                                ->send();
                        }
                    })
                    ->columnSpanFull(),

                Checkbox::make('use_custom_names')
                    ->label('Використовувати власні назви')
                    ->helperText('Якщо включено — можна задати інші назви для дублікату')
                    ->live()
                    ->dehydrated(false)
                    ->columnSpanFull(),

                TextInput::make('custom_name_uk')
                    ->label('Власна назва (Українська)')
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('use_custom_names') === true)
                    ->helperText('Залиште порожнім щоб використати оригінальну назву')
                    ->columnSpanFull(),

                TextInput::make('custom_name_en')
                    ->label('Власна назва (English)')
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('use_custom_names') === true)
                    ->columnSpanFull(),

                TextInput::make('custom_name_ru')
                    ->label('Власна назва (Русский)')
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('use_custom_names') === true)
                    ->columnSpanFull(),

                TextInput::make('custom_slug')
                    ->label('Власний slug')
                    ->helperText('Залиште порожнім щоб використати оригінальний slug')
                    ->maxLength(255)
                    ->columnSpan(1),

                Toggle::make('is_active')
                    ->label('Активний')
                    ->default(true)
                    ->helperText('Неактивні дублікати не відображаються на сайті')
                    ->columnSpan(1),
            ]);
    }
}
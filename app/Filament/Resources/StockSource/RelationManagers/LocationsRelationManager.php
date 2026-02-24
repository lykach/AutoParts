<?php

namespace App\Filament\Resources\StockSource\RelationManagers;

use App\Models\StockSource;
use App\Models\StockSourceLocation;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';
    protected static ?string $title = 'Склади постачальника';
    protected static bool $isLazy = false;
    protected static ?string $model = StockSourceLocation::class;

    public function getRelationship(): HasMany
    {
        /** @var Model|null $owner */
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof StockSource) {
            return (new StockSource())->locations();
        }

        return $owner->locations();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Toggle::make('is_active')
                ->label('Активний')
                ->default(true),

            Forms\Components\TextInput::make('sort_order')
                ->label('Сортування')
                ->numeric()
                ->placeholder('Авто')
                ->helperText('Якщо пусто — авто (для нового) або не змінюється (при редагуванні).')
                // "" -> null (далі модель сама підставить)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : null),

            Forms\Components\TextInput::make('name')
                ->label('Назва складу')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if (!filled($get('code')) && filled($state)) {
                        $set('code', Str::upper(Str::slug((string) $state, '_')));
                    }
                })
                ->columnSpanFull(),

            Forms\Components\TextInput::make('code')
                ->label('Код (унікальний в постачальнику)')
                ->helperText('Напр: MAIN, KYIV, LVIV_1. Якщо не вкажеш — згенерується з назви.')
                ->maxLength(64)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set) {
                    if (filled($state)) {
                        $set('code', Str::upper(trim((string) $state)));
                    }
                }),

            Forms\Components\TextInput::make('country')->label('Країна')->maxLength(100),
            Forms\Components\TextInput::make('region')->label('Область')->maxLength(100),
            Forms\Components\TextInput::make('city')->label('Місто')->maxLength(100),
            Forms\Components\TextInput::make('postal_code')->label('Індекс')->maxLength(20),

            Forms\Components\TextInput::make('address_line1')->label('Адреса 1')->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('address_line2')->label('Адреса 2')->maxLength(255)->columnSpanFull(),

            Forms\Components\TextInput::make('lat')->label('Lat')->numeric(),
            Forms\Components\TextInput::make('lng')->label('Lng')->numeric(),

            Forms\Components\KeyValue::make('settings')->label('settings')->columnSpanFull(),
            Forms\Components\Textarea::make('note')->label('Примітка')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            // ✅ щоб показувати items_count без N+1
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))

            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')

            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Код скопійовано')
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Місто')
                    ->toggleable(),

                // ✅ як у категоріях: показуємо скільки товарів у складі
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Товарів')
                    ->counts('items')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                CreateAction::make()->label('Додати склад'),
            ])

            ->actions([
                EditAction::make(),

                DeleteAction::make()
                    ->disabled(fn (StockSourceLocation $record) => $record->items()->exists())
                    ->tooltip(fn (StockSourceLocation $record) => $record->items()->exists()
                        ? 'Неможливо видалити: у складі є позиції (stock_items).'
                        : null
                    ),
            ])

            // ✅ чекбокси + "Відкрити дії" як у CategoriesTable
            ->bulkActions([
                BulkActionGroup::make([

                    BulkAction::make('activateSelected')
                        ->label('Зробити активними')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation(false)
                        ->action(function (Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if (! $record->is_active) {
                                    $record->update(['is_active' => true]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Активовано: {$count}")
                                ->send();
                        }),

                    BulkAction::make('deactivateSelected')
                        ->label('Зробити неактивними')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->is_active) {
                                    $record->update(['is_active' => false]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Деактивовано: {$count}")
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                /** @var StockSourceLocation $record */
                                if ($record->items()->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Склад має товари!')
                                        ->body("Неможливо видалити '{$record->name}' — у ньому є позиції (stock_items).")
                                        ->send();

                                    $action->cancel();
                                    return;
                                }
                            }
                        }),

                ])->label('Відкрити дії'),
            ]);
    }
}
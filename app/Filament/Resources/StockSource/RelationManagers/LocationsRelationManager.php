<?php

namespace App\Filament\Resources\StockSource\RelationManagers;

use App\Models\StockSource;
use App\Models\StockSourceLocation;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
                ->helperText('Якщо пусто — не змінюється (або авто для нового).')
                // ✅ ключ: "" -> null (далі модель сама підставить правильне)
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
            ]);
    }
}
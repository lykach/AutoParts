<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductDetail;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    protected static ?string $title = 'Властивості (для картки товару)';

    private function nextSort(): int
    {
        $productId = (int) $this->getOwnerRecord()->id;

        $max = ProductDetail::query()
            ->where('product_id', $productId)
            ->max('sort');

        return ((int) $max) + 1;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Назва (3 мови)')
                ->schema([
                    TextInput::make('name_uk')
                        ->label('Назва (UK)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('name_en')
                        ->label('Name (EN)')
                        ->maxLength(255),

                    TextInput::make('name_ru')
                        ->label('Название (RU)')
                        ->maxLength(255),
                ])
                ->columns(1),

            Section::make('Значення (3 мови)')
                ->schema([
                    Textarea::make('value_uk')
                        ->label('Значення (UK)')
                        ->rows(1)
                        ->required()
                        ->maxLength(1000),

                    Textarea::make('value_en')
                        ->label('Value (EN)')
                        ->rows(1)
                        ->maxLength(1000),

                    Textarea::make('value_ru')
                        ->label('Значение (RU)')
                        ->rows(1)
                        ->maxLength(1000),
                ])
                ->columns(1),

            Section::make('Сортування / джерело')
                ->schema([
                    TextInput::make('sort')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Якщо 0 — буде виставлено автоматично.'),

                    TextInput::make('source')
                        ->label('Джерело')
                        ->maxLength(50)
                        ->placeholder('manual / import / tecdoc ...'),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort') // ✅ drag&drop
            ->columns([
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name_uk')
                    ->label('Назва (UK)')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('value_uk')
                    ->label('Значення (UK)')
                    ->searchable()
                    ->wrap()
                    ->limit(80),

                TextColumn::make('source')
                    ->label('Джерело')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->defaultSort('sort', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->label('Додати властивість')
                    ->mutateFormDataUsing(function (array $data) {
                        // ✅ автопорядок одразу в RM
                        if (empty($data['sort']) || (int) $data['sort'] === 0) {
                            $data['sort'] = $this->nextSort();
                        }
                        // ✅ завжди прив’язка до товару
                        $data['product_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        if (array_key_exists('sort', $data) && (int) $data['sort'] === 0) {
                            $data['sort'] = $this->nextSort();
                        }
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped();
    }
}
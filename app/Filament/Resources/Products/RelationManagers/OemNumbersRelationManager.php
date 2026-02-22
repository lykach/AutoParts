<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductOemNumber;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OemNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'oemNumbers';

    protected static ?string $title = 'OE номери';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('oem_number_raw')
                ->label('OE номер (як є)')
                ->required()
                ->maxLength(128)
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    $raw = mb_strtoupper(trim((string) $state), 'UTF-8');
                    $set('oem_number_raw', $raw);
                    $set('oem_number_norm', Product::normalizeArticle($raw));
                })
                ->helperText('Можна з пробілами/тире — очищений (norm) порахується автоматично. Зберігаємо у верхньому регістрі.'),

            TextInput::make('oem_number_norm')
                ->label('OE номер (очищений)')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn ($state, $record) => $record?->oem_number_norm),

            Select::make('manufacturer_id')
                ->label('Виробник (бренд OE)')
                ->native(false)
                ->searchable()
                ->preload()
                ->options(fn () => Manufacturer::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all()
                )
                ->helperText('Опційно. Якщо знаєш бренд OE (VAG, MAN, BMW) — вкажи.'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('manufacturer'))
            ->columns([
                Tables\Columns\TextColumn::make('oem_number_raw')
                    ->label('OE (raw)')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Скопійовано')
                    ->wrap(),

                Tables\Columns\TextColumn::make('oem_number_norm')
                    ->label('OE (norm)')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Скопійовано')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('manufacturer.name')
                    ->label('Виробник')
                    ->placeholder('—')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->label('Додати OE'),

                Action::make('bulkAdd')
                    ->label('Додати списком')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->form([
                        Textarea::make('list')
                            ->label('OE номери (кожен з нового рядка)')
                            ->rows(10)
                            ->required()
                            ->helperText("Приклад:\n626 3150 09\n510 0325 10\n06A109088"),
                        Select::make('manufacturer_id')
                            ->label('Виробник (для всіх)')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->options(fn () => Manufacturer::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->helperText('Опційно. Якщо це OE одного бренду — можна вибрати один раз.'),
                    ])
                    ->action(function (array $data) {
                        $text = (string) ($data['list'] ?? '');
                        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];

                        $product = $this->getOwnerRecord();
                        $mId = $data['manufacturer_id'] ?? null;

                        foreach ($lines as $line) {
                            $raw = trim((string) $line);
                            if ($raw === '') {
                                continue;
                            }

                            // ✅ завжди uppercase як у одиночному вводі
                            $raw = mb_strtoupper($raw, 'UTF-8');

                            $norm = Product::normalizeArticle($raw);
                            if ($norm === '') {
                                continue;
                            }

                            $exists = ProductOemNumber::query()
                                ->where('product_id', $product->id)
                                ->where('oem_number_norm', $norm)
                                ->when($mId, fn ($q) => $q->where('manufacturer_id', $mId))
                                ->exists();

                            if ($exists) {
                                continue;
                            }

                            ProductOemNumber::create([
                                'product_id' => $product->id,
                                'oem_number_raw' => $raw,
                                'oem_number_norm' => $norm,
                                'manufacturer_id' => $mId,
                            ]);
                        }
                    })
                    ->successNotificationTitle('OE номери додано'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}

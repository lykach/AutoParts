<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductBarcode;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductBarcodesRelationManager extends RelationManager
{
    protected static string $relationship = 'barcodes';

    protected static ?string $title = 'Штрих-коди';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->barcodes()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'info';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('barcode')
                ->label('Штрих-код')
                ->required()
                ->maxLength(64)
                ->helperText('Можна вставляти з пробілами/дефісами — збережеться тільки цифрами.'),

            Select::make('type')
                ->label('Тип')
                ->native(false)
                ->options([
                    'EAN13' => 'EAN-13',
                    'EAN8'  => 'EAN-8',
                    'UPCA'  => 'UPC-A',
                    'GTIN14'=> 'GTIN-14',
                    'OTHER' => 'Інше',
                ])
                ->helperText('Якщо не вибереш — визначиться автоматично по довжині.'),

            Toggle::make('is_primary')
                ->label('Основний')
                ->default(false)
                ->helperText('Якщо увімкнути — інші “основні” у цього товару будуть зняті автоматично.'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_primary', 'desc')
            ->columns([
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('⭐')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('barcode')
                    ->label('Штрих-код')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Штрих-код скопійовано')
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->label('Додати штрих-код'),

                // ✅ Масове додавання (списком)
                Action::make('bulkAdd')
                    ->label('Додати списком')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->form([
                        Textarea::make('list')
                            ->label("Штрих-коди (кожен з нового рядка)")
                            ->rows(10)
                            ->required(),

                        Toggle::make('make_first_primary')
                            ->label('Перший зробити основним')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        $product = $this->getOwnerRecord();

                        $lines = preg_split("/\r\n|\n|\r/", (string) ($data['list'] ?? '')) ?: [];
                        $makeFirstPrimary = (bool) ($data['make_first_primary'] ?? true);

                        $i = 0;

                        foreach ($lines as $line) {
                            $raw = trim((string) $line);
                            if ($raw === '') continue;

                            $digits = ProductBarcode::normalizeBarcode($raw);
                            if ($digits === '') continue;

                            // у тебе вже є unique(barcode) глобальний — тож дублікати пропустимо
                            $exists = ProductBarcode::query()->where('barcode', $digits)->exists();
                            if ($exists) continue;

                            $i++;

                            ProductBarcode::create([
                                'product_id' => $product->id,
                                'barcode' => $digits,
                                'type' => ProductBarcode::detectType($digits) ?? 'OTHER',
                                'is_primary' => $makeFirstPrimary && $i === 1,
                            ]);
                        }
                    })
                    ->successNotificationTitle('Штрих-коди додано'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

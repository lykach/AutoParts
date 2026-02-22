<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use App\Models\ProductComponent;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $title = 'Комплектність';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) ($ownerRecord->components()->count());
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'info';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('position')
                ->label('Позиція')
                ->numeric()
                ->helperText('Якщо не вкажеш — буде поставлено автоматично в кінець.')
                ->placeholder('Авто'),

            TextInput::make('qty')
                ->label('К-сть')
                ->numeric()
                ->default(1)
                ->required(),

            TextInput::make('title')
                ->label('Назва позиції')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('article_raw')
                ->label('Артикул (як є)')
                ->maxLength(128)
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    $raw = mb_strtoupper(trim((string) $state), 'UTF-8');
                    $set('article_raw', $raw);
                    $set('article_norm', Product::normalizeArticle($raw));
                })
                ->helperText('Може бути з пробілами/тире/кирилицею — norm порахується автоматично. Зберігаємо у верхньому регістрі.'),

            TextInput::make('article_norm')
                ->label('Артикул (очищений)')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn ($state, $record) => $record?->article_norm),

            Textarea::make('note')
                ->label('Примітка')
                ->rows(2)
                ->maxLength(255)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position', 'asc')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Назва')
                    ->searchable()
                    ->wrap()
                    ->limit(120),

                Tables\Columns\TextColumn::make('article_raw')
                    ->label('Артикул')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Артикул скопійовано')
                    ->placeholder('—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('К-сть')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Примітка')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->label('Додати позицію'),

                Action::make('bulkAdd')
                    ->label('Додати списком')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->form([
                        Textarea::make('list')
                            ->label("Список позицій (1 рядок = 1 позиція)")
                            ->rows(10)
                            ->required()
                            ->helperText("Формат рядка:\nНазва | Артикул | К-сть\n\nПриклад:\nДиск зчеплення | 320 0178 10 | 1\nПідшипник вижимний | 500 0254 10 | 1"),
                    ])
                    ->action(function (array $data) {
                        $text = (string) ($data['list'] ?? '');
                        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];

                        $product = $this->getOwnerRecord();

                        $maxPos = (int) ($product->components()->max('position') ?? 0);

                        foreach ($lines as $line) {
                            $line = trim((string) $line);
                            if ($line === '') continue;

                            $parts = array_map('trim', explode('|', $line));
                            $title = $parts[0] ?? '';
                            $article = $parts[1] ?? null;
                            $qty = $parts[2] ?? 1;

                            if ($title === '') continue;

                            $maxPos++;

                            $article = $article !== null ? trim((string) $article) : null;
                            $article = $article ? mb_strtoupper($article, 'UTF-8') : null;

                            ProductComponent::create([
                                'product_id' => $product->id,
                                'position' => $maxPos,
                                'title' => $title,
                                'article_raw' => $article ?: null,
                                'article_norm' => $article ? Product::normalizeArticle($article) : null,
                                'qty' => is_numeric($qty) ? (float) $qty : 1,
                            ]);
                        }
                    })
                    ->successNotificationTitle('Позиції комплекту додано'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

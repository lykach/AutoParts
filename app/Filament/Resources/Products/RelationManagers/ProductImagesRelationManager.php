<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductImage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Зображення';

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Toggle::make('is_active')
                ->label('Активно')
                ->default(true),

            Toggle::make('is_primary')
                ->label('Основне')
                ->default(false),

            Toggle::make('convert_to_webp')
                ->label('WebP')
                ->default(true)
                ->disabled(fn () => ! ProductImage::webpSupported()),

            TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->placeholder('Авто'),

            Radio::make('source')
                ->label('Джерело')
                ->options([
                    'upload' => 'Файл',
                    'url' => 'URL',
                ])
                ->default('upload')
                ->live(),

            FileUpload::make('image_path')
                ->label('Зображення')
                ->image()
                ->disk('public')
                ->directory('products')
                ->visible(fn ($get) => $get('source') === 'upload')
                ->required(fn ($get) => $get('source') === 'upload'),

            TextInput::make('external_url')
                ->label('URL')
                ->url()
                ->visible(fn ($get) => $get('source') === 'url')
                ->required(fn ($get) => $get('source') === 'url'),

            TextInput::make('title')
                ->label('Title')
                ->columnSpanFull(),

            TextInput::make('alt')
                ->label('Alt')
                ->columnSpanFull(),

        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('⭐')
                    ->boolean()
                    ->alignCenter(),

                // 🔥 ГОЛОВНИЙ ФІКС — використовуємо accessor url
                Tables\Columns\ImageColumn::make('url')
                    ->label('Фото')
                    ->height(60)
                    ->width(60)
                    ->square()
                    ->extraImgAttributes([
                        'loading' => 'lazy',
                    ]),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->label('Джерело'),

                Tables\Columns\TextColumn::make('alt')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),

                Action::make('normalize')
                    ->label('Вирівняти порядок')
                    ->icon('heroicon-o-arrows-up-down')
                    ->action(fn () =>
                        ProductImage::stabilize($this->getOwnerRecord()->id)
                    )
                    ->successNotificationTitle('Порядок вирівняно'),
            ])
            ->actions([
                Action::make('makePrimary')
                    ->label('Зробити основним')
                    ->visible(fn ($record) => ! $record->is_primary)
                    ->action(function ($record) {
                        ProductImage::where('product_id', $record->product_id)
                            ->update(['is_primary' => false]);

                        $record->update(['is_primary' => true]);
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

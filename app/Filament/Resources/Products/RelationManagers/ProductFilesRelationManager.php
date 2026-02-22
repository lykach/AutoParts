<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductFile;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';
    protected static ?string $title = 'Файли';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('is_active')->label('Активний')->default(true),

            Toggle::make('is_primary')
                ->label('Основний')
                ->default(false),

            TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->placeholder('Авто'),

            Select::make('type')
                ->label('Тип')
                ->native(false)
                ->placeholder('Авто')
                ->options([
                    'manual' => 'Інструкція',
                    'certificate' => 'Сертифікат',
                    'scheme' => 'Схема',
                    'photo' => 'Фото',
                    'other' => 'Інше',
                ]),

            TextInput::make('title')
                ->label('Назва')
                ->maxLength(255)
                ->columnSpanFull(),

            Radio::make('source')
                ->label('Джерело')
                ->options([
                    'upload' => 'Файл',
                    'url' => 'URL',
                ])
                ->default('upload')
                ->live(),

            FileUpload::make('file_path')
                ->disk('public')
                ->directory('product-files')
                ->visible(fn ($get) => $get('source') === 'upload')
                ->required(fn ($get) => $get('source') === 'upload'),

            TextInput::make('external_url')
                ->url()
                ->visible(fn ($get) => $get('source') === 'url')
                ->required(fn ($get) => $get('source') === 'url'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\IconColumn::make('is_active')->boolean(),

                Tables\Columns\IconColumn::make('is_primary')->label('⭐')->boolean(),

                Tables\Columns\TextColumn::make('sort_order')->label('#'),

                Tables\Columns\TextColumn::make('type')->badge(),

                Tables\Columns\TextColumn::make('title')->wrap(),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Розмір')
                    ->formatStateUsing(fn ($state) =>
                        $state ? round($state / 1024, 1) . ' KB' : '—'
                    ),

                Tables\Columns\TextColumn::make('mime')
                    ->label('MIME')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Файл')
                    ->url(fn ($record) => $record->url, true)
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                CreateAction::make(),

                Action::make('normalize')
                    ->label('Вирівняти порядок')
                    ->icon('heroicon-o-arrows-up-down')
                    ->action(fn () =>
                        ProductFile::stabilize($this->getOwnerRecord()->id)
                    )
                    ->successNotificationTitle('Порядок вирівняно'),
            ])
            ->actions([
                Action::make('makePrimary')
                    ->label('Зробити основним')
                    ->visible(fn ($record) => ! $record->is_primary)
                    ->action(function ($record) {
                        ProductFile::where('product_id', $record->product_id)
                            ->update(['is_primary' => false]);

                        $record->update(['is_primary' => true]);
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

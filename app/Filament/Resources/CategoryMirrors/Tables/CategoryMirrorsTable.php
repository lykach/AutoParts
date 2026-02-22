<?php
namespace App\Filament\Resources\CategoryMirrors\Tables;

use App\Models\CategoryMirror;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CategoryMirrorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parentCategory.name_uk')
                    ->label('Під категорією')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (CategoryMirror $record): string => 
                        'Шлях: ' . $record->parentCategory->full_url_path
                    ),
                
                TextColumn::make('sourceCategory.name_uk')
                    ->label('Дублікат категорії')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->description(fn (CategoryMirror $record): string => 
                        'Оригінал: ' . $record->sourceCategory->full_url_path
                    ),
                
                TextColumn::make('display_name')
                    ->label('Назва для відображення')
                    ->description(fn (CategoryMirror $record): string => 
                        $record->custom_name_uk 
                            ? 'Перейменовано' 
                            : 'Оригінальна назва'
                    )
                    ->toggleable(),
                
                TextColumn::make('full_url_path')
                    ->label('Повний URL шлях')
                    ->copyable()
                    ->copyMessage('URL скопійовано')
                    ->badge()
                    ->color('success')
                    ->description('Шлях через дублікат'),
                
                TextColumn::make('custom_slug')
                    ->label('Власний slug')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Неактивні'),
            ])
            ->recordUrl( // ✅ recordUrl замість recordActions
                fn (CategoryMirror $record): string => 
                    route('filament.admin.resources.category-mirrors.edit', ['record' => $record])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Дублікати видалено')
                                ->body('Вибрані дублікати успішно видалено.')
                        ),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
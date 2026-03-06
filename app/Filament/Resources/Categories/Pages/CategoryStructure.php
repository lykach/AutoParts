<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryStructure extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.category-structure';

    protected static ?string $title = 'Структура каталогу';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад до категорій')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Category::query()
                    ->with(['parent:id,name_uk'])
                    ->orderBy('path_ids')
            )
            ->defaultSort('path_ids')
            ->recordUrl(fn (Category $record): string => static::getResource()::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('name_uk')
                    ->label('Дерево каталогу')
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(function (?string $state, Category $record): string {
                        $depth = max(0, (int) ($record->depth ?? 0));

                        $indent = '';
                        for ($i = 0; $i < $depth; $i++) {
                            $indent .= '<span style="display:inline-block;width:22px;color:#94a3b8;">│</span>';
                        }

                        $branch = $depth > 0
                            ? '<span style="color:#94a3b8;">└─ </span>'
                            : '';

                        if ((bool) $record->is_container) {
                            $icon = '🧱';
                        } elseif ((int) $record->depth === 0) {
                            $icon = '🗂️';
                        } elseif ((bool) $record->is_leaf && (int) $record->products_total_count > 0) {
                            $icon = '📦';
                        } elseif ((bool) $record->is_leaf) {
                            $icon = '📄';
                        } else {
                            $icon = '📁';
                        }

                        $name = e((string) ($state ?? ''));

                        return <<<HTML
{$indent}{$branch}<span>{$icon} {$name}</span>
HTML;
                    })
                    ->color(function (Category $record): string {
                        if ((bool) $record->is_container) {
                            return 'info';
                        }

                        if ((int) $record->depth === 0) {
                            return 'primary';
                        }

                        if ((bool) $record->is_leaf && (int) $record->products_total_count > 0) {
                            return 'success';
                        }

                        if ((bool) $record->is_leaf) {
                            return 'gray';
                        }

                        return 'warning';
                    })
                    ->description(function (Category $record): string {
                        $parts = [];

                        $parts[] = 'Path: /' . $record->full_url_path;
                        $parts[] = 'ID: ' . $record->id;
                        $parts[] = 'Depth: ' . (int) $record->depth;

                        if ($record->parent?->name_uk) {
                            $parts[] = 'Батько: ' . $record->parent->name_uk;
                        } else {
                            $parts[] = 'Root';
                        }

                        return implode(' • ', $parts);
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Дітей')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => (int) $record->children_count > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('products_direct_count')
                    ->label('Товарів тут')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => (int) $record->products_direct_count > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('products_total_count')
                    ->label('Товарів у гілці')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => (int) $record->products_total_count > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('depth')
                    ->label('Рівень')
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_container')
                    ->label('Container')
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_leaf')
                    ->label('Leaf')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('path_slugs')
                    ->label('Path')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('path_ids')
                    ->label('Path IDs')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_root')
                    ->label('Root')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки root')
                    ->falseLabel('Тільки вкладені')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('parent_id'),
                        false: fn (Builder $query) => $query->whereNotNull('parent_id'),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\TernaryFilter::make('has_products')
                    ->label('Товари в гілці')
                    ->placeholder('Всі')
                    ->trueLabel('Є товари')
                    ->falseLabel('Без товарів')
                    ->queries(
                        true: fn (Builder $query) => $query->where('products_total_count', '>', 0),
                        false: fn (Builder $query) => $query->where('products_total_count', 0),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\TernaryFilter::make('is_leaf')
                    ->label('Leaf')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки leaf')
                    ->falseLabel('Тільки не-leaf'),

                Tables\Filters\TernaryFilter::make('is_container')
                    ->label('Container')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки container')
                    ->falseLabel('Не container'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Активні')
                    ->falseLabel('Неактивні'),
            ])
            ->paginated([50, 100, 'all'])
            ->defaultPaginationPageOption(100)
            ->striped();
    }
}
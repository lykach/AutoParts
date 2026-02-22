<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Widgets\CategoryTreeWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Створити категорію')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
    
    // ✅ Tree Widget зверху
    protected function getHeaderWidgets(): array
    {
        return [
            CategoryTreeWidget::class,
        ];
    }
    
    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
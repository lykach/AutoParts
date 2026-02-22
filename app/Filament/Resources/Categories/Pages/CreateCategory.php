<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
    
    /**
     * ✅ Підставляємо parent_id при відкритті форми
     */
    public function mount(): void
    {
        parent::mount();
        
        // Отримуємо parent_id з URL
        $parentId = request()->query('parent_id');
        
        if ($parentId) {
            $parent = Category::find($parentId);
            
            if ($parent) {
                // Перевіряємо чи може мати дітей
                if (!$parent->canHaveChildren()) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка')
                        ->body("Категорія '{$parent->name_uk}' має товари і не може мати підкатегорій!")
                        ->persistent()
                        ->send();
                    
                    // Перенаправляємо назад
                    redirect()->route('filament.admin.resources.categories.index');
                    return;
                }
                
                // ✅ Встановлюємо parent_id в дані форми
                $this->data['parent_id'] = (int) $parentId;
                
                Notification::make()
                    ->success()
                    ->title('Створення підкатегорії')
                    ->body("Батьківська категорія: {$parent->name_uk}")
                    ->send();
            }
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад до списку')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Категорію створено')
            ->body('Категорія успішно додана до каталогу.');
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Конвертуємо null parent_id → -1
        if (!isset($data['parent_id']) || $data['parent_id'] === null) {
            $data['parent_id'] = -1;
        }
        
        return $data;
    }
}
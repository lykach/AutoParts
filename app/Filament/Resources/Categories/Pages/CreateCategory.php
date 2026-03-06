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

    public function mount(): void
    {
        parent::mount();

        $parentId = request()->query('parent_id');

        if (! $parentId) {
            return;
        }

        $parent = Category::find((int) $parentId);

        if (! $parent) {
            return;
        }

        if (! $parent->canHaveChildren()) {
            Notification::make()
                ->danger()
                ->title('Помилка')
                ->body("Категорія '{$parent->name_uk}' не може мати підкатегорій, бо має товари або характеристики.")
                ->persistent()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
            return;
        }

        $this->data['parent_id'] = (int) $parentId;

        Notification::make()
            ->success()
            ->title('Створення підкатегорії')
            ->body("Батьківська категорія: {$parent->name_uk}")
            ->send();
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
}
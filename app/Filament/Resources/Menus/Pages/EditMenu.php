<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Models\Menu;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LogicException;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => ! $this->record->is_system)
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        $this->record->delete();

                        $this->redirect(static::getResource()::getUrl('index'));
                    } catch (LogicException $e) {
                        Notification::make()
                            ->title('Меню не можна видалити')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->is_system) {
            $data['code'] = $this->record->code;
            $data['is_system'] = true;
        }

        return $data;
    }
}
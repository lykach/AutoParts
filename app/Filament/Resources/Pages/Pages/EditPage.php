<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LogicException;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

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
                            ->title('Сторінку не можна видалити')
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
            $data['slug'] = $this->record->slug;
            $data['is_system'] = true;
        }

        $data['updated_by'] = auth()->id();

        return $data;
    }
}
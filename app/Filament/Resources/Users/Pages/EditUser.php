<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Видалити користувача?')
                ->modalDescription('Ця дія назавжди видалить аккаунт. Ви впевнені?')
                ->modalSubmitActionLabel('Так, видалити')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Видалено')
                        ->body('Користувача успішно видалено.')
                )
                ->disabled(fn ($record) => $record->id === auth()->id())
                ->hidden(fn ($record) => $record->id === auth()->id()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Зміни збережено')
            ->body("Дані користувача '{$this->record->name}' оновлені.");
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['email_verified_at'] = $this->record->email_verified_at?->format('d.m.Y H:i');
        $data['created_at'] = $this->record->created_at?->format('d.m.Y H:i');
        $data['updated_at'] = $this->record->updated_at?->format('d.m.Y H:i');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ✅ Backend-захист: admin/manager не може додати super-admin роль
        if (isset($data['roles']) && is_array($data['roles'])) {
            $me = auth()->user();

            $allowedRoleIds = Role::query()
                ->when(! $me?->hasRole('super-admin'), fn ($q) => $q->where('name', '!=', 'super-admin'))
                ->pluck('id')
                ->all();

            $data['roles'] = array_values(array_intersect($data['roles'], $allowedRoleIds));
        }

        return $data;
    }
}

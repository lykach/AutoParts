<?php

namespace App\Filament\Resources\Permissions\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus-circle')
                ->label('Створити permission')
                ->visible(fn () => auth()->user()?->hasRole('super-admin') || auth()->user()?->can('permissions.create')),
        ];
    }
}

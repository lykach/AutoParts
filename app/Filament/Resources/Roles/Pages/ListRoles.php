<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Створити роль')
                ->icon('heroicon-m-plus-circle')
                ->visible(fn () => auth()->user()?->hasRole('super-admin') || auth()->user()?->can('roles.create')),
        ];
    }
}

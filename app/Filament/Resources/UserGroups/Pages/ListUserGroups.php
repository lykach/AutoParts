<?php

namespace App\Filament\Resources\UserGroups\Pages;

use App\Filament\Resources\UserGroups\UserGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserGroups extends ListRecords
{
    protected static string $resource = UserGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus-circle')
                ->label('Створити групу'),
        ];
    }
}
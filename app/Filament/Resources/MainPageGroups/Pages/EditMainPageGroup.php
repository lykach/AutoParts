<?php

namespace App\Filament\Resources\MainPageGroups\Pages;

use App\Filament\Resources\MainPageGroups\MainPageGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMainPageGroup extends EditRecord
{
    protected static string $resource = MainPageGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
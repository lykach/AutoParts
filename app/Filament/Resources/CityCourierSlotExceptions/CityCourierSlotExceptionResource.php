<?php

namespace App\Filament\Resources\CityCourierSlotExceptions;

use App\Filament\Resources\CityCourierSlotExceptions\Pages\CreateCityCourierSlotException;
use App\Filament\Resources\CityCourierSlotExceptions\Pages\EditCityCourierSlotException;
use App\Filament\Resources\CityCourierSlotExceptions\Pages\ListCityCourierSlotExceptions;
use App\Filament\Resources\CityCourierSlotExceptions\Schemas\CityCourierSlotExceptionForm;
use App\Filament\Resources\CityCourierSlotExceptions\Tables\CityCourierSlotExceptionsTable;
use App\Models\CityCourierSlotException;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CityCourierSlotExceptionResource extends Resource
{
    protected static ?string $model = CityCourierSlotException::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|UnitEnum|null $navigationGroup = 'Доставки';

    protected static ?string $navigationLabel = 'Курʼєр: винятки';
    protected static ?string $modelLabel = 'виняток слота доставки';
    protected static ?string $pluralModelLabel = 'винятки слотів доставки';

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'slot.zone.store',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CityCourierSlotExceptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CityCourierSlotExceptionsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) CityCourierSlotException::query()->count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCityCourierSlotExceptions::route('/'),
            'create' => CreateCityCourierSlotException::route('/create'),
            'edit'   => EditCityCourierSlotException::route('/{record}/edit'),
        ];
    }
}
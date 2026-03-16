<?php

namespace App\Filament\Resources\CityCourierZoneSlots;

use App\Filament\Resources\CityCourierZoneSlots\Pages\CreateCityCourierZoneSlot;
use App\Filament\Resources\CityCourierZoneSlots\Pages\EditCityCourierZoneSlot;
use App\Filament\Resources\CityCourierZoneSlots\Pages\ListCityCourierZoneSlots;
use App\Filament\Resources\CityCourierZoneSlots\Schemas\CityCourierZoneSlotForm;
use App\Filament\Resources\CityCourierZoneSlots\Tables\CityCourierZoneSlotsTable;
use App\Models\CityCourierZoneSlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CityCourierZoneSlotResource extends Resource
{
    protected static ?string $model = CityCourierZoneSlot::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|UnitEnum|null $navigationGroup = 'Логістика';

    protected static ?string $navigationLabel = 'Курʼєр: слоти';
    protected static ?string $modelLabel = 'слот курʼєрської доставки';
    protected static ?string $pluralModelLabel = 'слоти курʼєрської доставки';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['zone.store'])
            ->withCount('exceptions');
    }

    public static function form(Schema $schema): Schema
    {
        return CityCourierZoneSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CityCourierZoneSlotsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) CityCourierZoneSlot::query()->count();
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
            'index'  => ListCityCourierZoneSlots::route('/'),
            'create' => CreateCityCourierZoneSlot::route('/create'),
            'edit'   => EditCityCourierZoneSlot::route('/{record}/edit'),
        ];
    }
}
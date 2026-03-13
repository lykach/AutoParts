<?php

namespace App\Filament\Resources\CityCourierZones;

use App\Filament\Resources\CityCourierZones\Pages\CreateCityCourierZone;
use App\Filament\Resources\CityCourierZones\Pages\EditCityCourierZone;
use App\Filament\Resources\CityCourierZones\Pages\ListCityCourierZones;
use App\Filament\Resources\CityCourierZones\Schemas\CityCourierZoneForm;
use App\Filament\Resources\CityCourierZones\Tables\CityCourierZonesTable;
use App\Models\CityCourierZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CityCourierZoneResource extends Resource
{
    protected static ?string $model = CityCourierZone::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';
    protected static string|UnitEnum|null $navigationGroup = 'Доставки';

    protected static ?string $navigationLabel = 'Курʼєр по місту';
    protected static ?string $modelLabel = 'зона курʼєрської доставки';
    protected static ?string $pluralModelLabel = 'зони курʼєрської доставки';

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store'])
            ->withCount('slots');
    }

    public static function form(Schema $schema): Schema
    {
        return CityCourierZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CityCourierZonesTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) CityCourierZone::query()->count();
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
            'index'  => ListCityCourierZones::route('/'),
            'create' => CreateCityCourierZone::route('/create'),
            'edit'   => EditCityCourierZone::route('/{record}/edit'),
        ];
    }
}
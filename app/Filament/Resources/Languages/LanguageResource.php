<?php

namespace App\Filament\Resources\Languages;

use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Filament\Resources\Languages\Schemas\LanguageForm;
use App\Filament\Resources\Languages\Tables\LanguagesTable;
use App\Models\Language;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    // ✅ МЕТОДИ замість властивостей (Filament 5.x)
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-language';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Локалізація';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Мови';
    }
    
    public static function getModelLabel(): string
    {
        return 'Мова';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Мови';
    }
    
    public static function getRecordTitleAttribute(): ?string
    {
        return 'name_uk';
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return LanguageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
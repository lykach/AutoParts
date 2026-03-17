<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use UnitEnum;

class Content extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|UnitEnum|null $navigationGroup = 'CMS';
    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return 'Контент';
    }

    public static function getClusterBreadcrumb(): string
    {
        return 'Контент';
    }
}
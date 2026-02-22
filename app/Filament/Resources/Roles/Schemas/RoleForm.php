<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Section::make('Роль')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Системна назва (slug)')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->disabled(function (?Role $record): bool {
                                $me = auth()->user();

                                if ($record?->name === 'super-admin') {
                                    return ! $me?->hasRole('super-admin');
                                }

                                return false;
                            })
                            ->helperText('Напр: super-admin, admin, manager, user'),

                        TextInput::make('description')
                            ->label('Опис')
                            ->maxLength(255)
                            ->placeholder('Для чого ця роль?')
                            ->helperText('Показується в списках/формах для зрозумілості'),

                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->disabled()
                            ->dehydrated(true),

                        Checkbox::make('confirm_permissions_change')
                            ->label('Я розумію, що зміна прав може дати доступ до критичних дій')
                            ->helperText('Без підтвердження список прав буде заблокований.')
                            ->dehydrated(false)
                            ->visible(fn (?Role $record) => $record !== null)
                            ->default(false)
                            ->live(),

                        Select::make('permissions')
                            ->label('Права (permissions)')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->options(function () {
                                return Permission::query()
                                    ->where('guard_name', 'web')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function (Permission $p) {
                                        $desc = trim((string) ($p->description ?? ''));
                                        $label = $desc !== '' ? "{$p->name} — {$desc}" : $p->name;
                                        return [$p->id => $label];
                                    })
                                    ->all();
                            })
                            ->afterStateHydrated(function ($component, ?Role $record) {
                                if (! $record) {
                                    return;
                                }

                                $component->state($record->permissions()->pluck('id')->all());
                            })
                            ->disabled(function (?Role $record, callable $get): bool {
                                $me = auth()->user();

                                // супер-захист: super-admin роль може редагувати тільки super-admin
                                if ($record?->name === 'super-admin') {
                                    return ! $me?->hasRole('super-admin');
                                }

                                // чекбокс підтвердження (на edit)
                                if ($record !== null) {
                                    return ! (bool) $get('confirm_permissions_change');
                                }

                                return false;
                            })
                            // щоб при заблокованому полі не було "синхронізації" і випадкового стирання
                            ->dehydrated(fn (callable $get) => (bool) $get('confirm_permissions_change'))
                            ->helperText('Вибирай уважно. Це визначає, що може робити роль.'),
                    ]),

                Section::make('Підказка')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('hint')
                            ->label('Рекомендація')
                            ->disabled()
                            ->dehydrated(false)
                            ->default('Формат permissions: <module>.<action> (напр. users.view, orders.update).'),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Rules\UkrainianPhone;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Section::make('Основна інформація')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Ім\'я')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->mask('+38 (099) 999-99-99')
                            ->placeholder('+38 (0__) ___-__-__')
                            ->default('+38 (0')
                            ->stripCharacters([' ', '(', ')', '-'])
                            ->rules([new UkrainianPhone])
                            ->dehydrateStateUsing(function ($state) {
                                if (empty($state) || $state === '+38 (0' || $state === '+38 (0)') {
                                    return null;
                                }
                                return UkrainianPhone::normalize($state);
                            })
                            ->afterStateHydrated(function ($component, $state) {
                                if (empty($state)) {
                                    $component->state('+38 (0');
                                } else {
                                    $component->state(UkrainianPhone::format($state));
                                }
                            })
                            ->helperText('Формат: +38 (0XX) XXX-XX-XX')
                            ->columnSpan(2),

                        Select::make('user_group_id')
                            ->label('Група користувача')
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Оберіть групу')
                            ->helperText('Група визначає знижки/націнки для клієнта')
                            ->columnSpan(2),

                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::default())
                            ->helperText('Мінімум 8 символів. Залиште пустим, щоб не змінювати.')
                            ->columnSpan(1),

                        TextInput::make('password_confirmation')
                            ->label('Підтвердження паролю')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->requiredWith('password')
                            ->same('password')
                            ->columnSpan(1),

                        FileUpload::make('avatar_url')
                            ->label('Аватар')
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->imagePreviewHeight('100')
                            ->maxSize(1024)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->helperText('Рекомендований розмір: 200x200 px. Формат: PNG, JPG')
                            ->columnSpan(2),

                        /**
                         * ✅ Ролі (Spatie Permission) — БЕЗПЕЧНО:
                         * - super-admin роль не показуємо не-super-admin користувачам
                         * - у підписі показуємо description ролі (якщо є)
                         */
                        Select::make('roles')
                            ->label('Ролі в системі')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query) {
                                    $me = auth()->user();

                                    if (! $me?->hasRole('super-admin')) {
                                        $query->where('name', '!=', 'super-admin');
                                    }

                                    return $query->orderBy('name');
                                }
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(function (Role $record): string {
                                $desc = trim((string) ($record->description ?? ''));
                                return $desc !== '' ? "{$record->name} — {$desc}" : $record->name;
                            })
                            ->helperText('Уважно: ролі визначають доступ до адмінки.')
                            ->columnSpan(2),
                    ]),

                Section::make('Інформація про обліковий запис')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('email_verified_at')
                            ->label('Email підтверджено')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Дата підтвердження email'),

                        TextInput::make('created_at')
                            ->label('Створено')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('updated_at')
                            ->label('Оновлено')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}

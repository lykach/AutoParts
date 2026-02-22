<?php

namespace App\Filament\Resources\Store\Tables;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Store;
use App\Rules\UkrainianPhone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoreTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->withCount('stockSourceLinks');
            })
            ->columns([
                ImageColumn::make('logo')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(34),

                TextColumn::make('name_uk')
                    ->label('Магазин')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Store $record) => $record->slug),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Місто')
                    ->toggleable(),

                // ✅ Основний телефон (як у UsersTable — формат + copy)
                TextColumn::make('primary_phone')
                    ->label('Телефон')
                    ->state(function (Store $record): ?string {
                        $phones = is_array($record->phones) ? $record->phones : [];

                        if (empty($phones)) {
                            return null;
                        }

                        // primary first
                        $primary = collect($phones)->firstWhere('is_primary', true);
                        $first = $phones[0] ?? null;

                        $raw = $primary['number'] ?? ($first['number'] ?? null);

                        return $raw ? UkrainianPhone::format($raw) : null;
                    })
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Телефон скопійовано')
                    ->icon('heroicon-o-phone')
                    ->toggleable(),

                TextColumn::make('stock_source_links_count')
                    ->label('Склади')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('resolved_country')
                    ->label('Країна')
                    ->state(function (Store $record) {
                        static $map = null;
                        $map ??= Country::query()->pluck('name_uk', 'id')->all();

                        $id = $record->resolvedCountryId();
                        return $id ? ($map[$id] ?? ('#' . $id)) : '—';
                    })
                    ->toggleable(),

                TextColumn::make('resolved_currency')
                    ->label('Валюта')
                    ->state(function (Store $record) {
                        static $map = null;
                        $map ??= Currency::query()->pluck('code', 'id')->all();

                        $id = $record->resolvedCurrencyId();
                        return $id ? ($map[$id] ?? ('#' . $id)) : '—';
                    })
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Тільки активні')
                    ->query(fn (Builder $q) => $q->where('is_active', true)),

                Filter::make('main')
                    ->label('Тільки головний')
                    ->query(fn (Builder $q) => $q->where('is_main', true)),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('toggleActive')
                        ->label(fn (Store $record) => $record->is_active ? 'Вимкнути' : 'Увімкнути')
                        ->icon(fn (Store $record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->requiresConfirmation()
                        ->action(function (Store $record) {
                            $record->is_active = ! $record->is_active;
                            $record->save();

                            Notification::make()
                                ->title('Збережено')
                                ->body($record->is_active ? 'Магазин увімкнено' : 'Магазин вимкнено')
                                ->success()
                                ->send();
                        }),

                    Action::make('copyFromMain')
                        ->label('Копіювати з головного')
                        ->icon('heroicon-o-document-duplicate')
                        ->requiresConfirmation()
                        ->visible(fn (Store $record) => ! $record->is_main)
                        ->action(function (Store $record) {
                            /** @var Store|null $main */
                            $main = Store::query()->where('is_main', true)->orderBy('id')->first();

                            if (! $main) {
                                Notification::make()
                                    ->title('Немає головного магазину')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $fields = [
                                'country_id', 'currency_id', 'timezone', 'default_language',
                                'email', 'website_url', 'phones', 'additional_emails', 'messengers', 'social_links',
                                'working_hours', 'working_exceptions',
                                'payment_methods', 'delivery_methods', 'services',
                                'pickup_instructions_uk', 'pickup_instructions_en', 'pickup_instructions_ru',
                                'delivery_info_uk', 'delivery_info_en', 'delivery_info_ru',
                                'title_uk', 'title_en', 'title_ru',
                                'description_uk', 'description_en', 'description_ru',
                                'meta_title_uk', 'meta_title_en', 'meta_title_ru',
                                'meta_description_uk', 'meta_description_en', 'meta_description_ru',
                                'canonical_url', 'robots', 'seo',
                                'company_name', 'edrpou', 'vat', 'legal_address',
                                'settings',
                            ];

                            foreach ($fields as $f) {
                                $record->{$f} = $main->{$f};
                            }

                            $settings = is_array($record->settings) ? $record->settings : [];
                            $settings['overrides'] = array_merge(
                                $settings['overrides'] ?? [],
                                [
                                    'working_hours' => true,
                                    'delivery' => true,
                                    'contacts' => true,
                                    'seo' => true,
                                    'legal' => true,
                                    'stock_sources' => false,
                                ]
                            );
                            $record->settings = $settings;

                            $record->save();

                            Notification::make()
                                ->title('Скопійовано')
                                ->body('Дані перенесено з головного магазину.')
                                ->success()
                                ->send();
                        }),
                ])->iconButton(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order', 'asc');
    }
}

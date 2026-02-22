<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use App\Services\CurrencyRateService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateRates')
                ->label('Оновити курси')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Оновити курси валют?')
                ->modalDescription('Курси будуть оновлені з PrivatBank або NBU API.')
                ->action(function () {
                    /** @var CurrencyRateService $service */
                    $service = app(CurrencyRateService::class);

                    $results = $service->updateRates();

                    if (!empty($results['success'])) {
                        Notification::make()
                            ->success()
                            ->title('Курси оновлено!')
                            ->body('Джерело: ' . ($results['source'] ?? '—') . '. Оновлено: ' . count($results['success']) . ' валют.')
                            ->send();
                    }

                    if (!empty($results['failed'])) {
                        Notification::make()
                            ->warning()
                            ->title('Оновлення з попередженнями')
                            ->body(implode('; ', $results['failed']))
                            ->send();
                    }

                    if (empty($results['success']) && empty($results['failed'])) {
                        Notification::make()
                            ->info()
                            ->title('Нічого не оновлено')
                            ->body('Спробуй ще раз пізніше.')
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}

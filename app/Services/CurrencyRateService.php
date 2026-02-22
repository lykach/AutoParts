<?php

namespace App\Services;

use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    /**
     * ✅ Які валюти ми вміємо тягнути з API
     */
    private const SUPPORTED = ['USD', 'EUR', 'PLN', 'GBP'];

    public function updateRates(): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'source' => null,
        ];

        $baseCurrency = Currency::query()
            ->where('is_default', true)
            ->first();

        if (!$baseCurrency) {
            $results['failed'][] = 'Не знайдено головної (default) валюти.';
            return $results;
        }

        // ✅ Поки працюємо тільки з UAH як базовою (бо API віддають курси відносно UAH)
        if (strtoupper($baseCurrency->code) !== 'UAH') {
            $results['failed'][] = 'Головна валюта має бути UAH для роботи з PrivatBank/NBU.';
            return $results;
        }

        $currencies = Currency::query()
            ->where('is_default', false)
            ->where('is_active', true)
            ->whereIn('code', self::SUPPORTED)
            ->get();

        if ($currencies->isEmpty()) {
            $results['failed'][] = 'Немає активних валют для оновлення (USD/EUR/PLN/GBP).';
            return $results;
        }

        // 1) PrivatBank
        $rates = $this->fetchFromPrivatBank();
        if (!empty($rates)) {
            $results['source'] = 'PrivatBank';
        } else {
            // 2) Fallback на NBU
            $rates = $this->fetchFromNBU();
            if (!empty($rates)) {
                $results['source'] = 'NBU';
            }
        }

        if (empty($rates)) {
            $results['failed'][] = 'Не вдалося отримати курси з PrivatBank або NBU.';
            return $results;
        }

        foreach ($currencies as $currency) {
            $code = strtoupper($currency->code);

            if (!isset($rates[$code])) {
                $results['failed'][] = "{$code}: курс не знайдено";
                continue;
            }

            $currency->update([
                // ✅ rate = скільки UAH за 1 одиницю валюти (наприклад USD=39.1234)
                'rate' => $rates[$code],
                'rate_updated_at' => now(),
            ]);

            $results['success'][] = "{$code}: {$rates[$code]}";
        }

        return $results;
    }

    private function fetchFromPrivatBank(): array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://api.privatbank.ua/p24api/pubinfo?exchange&coursid=5');

            if (!$response->successful()) {
                Log::warning('PrivatBank API недоступний', ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            $rates = [];

            foreach ($data as $item) {
                $ccy = strtoupper((string) ($item['ccy'] ?? ''));
                $sale = (float) ($item['sale'] ?? 0);

                if (in_array($ccy, self::SUPPORTED, true) && $sale > 0) {
                    $rates[$ccy] = round($sale, 4);
                }
            }

            Log::info('Курси отримано з PrivatBank', ['rates' => $rates]);

            return $rates;
        } catch (\Throwable $e) {
            Log::error('Помилка PrivatBank API', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchFromNBU(): array
    {
        try {
            $date = Carbon::now()->format('Ymd');

            $response = Http::timeout(10)
                ->get("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?date={$date}&json");

            if (!$response->successful()) {
                Log::warning('NBU API недоступний', ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            $rates = [];

            foreach ($data as $item) {
                $cc = strtoupper((string) ($item['cc'] ?? ''));
                $rate = (float) ($item['rate'] ?? 0);

                if (in_array($cc, self::SUPPORTED, true) && $rate > 0) {
                    $rates[$cc] = round($rate, 4);
                }
            }

            Log::info('Курси отримано з NBU', ['rates' => $rates]);

            return $rates;
        } catch (\Throwable $e) {
            Log::error('Помилка NBU API', ['error' => $e->getMessage()]);
            return [];
        }
    }
}

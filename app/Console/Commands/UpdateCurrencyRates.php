<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CurrencyRateService;

class UpdateCurrencyRates extends Command
{
    protected $signature = 'currency:update';
    protected $description = 'Оновлює курси валют з PrivatBank або NBU API';

    public function handle(CurrencyRateService $service)
    {
        $this->info('Оновлення курсів валют...');
        
        $results = $service->updateRates();
        
        // Виводимо результати
        if (!empty($results['success'])) {
            $this->info("✓ Джерело: {$results['source']}");
            $this->info('✓ Успішно оновлено:');
            foreach ($results['success'] as $message) {
                $this->line("  • {$message}");
            }
        }
        
        if (!empty($results['failed'])) {
            $this->warn('✗ Помилки:');
            foreach ($results['failed'] as $message) {
                $this->line("  • {$message}");
            }
        }
        
        return empty($results['failed']) ? Command::SUCCESS : Command::FAILURE;
    }
}
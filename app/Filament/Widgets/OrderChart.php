<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatusEnum;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderChart extends ChartWidget
{
    protected ?string $heading = 'Order Chart';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = Order::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => "Orders",
                    'data' => array_values($data)
                ],

            ],
            'labels' => OrderStatusEnum::cases()
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

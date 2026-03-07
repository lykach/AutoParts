<div class="space-y-3">
    @if($products->isEmpty())
        <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-600">
            Нічого не знайдено.
        </div>
    @else
        <div class="rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">ID</th>
                        <th class="px-3 py-2 text-left">Артикул</th>
                        <th class="px-3 py-2 text-left">Назва</th>
                        <th class="px-3 py-2 text-left">Категорія</th>
                        <th class="px-3 py-2 text-left">Бренд</th>
                        <th class="px-3 py-2 text-left">Ціна</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="border-t">
                            <td class="px-3 py-2 font-medium">{{ $product->id }}</td>
                            <td class="px-3 py-2">{{ $product->display_article }}</td>
                            <td class="px-3 py-2">{{ $product->display_name }}</td>
                            <td class="px-3 py-2">{{ $product->category?->name_uk ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $product->manufacturer?->name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                {{ $product->best_price_uah ? number_format($product->best_price_uah, 2, '.', ' ') . ' грн' : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-xs text-gray-500">
            Скопіюй ID потрібного товару в поле “ID товару”.
        </div>
    @endif
</div>
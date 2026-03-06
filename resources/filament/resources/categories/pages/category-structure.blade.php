<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-sm text-gray-500">Всього категорій</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->stats['total'] }}</div>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-sm text-gray-500">Root</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->stats['root'] }}</div>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-sm text-gray-500">Container</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->stats['containers'] }}</div>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-sm text-gray-500">Leaf</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->stats['leaf'] }}</div>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-sm text-gray-500">З товарами</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->stats['with_products'] }}</div>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-sm text-gray-500">Макс. глибина</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->stats['max_depth'] }}</div>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Пошук по дереву</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Назва, path, ID..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input
                        type="checkbox"
                        wire:model.live="showInactive"
                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                    >
                    Показувати неактивні
                </label>
            </div>
        </div>

        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-4 py-3 text-sm text-gray-500">
                Клік по назві відкриває редагування. Кнопка ліворуч від вузла згортaє або розгортaє гілку.
            </div>

            <div class="divide-y">
                @php
                    $renderTree = function (array $nodes) use (&$renderTree) {
                        foreach ($nodes as $node) {
                            /** @var \App\Models\Category $record */
                            $record = $node['record'];
                            $children = $node['children'];
                            $hasChildren = count($children) > 0;
                            $isExpanded = in_array((int) $record->id, $this->expanded, true);
                            $indent = min((int) $record->depth, 12) * 28;
                @endphp
                    <div class="px-3 py-2 {{ $this->rowColor($record) }}">
                        <div class="flex items-start gap-3">
                            <div style="padding-left: {{ $indent }}px;" class="flex items-center gap-2 min-w-0 flex-1">
                                @if ($hasChildren)
                                    <button
                                        type="button"
                                        wire:click="toggleNode({{ $record->id }})"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border bg-white text-sm hover:bg-gray-50"
                                        title="{{ $isExpanded ? 'Згорнути' : 'Розгорнути' }}"
                                    >
                                        {{ $isExpanded ? '−' : '+' }}
                                    </button>
                                @else
                                    <span class="inline-flex h-7 w-7 items-center justify-center text-gray-300">•</span>
                                @endif

                                @if ($record->depth > 0)
                                    <span class="text-gray-400">└─</span>
                                @endif

                                <a
                                    href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('edit', ['record' => $record]) }}"
                                    class="min-w-0 flex-1"
                                >
                                    <div class="truncate font-semibold text-gray-900">
                                        {{ $this->icon($record) }} {{ $record->name_uk }}
                                    </div>
                                    <div class="truncate text-xs text-gray-500">
                                        /{{ $record->full_url_path }}
                                    </div>
                                </a>
                            </div>

                            <div class="hidden md:flex items-center gap-2 text-xs">
                                <span class="rounded-md border bg-white px-2 py-1">ID: {{ $record->id }}</span>
                                <span class="rounded-md border bg-white px-2 py-1">Depth: {{ $record->depth }}</span>
                                <span class="rounded-md border bg-white px-2 py-1">Дітей: {{ $record->children_count }}</span>
                                <span class="rounded-md border bg-white px-2 py-1">Тут: {{ $record->products_direct_count }}</span>
                                <span class="rounded-md border bg-white px-2 py-1">Гілка: {{ $record->products_total_count }}</span>
                                @if($record->is_container)
                                    <span class="rounded-md border border-sky-200 bg-sky-100 px-2 py-1">Container</span>
                                @endif
                                @if($record->is_leaf)
                                    <span class="rounded-md border border-emerald-200 bg-emerald-100 px-2 py-1">Leaf</span>
                                @endif
                                @if(!$record->is_active)
                                    <span class="rounded-md border border-rose-200 bg-rose-100 px-2 py-1">Неактивна</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($hasChildren && $isExpanded)
                        <div>
                            @php $renderTree($children); @endphp
                        </div>
                    @endif
                @php
                        }
                    };
                @endphp

                @php $renderTree($this->tree); @endphp
            </div>
        </div>
    </div>
</x-filament-panels::page>
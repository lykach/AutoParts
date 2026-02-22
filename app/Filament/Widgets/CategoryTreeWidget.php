<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use SolutionForest\FilamentTree\Widgets\Tree as BaseWidget;

class CategoryTreeWidget extends BaseWidget
{
    protected static string $model = Category::class;

    protected static int $maxDepth = 10;

    protected ?string $treeTitle = 'Дерево категорій';

    protected bool $enableTreeTitle = true;

    /**
     * ✅ Всі вузли згорнуті при завантаженні
     */
    public function getNodeCollapsedState(?\Illuminate\Database\Eloquent\Model $record = null): bool
    {
        return true;
    }

    /**
     * ✅ Основний query дерева (без N+1)
     */
    protected function getTreeQuery(): Builder
    {
        return Category::query()
            ->select('categories.*')
            ->withCount([
                'children',
                'products',
                'mirrorsAsParent',
                'mirrorsAsSource',
            ])
            ->orderBy('parent_id')
            ->orderBy('order');
    }

    /**
     * ✅ Заголовок вузла (бейджі)
     */
    public function getTreeRecordTitle(?\Illuminate\Database\Eloquent\Model $record = null): string
    {
        if (! $record) {
            return '';
        }

        /** @var Category $record */
        $title = (string) ($record->name_uk ?? '');

        $badges = [];

        // -----------------------
        // Діти
        // -----------------------
        $childrenCount = $record->children_count;
        if ($childrenCount === null) {
            $childrenCount = $record->children()->count();
        }
        $childrenCount = (int) $childrenCount;

        $badges[] = $childrenCount > 0 ? "📁 {$childrenCount}" : "📄";

        // -----------------------
        // Товари (fallback якщо withCount не підвантажився)
        // -----------------------
        $productsCount = $record->products_count;
        if ($productsCount === null) {
            $productsCount = $record->products()->count();
        }
        $productsCount = (int) $productsCount;

        if ($productsCount > 0) {
            $badges[] = "🛒 {$productsCount}";
        }

        // -----------------------
        // Дзеркала під контейнером (parent)
        // -----------------------
        $mirrorsParentCount = $record->mirrors_as_parent_count;
        if ($mirrorsParentCount === null) {
            $mirrorsParentCount = $record->mirrorsAsParent()->count();
        }
        $mirrorsParentCount = (int) $mirrorsParentCount;

        if ($mirrorsParentCount > 0) {
            $badges[] = "🔁 {$mirrorsParentCount}";
        }

        // -----------------------
        // Скільки разів цю категорію дублюють (source)
        // -----------------------
        $mirrorsSourceCount = $record->mirrors_as_source_count;
        if ($mirrorsSourceCount === null) {
            $mirrorsSourceCount = $record->mirrorsAsSource()->count();
        }
        $mirrorsSourceCount = (int) $mirrorsSourceCount;

        if ($mirrorsSourceCount > 0) {
            $badges[] = "🧬 {$mirrorsSourceCount}";
        }

        // -----------------------
        // Контейнер
        // -----------------------
        if ((bool) $record->is_container) {
            $badges[] = "🧱";
        }

        // -----------------------
        // TecDoc
        // -----------------------
        if (! empty($record->tecdoc_id)) {
            $badges[] = "🏷 {$record->tecdoc_id}";
        }

        // -----------------------
        // Неактивна
        // -----------------------
        if (! (bool) $record->is_active) {
            $badges[] = "🚫";
        }

        return implode(' ', $badges) . '  ' . $title;
    }

    /**
     * ✅ Форма редагування у TreeWidget (синхрон з Category.php + CategoryForm)
     */
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('full_url_path')
                ->label('Шлях (URL)')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(function ($state, $record) {
                    /** @var Category|null $record */
                    return $record?->full_url_path ? ('/' . $record->full_url_path) : '—';
                })
                ->helperText('Це canonical шлях категорії.'),

            Select::make('parent_id')
                ->label('Батьківська категорія')
                ->placeholder('Коренева категорія (без батька)')
                ->options(function ($record) {
                    $query = Category::query();

                    if ($record instanceof Category && $record->exists) {
                        // не можна вибрати саму себе
                        $query->where('id', '!=', $record->id);

                        // ✅ не можна вибрати нащадка (анти-цикл)
                        $descendants = $record->descendantIds();
                        if (! empty($descendants)) {
                            $query->whereNotIn('id', $descendants);
                        }
                    }

                    return $query->orderBy('name_uk')->pluck('name_uk', 'id')->toArray();
                })
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (! $state) return;

                    $parent = Category::find((int) $state);

                    // ✅ як у CategoryForm: якщо в батька є товари — не можна робити його батьком
                    if ($parent && ! $parent->canHaveChildren()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка')
                            ->body("Категорія '{$parent->name_uk}' має товари і не може мати підкатегорій!")
                            ->send();

                        $set('parent_id', null);
                    }
                }),

            TextInput::make('name_uk')
                ->label('Назва (Українська)')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    // якщо slug пустий — автогенерація
                    if (! $get('slug')) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),

            TextInput::make('slug')
                ->label('URL Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('name_en')
                ->label('Name (English)')
                ->maxLength(255),

            TextInput::make('name_ru')
                ->label('Название (Русский)')
                ->maxLength(255),

            Textarea::make('description_uk')
                ->label('Опис')
                ->rows(2)
                ->maxLength(1000),

            TextInput::make('tecdoc_id')
                ->label('TecDoc ID')
                ->numeric()
                ->unique(ignoreRecord: true),

            FileUpload::make('image')
                ->label('Зображення')
                ->image()
                ->directory('categories')
                ->disk('public')
                ->visibility('public'),

            Toggle::make('is_active')
                ->label('Активна')
                ->default(true),

            Toggle::make('is_container')
                ->label('Контейнерна категорія (вітрина / дзеркала)')
                ->helperText('Контейнер — для структури/дзеркал. У контейнер НЕ додаються товари.')
                ->default(false)
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if (! $state) return;

                    $id = $get('id');
                    if (! $id) return;

                    $cat = Category::find((int) $id);

                    // ✅ синхрон з Category.php: не можна контейнер, якщо є товари
                    if ($cat && $cat->hasProducts()) {
                        Notification::make()
                            ->danger()
                            ->title('Неможливо')
                            ->body("Категорія '{$cat->name_uk}' має товари — перенесіть товари в кінцеві категорії.")
                            ->send();

                        $set('is_container', false);
                    }
                }),

            Toggle::make('is_leaf')
                ->label('Кінцева (leaf)')
                ->helperText('Оновлюється автоматично (залежить від наявності дітей).')
                ->disabled()
                ->dehydrated(false),
        ];
    }

    /**
     * ✅ Налаштування дерева
     */
    protected function getTreeOptions(): array
    {
        return [
            'defaultParentId' => -1,
            'titleColumn' => 'name_uk',
            'orderColumn' => 'order',
            'parentColumn' => 'parent_id',
        ];
    }

    /**
     * ✅ Дії для вузлів (RBAC) + захисти
     */
    protected function getTreeActions(): array
    {
        $user = auth()->user();

        $canUpdate = (bool) ($user?->hasRole('super-admin') || $user?->can('categories.update'));
        $canDelete = (bool) ($user?->hasRole('super-admin') || $user?->can('categories.delete'));

        $actions = [];

        if ($canUpdate) {
            $actions[] = \SolutionForest\FilamentTree\Actions\EditAction::make()
                ->label('Редагувати')
                ->icon('heroicon-o-pencil');
        }

        if ($canDelete) {
            $actions[] = \SolutionForest\FilamentTree\Actions\DeleteAction::make()
                ->label('Видалити')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->before(function ($action, $record) {
                    /** @var Category $record */

                    if ($record->children()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' має підкатегорії!")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }

                    if ($record->hasProducts()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' має товари!")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }

                    if ($record->mirrorsAsParent()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' використовується як контейнер для дзеркал (CategoryMirrors)!")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }
                });
        }

        return $actions;
    }

    /**
     * ✅ Toolbar (RBAC)
     */
    protected function getTreeToolbarActions(): array
    {
        $user = auth()->user();

        $canCreate = (bool) ($user?->hasRole('super-admin') || $user?->can('categories.create'));

        if (! $canCreate) {
            return [];
        }

        return [
            \SolutionForest\FilamentTree\Actions\CreateAction::make()
                ->label('Створити кореневу категорію')
                ->icon('heroicon-m-plus-circle')
                ->color('success'),
        ];
    }
}
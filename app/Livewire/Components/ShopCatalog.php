<?php

namespace App\Livewire\Components;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The shop (search, categories, sort, product grid) as a section of the homepage (#shop). Filters in place —
 * category / search / sort / page live in the URL (?category=…&q=…). /shop and /shop/{category} still work
 * and share the same query (ShopPage) and grid markup (<x-shop.catalog>).
 */
class ShopCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sort = 'popular';

    #[Url(as: 'category', history: true)]
    public string $categorySlug = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function setCategory(string $slug = ''): void
    {
        $this->categorySlug = $slug;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'categorySlug');
        $this->resetPage();
    }

    /** Active products, optionally in one category / matching a search, in the chosen order. */
    public static function query(?Category $category, string $search = '', string $sort = 'popular'): Builder
    {
        $query = Service::query()->with(['category', 'plans'])->where('is_active', true);

        if ($category) {
            $query->where('category_id', $category->id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_km', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'name' => $query->orderBy('name_en'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order'),
        };

        return $query;
    }

    public function render()
    {
        $category = $this->categorySlug !== '' ? Category::where('slug', $this->categorySlug)->first() : null;

        return view('livewire.components.shop-catalog', [
            'services' => self::query($category, trim($this->search), $this->sort)->paginate(12),
            'categories' => Category::orderBy('sort_order')->get(),
            'category' => $category,
        ]);
    }
}

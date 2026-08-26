<?php

namespace App\Livewire\Pages;

use App\Models\Category;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ShopPage extends Component
{
    use WithPagination;

    public ?Category $category = null;

    #[Url(as: 'search', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sort = 'popular';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    #[Title('ហាង | ROUMDOUL')]
    public function render()
    {
        $query = Service::query()->with(['category', 'plans'])->where('is_active', true);

        if ($this->category) {
            $query->where('category_id', $this->category->id);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name_en', 'like', "%{$this->search}%")
                    ->orWhere('name_km', 'like', "%{$this->search}%")
                    ->orWhere('short_description', 'like', "%{$this->search}%");
            });
        }

        match ($this->sort) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'name' => $query->orderBy('name_en'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order'),
        };

        return view('livewire.pages.shop-page', [
            'services' => $query->paginate(12),
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }
}

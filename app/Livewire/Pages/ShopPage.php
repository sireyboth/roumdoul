<?php

namespace App\Livewire\Pages;

use App\Livewire\Components\ShopCatalog;
use App\Models\Category;
use Livewire\Attributes\Layout;
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

    public function render()
    {
        $query = ShopCatalog::query($this->category, $this->search, $this->sort);

        return view('livewire.pages.shop-page', [
            'services' => $query->paginate(12),
            'categories' => Category::orderBy('sort_order')->get(),
        ])->title(($this->category ? (app()->getLocale() === 'km' ? $this->category->name_km : $this->category->name_en).' | ' : '').__('Shop').' | ROUMDOUL');
    }
}

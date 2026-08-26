<?php

namespace App\Livewire\Pages;

use App\Models\Category;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ROUMDOUL | Premium Digital Services')]
class HomePage extends Component
{
    public function render()
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('livewire.pages.home-page', [
            'categories' => $categories,
            'featuredServices' => Service::with(['category', 'plans'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get(),
            'servicesByCategory' => Service::with(['category', 'plans'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category_id'),
        ]);
    }
}

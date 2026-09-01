<?php

namespace App\Livewire\Pages;

use App\Models\Category;
use App\Models\Review;
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
        return view('livewire.pages.home-page', [
            'categories' => Category::orderBy('sort_order')->get(),
            'featuredServices' => Service::with(['category', 'plans'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get(),
            'reviews' => Review::whereNotNull('comment')
                ->where('comment', '!=', '')
                ->latest()
                ->limit(20)
                ->get(),
            'totalActiveServices' => Service::where('is_active', true)->count(),
        ]);
    }
}

<?php

namespace App\Livewire\Components;

use App\Models\Category;
use Livewire\Component;

class FooterSection extends Component
{
    public function render()
    {
        return view('livewire.components.footer-section', [
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }
}

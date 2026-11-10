<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AboutPage extends Component
{
    public function render()
    {
        return view('livewire.pages.about-page')
            ->title(__('About Us | ROUMDOUL'));
    }
}

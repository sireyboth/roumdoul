<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('អំពីយើង / About Us — JingLong Security')]
class AboutPage extends Component
{
    public function render()
    {
        return view('livewire.pages.about-page');
    }
}

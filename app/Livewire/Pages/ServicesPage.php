<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('សេវាកម្មរបស់យើង / Our Services — JingLong Security')]
class ServicesPage extends Component
{
    public function render()
    {
        return view('livewire.pages.services-page');
    }
}

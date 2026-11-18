<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TermsPage extends Component
{
    public function render()
    {
        return view('livewire.pages.terms-page')->title(__('Terms of Use').' | ROUMDOUL');
    }
}

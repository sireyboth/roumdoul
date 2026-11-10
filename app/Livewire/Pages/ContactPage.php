<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ContactPage extends Component
{
    public function render()
    {
        return view('livewire.pages.contact-page')->title(__('Contact | ROUMDOUL'));
    }
}

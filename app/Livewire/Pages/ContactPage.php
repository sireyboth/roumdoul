<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ទំនាក់ទំនង / Contact Us — JingLong Security')]
class ContactPage extends Component
{
    public function render()
    {
        return view('livewire.pages.contact-page');
    }
}

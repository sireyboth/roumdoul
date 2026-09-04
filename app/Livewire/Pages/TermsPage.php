<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('លក្ខខណ្ឌប្រើប្រាស់ | ROUMDOUL')]
class TermsPage extends Component
{
    public function render()
    {
        return view('livewire.pages.terms-page');
    }
}

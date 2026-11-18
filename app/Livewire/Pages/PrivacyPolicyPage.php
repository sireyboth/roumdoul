<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PrivacyPolicyPage extends Component
{
    public function render()
    {
        return view('livewire.pages.privacy-policy-page')->title(__('Privacy Policy').' | ROUMDOUL');
    }
}

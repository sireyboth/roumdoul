<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('គោលការណ៍ភាពឯកជន | ROUMDOUL')]
class PrivacyPolicyPage extends Component
{
    public function render()
    {
        return view('livewire.pages.privacy-policy-page');
    }
}

<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ចែករំលែកមតិយោបល់ | ROUMDOUL')]
class FeedbackPage extends Component
{
    public function render()
    {
        return view('livewire.pages.feedback-page');
    }
}

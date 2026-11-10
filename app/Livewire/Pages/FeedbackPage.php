<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FeedbackPage extends Component
{
    public function render()
    {
        return view('livewire.pages.feedback-page')->title(__('Share your feedback | ROUMDOUL'));
    }
}

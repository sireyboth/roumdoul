<?php

namespace App\Livewire\Pages;

use App\Models\Review;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class HomePage extends Component
{
    public function render()
    {
        return view('livewire.pages.home-page', [
            'featuredServices' => Service::with(['category', 'plans'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get(),
            'reviews' => Review::where('is_approved', true)
                ->whereNotNull('comment')
                ->where('comment', '!=', '')
                ->latest()
                ->limit(20)
                ->get(),
        ])->title(__('ROUMDOUL | Khmer Digital Invitations'))
            ->layoutData(['description' => __('Beautiful Khmer digital invitations for weddings, anniversaries and every celebration. Pick a template, add your details and send a personal link to each guest.')]);
    }
}

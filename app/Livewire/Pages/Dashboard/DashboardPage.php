<?php

namespace App\Livewire\Pages\Dashboard;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ផ្ទាំងគ្រប់គ្រង | ROUMDOUL')]
class DashboardPage extends Component
{
    public function render()
    {
        $customer = Auth::guard('customer')->user();

        return view('livewire.pages.dashboard.dashboard-page', [
            'invitations' => $customer->invitations()->with('template')->latest()->get(),
            'orders' => $customer->orders()->with('items')->limit(10)->get(),
        ]);
    }
}

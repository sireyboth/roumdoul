<?php

namespace App\Livewire\Pages;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('បញ្ជាក់ការបញ្ជាទិញ | ROUMDOUL')]
class OrderConfirmationPage extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $order->load('items');
        $this->order = $order;
    }

    public function render()
    {
        return view('livewire.pages.order-confirmation-page');
    }
}

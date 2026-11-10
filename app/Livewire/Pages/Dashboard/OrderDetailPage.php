<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderDetailPage extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 403);

        $order->load(['items', 'review']);
        $this->order = $order;
    }

    public function render()
    {
        return view('livewire.pages.dashboard.order-detail-page', [
            'statusLabels' => [
                'pending_payment' => __('Pending payment'),
                'paid' => __('Paid'),
                'fulfilled' => __('Fulfilled'),
                'cancelled' => __('Cancelled'),
            ],
        ])->title(__('Order details | ROUMDOUL'));
    }
}

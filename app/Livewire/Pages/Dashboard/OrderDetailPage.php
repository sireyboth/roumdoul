<?php

namespace App\Livewire\Pages\Dashboard;

use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('លម្អិតការបញ្ជាទិញ | ROUMDOUL')]
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
            'statusLabels' => OrderForm::STATUSES,
        ]);
    }
}

<?php

namespace App\Livewire\Pages;

use App\Models\Order;
use App\Models\Review;
use App\Rules\NoProfanity;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('បញ្ជាក់ការបញ្ជាទិញ | ROUMDOUL')]
class OrderConfirmationPage extends Component
{
    public Order $order;

    public int $rating = 5;

    public string $comment = '';

    public bool $reviewSubmitted = false;

    public function mount(Order $order): void
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 403);

        $order->load(['items', 'review']);
        $this->order = $order;
        $this->reviewSubmitted = $order->review !== null;
    }

    public function submitReview(): void
    {
        if ($this->order->review) {
            return;
        }

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000', new NoProfanity],
        ]);

        Review::create([
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer_name,
            'rating' => $this->rating,
            'comment' => $this->comment ?: null,
        ]);

        $this->reviewSubmitted = true;
    }

    public function render()
    {
        return view('livewire.pages.order-confirmation-page');
    }
}

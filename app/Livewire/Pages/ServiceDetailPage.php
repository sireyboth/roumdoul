<?php

namespace App\Livewire\Pages;

use App\Models\Service;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ServiceDetailPage extends Component
{
    public Service $service;

    public ?int $selectedPlanId = null;

    public int $quantity = 1;

    public bool $added = false;

    public function mount(Service $service): void
    {
        $service->load(['category', 'plans']);
        $this->service = $service;
        $this->selectedPlanId = $service->plans->first()?->id;
    }

    public function addToCart(CartService $cart): void
    {
        if (! $this->service->in_stock) {
            return;
        }

        $cart->add($this->service->id, $this->selectedPlanId, $this->quantity);
        $this->added = true;
        $this->dispatch('cart-updated');
        $this->dispatch('cart-added');
    }

    #[Title('ROUMDOUL')]
    public function render()
    {
        return view('livewire.pages.service-detail-page', [
            'selectedPlan' => $this->service->plans->firstWhere('id', $this->selectedPlanId),
            'relatedServices' => Service::with(['category', 'plans'])
                ->where('category_id', $this->service->category_id)
                ->where('id', '!=', $this->service->id)
                ->where('is_active', true)
                ->limit(4)
                ->get(),
        ]);
    }
}

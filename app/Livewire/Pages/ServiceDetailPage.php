<?php

namespace App\Livewire\Pages;

use App\Models\Service;
use App\Services\CartService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
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
        // A product turned off in admin is hidden from customers (admins can still preview it).
        abort_unless($service->is_active || auth('web')->check(), 404);

        $service->load(['category', 'plans']);
        $this->service = $service;
        $this->selectedPlanId = $service->plans->firstWhere('in_stock', true)?->id ?? $service->plans->first()?->id;
    }

    public function addToCart(CartService $cart): void
    {
        // selectedPlanId and quantity come from the browser, so re-check both: the plan must be one
        // of this service's own plans (never a cheaper plan of another product), and the quantity
        // must be positive (a negative line would subtract from the order total).
        $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY],
        ]);

        $selectedPlan = $this->service->plans->firstWhere('id', $this->selectedPlanId);

        if ($this->service->plans->isNotEmpty() ? ! $selectedPlan : $this->selectedPlanId !== null) {
            return;
        }

        if (! $this->service->in_stock || ($selectedPlan && ! $selectedPlan->in_stock)) {
            return;
        }

        $cart->add($this->service->id, $this->selectedPlanId, $this->quantity);
        $this->added = true;
        $this->dispatch('cart-updated');
        $this->dispatch('cart-added');
    }

    public function render()
    {
        $image = $this->service->image_path ?: ($this->service->gallery_images[0] ?? null);

        return view('livewire.pages.service-detail-page', [
            'selectedPlan' => $this->service->plans->firstWhere('id', $this->selectedPlanId),
            // invitation templates get a hint under the quantity: each one is a separate invitation
            'isInvitation' => $this->service->invitationTemplates()->exists(),
            'relatedServices' => Service::with(['category', 'plans'])
                ->where('category_id', $this->service->category_id)
                ->where('id', '!=', $this->service->id)
                ->where('is_active', true)
                ->limit(4)
                ->get(),
        ])
            ->title($this->service->name_en.' | ROUMDOUL')
            ->layoutData([
                'description' => $this->service->short_description ?: $this->service->description,
                'ogImage' => $image ? Storage::disk('s3')->url($image) : null,
            ]);
    }
}

<?php

namespace App\Livewire\Components;

use App\Models\ContactInquiry;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ContactPageForm extends Component
{
    public string $full_name = '';

    public string $phone = '';

    public string $email = '';

    public string $service_needed = '';

    public string $message = '';

    // Honeypot: real visitors never see or fill this field; bots that
    // auto-fill every input will, so a non-empty value marks spam.
    public string $website = '';

    public bool $submitted = false;

    public function mount(string $initialService = '', string $initialMessage = ''): void
    {
        if (array_key_exists($initialService, $this->services())) {
            $this->service_needed = $initialService;
        }

        $this->message = $initialMessage;
    }

    public function services(): array
    {
        return [
            'order' => __('Help with an order'),
            'payment' => __('Payment question'),
            'product' => __('Product question'),
            'partnership' => __('Partnership / Reseller'),
            'other' => __('Other'),
        ];
    }

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'service_needed' => ['required', 'in:'.implode(',', array_keys($this->services()))],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function submit(TelegramNotifier $telegram): void
    {
        if (filled($this->website)) {
            // Silently pretend it worked so bots don't retry.
            $this->reset(['full_name', 'phone', 'email', 'service_needed', 'message', 'website']);
            $this->submitted = true;

            return;
        }

        // Each message is saved and sent to the Telegram order-alert chat, so cap it per IP
        // (a script could otherwise flood the chat and bury real order alerts).
        $limiterKey = 'contact-message|'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $this->addError('full_name', __('Too many submissions — please try again later.'));

            return;
        }

        $validated = $this->validate();
        RateLimiter::hit($limiterKey, 3600);

        $inquiry = ContactInquiry::create($validated);

        $telegram->sendContactInquiry($inquiry);

        $this->reset(['full_name', 'phone', 'email', 'service_needed', 'message']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.components.contact-page-form');
    }
}

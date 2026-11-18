<?php

namespace App\Livewire\Components;

use App\Models\ContactSubmission;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ContactForm extends Component
{
    public string $email = '';

    public string $phone = '';

    public string $address = '';

    // Honeypot: real visitors never see or fill this field; bots that
    // auto-fill every input will, so a non-empty value marks spam.
    public string $website = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            // Not required, but still checked for valid format if they do type one —
            // 'nullable' alone wouldn't skip the email-format rule for an empty string
            // (it only skips for a true null), so the format check is inlined as a
            // closure instead of the 'email' rule.
            'email' => ['nullable', 'max:255', function ($attribute, $value, $fail) {
                if ($value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $fail(__('Please enter a valid email address.'));
                }
            }],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    public function submit(TelegramNotifier $telegram): void
    {
        if (filled($this->website)) {
            // Silently pretend it worked so bots don't retry.
            $this->reset(['email', 'phone', 'address', 'website']);
            $this->submitted = true;

            return;
        }

        // Each message is saved and sent to the Telegram order-alert chat, so cap it per IP
        // (a script could otherwise flood the chat and bury real order alerts).
        $limiterKey = 'contact-callback|'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $this->addError('phone', __('Too many submissions — please try again later.'));

            return;
        }

        $validated = $this->validate();
        RateLimiter::hit($limiterKey, 3600);

        $submission = ContactSubmission::create($validated);

        $telegram->sendContactSubmission($submission);

        $this->reset(['email', 'phone', 'address']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.components.contact-form');
    }
}

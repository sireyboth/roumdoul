<?php

namespace App\Livewire\Components;

use App\Models\ContactSubmission;
use App\Services\TelegramNotifier;
use Livewire\Component;

class ContactForm extends Component
{
    public string $email = '';

    public string $phone = '';

    public string $address = '';

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
                    $fail('សូមបញ្ចូលអ៊ីមែលដែលត្រឹមត្រូវ។');
                }
            }],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    public function submit(TelegramNotifier $telegram): void
    {
        $validated = $this->validate();

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

<?php

namespace App\Livewire\Components;

use App\Models\ContactSubmission;
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
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate();

        ContactSubmission::create($validated);

        $this->reset(['email', 'phone', 'address']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.components.contact-form');
    }
}

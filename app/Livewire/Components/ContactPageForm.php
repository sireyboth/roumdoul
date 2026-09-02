<?php

namespace App\Livewire\Components;

use App\Models\ContactInquiry;
use App\Services\TelegramNotifier;
use Livewire\Component;

class ContactPageForm extends Component
{
    public string $full_name = '';

    public string $phone = '';

    public string $email = '';

    public string $service_needed = '';

    public string $message = '';

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
            'order' => 'ជំនួយអំពីការបញ្ជាទិញ',
            'payment' => 'សំណួរអំពីការទូទាត់ប្រាក់',
            'product' => 'សំណួរអំពីផលិតផល',
            'partnership' => 'ភាពជាដៃគូ / ភ្នាក់ងារចែកចាយ',
            'other' => 'ផ្សេងៗ',
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
        $validated = $this->validate();

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

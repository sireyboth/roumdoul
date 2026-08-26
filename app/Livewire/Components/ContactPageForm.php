<?php

namespace App\Livewire\Components;

use App\Models\ContactInquiry;
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
            'commercial' => 'សន្តិសុខអាជីវកម្ម និងរោងចក្រ',
            'events' => 'សន្តិសុខព្រឹត្តិការណ៍ និងការគ្រប់គ្រងហ្វូងមនុស្ស',
            'residential' => 'ការល្បាតលំនៅដ្ឋាន និងបុរី',
            'executive' => 'ការការពារ VIP និងអ្នកគ្រប់គ្រង',
            'cctv' => 'ការគាំទ្រប្រព័ន្ធឃ្លាំមើល CCTV',
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

    public function submit(): void
    {
        $validated = $this->validate();

        ContactInquiry::create($validated);

        $this->reset(['full_name', 'phone', 'email', 'service_needed', 'message']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.components.contact-page-form');
    }
}

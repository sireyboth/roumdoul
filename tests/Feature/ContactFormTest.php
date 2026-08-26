<?php

namespace Tests\Feature;

use App\Livewire\Components\ContactForm;
use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_valid_input(): void
    {
        Livewire::test(ContactForm::class)
            ->set('email', 'not-an-email')
            ->set('phone', '')
            ->set('address', '')
            ->call('submit')
            ->assertHasErrors(['email', 'phone', 'address']);

        $this->assertSame(0, ContactSubmission::count());
    }

    public function test_it_stores_a_valid_submission(): void
    {
        Livewire::test(ContactForm::class)
            ->set('email', 'customer@example.com')
            ->set('phone', '012 345 678')
            ->set('address', 'Phnom Penh')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('contact_submissions', [
            'email' => 'customer@example.com',
            'phone' => '012 345 678',
            'address' => 'Phnom Penh',
        ]);
    }
}

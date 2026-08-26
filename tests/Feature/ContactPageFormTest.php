<?php

namespace Tests\Feature;

use App\Livewire\Components\ContactPageForm;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_valid_input(): void
    {
        Livewire::test(ContactPageForm::class)
            ->set('full_name', '')
            ->set('phone', '')
            ->set('email', 'not-an-email')
            ->set('service_needed', 'not-a-real-option')
            ->call('submit')
            ->assertHasErrors(['full_name', 'phone', 'email', 'service_needed']);

        $this->assertSame(0, ContactInquiry::count());
    }

    public function test_it_stores_a_valid_submission(): void
    {
        Livewire::test(ContactPageForm::class)
            ->set('full_name', 'Sokha Chan')
            ->set('phone', '012 345 678')
            ->set('email', 'sokha@example.com')
            ->set('service_needed', 'commercial')
            ->set('message', 'Need coverage for a 5-story office building.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('contact_inquiries', [
            'full_name' => 'Sokha Chan',
            'phone' => '012 345 678',
            'email' => 'sokha@example.com',
            'service_needed' => 'commercial',
        ]);
    }

    public function test_it_prefills_from_query_params(): void
    {
        Livewire::test(ContactPageForm::class, [
            'initialService' => 'residential',
            'initialMessage' => 'Pre-filled from the estimator.',
        ])
            ->assertSet('service_needed', 'residential')
            ->assertSet('message', 'Pre-filled from the estimator.');
    }

    public function test_it_ignores_an_invalid_prefilled_service(): void
    {
        Livewire::test(ContactPageForm::class, [
            'initialService' => 'not-a-real-option',
        ])
            ->assertSet('service_needed', '');
    }
}

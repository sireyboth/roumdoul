<?php

namespace Tests\Feature;

use App\Livewire\Components\ContactPageForm;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

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
            ->set('service_needed', 'order')
            ->set('message', 'Need help with my last order.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('contact_inquiries', [
            'full_name' => 'Sokha Chan',
            'phone' => '012 345 678',
            'email' => 'sokha@example.com',
            'service_needed' => 'order',
        ]);
    }

    public function test_it_notifies_telegram_when_configured(): void
    {
        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.orders_chat_id' => '12345']);

        Livewire::test(ContactPageForm::class)
            ->set('full_name', 'Sokha Chan')
            ->set('phone', '012 345 678')
            ->set('email', 'sokha@example.com')
            ->set('service_needed', 'order')
            ->set('message', 'Need help with my last order.')
            ->call('submit')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/bottest-token/sendMessage')
                && str_contains($request['text'], 'Sokha Chan')
                && str_contains($request['text'], 'Need help with my last order.');
        });
    }

    public function test_it_prefills_from_query_params(): void
    {
        Livewire::test(ContactPageForm::class, [
            'initialService' => 'partnership',
            'initialMessage' => 'Interested in becoming a reseller.',
        ])
            ->assertSet('service_needed', 'partnership')
            ->assertSet('message', 'Interested in becoming a reseller.');
    }

    public function test_it_ignores_an_invalid_prefilled_service(): void
    {
        Livewire::test(ContactPageForm::class, [
            'initialService' => 'not-a-real-option',
        ])
            ->assertSet('service_needed', '');
    }
}

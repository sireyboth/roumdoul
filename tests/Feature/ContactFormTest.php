<?php

namespace Tests\Feature;

use App\Livewire\Components\ContactForm;
use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

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

    public function test_email_is_optional(): void
    {
        Livewire::test(ContactForm::class)
            ->set('email', '')
            ->set('phone', '012 345 678')
            ->set('address', 'Phnom Penh')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('contact_submissions', [
            'email' => '',
            'phone' => '012 345 678',
            'address' => 'Phnom Penh',
        ]);
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

    public function test_it_notifies_telegram_when_configured(): void
    {
        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.orders_chat_id' => '12345']);

        Livewire::test(ContactForm::class)
            ->set('email', 'customer@example.com')
            ->set('phone', '012 345 678')
            ->set('address', 'Phnom Penh')
            ->call('submit')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/bottest-token/sendMessage')
                && str_contains($request['text'], 'customer@example.com')
                && str_contains($request['text'], 'Phnom Penh');
        });
    }
}

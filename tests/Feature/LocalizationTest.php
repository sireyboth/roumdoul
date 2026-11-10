<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private const KHMER_GUEST = 'ឯកឧត្តម ស៊ុន វណ្ណារិទ្ធ';

    public function test_the_default_locale_is_khmer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="km">', false);

        $this->assertSame('km', App::getLocale());
    }

    public function test_an_unsupported_session_locale_falls_back_to_khmer(): void
    {
        $this->withSession([SetLocale::SESSION_KEY => 'fr'])
            ->get('/')
            ->assertSee('<html lang="km">', false);
    }

    public function test_switching_language_stores_it_in_the_session_and_redirects_back(): void
    {
        $this->from(url('/shop'))
            ->get('/lang/en')
            ->assertRedirect(url('/shop'))
            ->assertSessionHas(SetLocale::SESSION_KEY, 'en');

        $this->get('/shop')->assertSee('<html lang="en">', false);

        $this->get('/lang/km')->assertSessionHas(SetLocale::SESSION_KEY, 'km');
        $this->get('/shop')->assertSee('<html lang="km">', false);
    }

    public function test_switching_language_rejects_unknown_locales(): void
    {
        $this->get('/lang/fr')->assertNotFound();
    }

    public function test_switching_language_never_redirects_off_site(): void
    {
        $this->get('/lang/en', ['referer' => 'https://evil.example/phish'])
            ->assertRedirect(url('/'));
    }

    public function test_switching_language_expires_old_google_translate_cookies(): void
    {
        $response = $this->get('/lang/km', ['referer' => 'http://roumdoul.com/']);

        $expired = collect($response->headers->getCookies())
            ->filter(fn ($cookie) => $cookie->getName() === 'googtrans' && $cookie->isCleared());

        $domains = $expired->map(fn ($cookie) => $cookie->getDomain())->all();

        // the test host is "localhost": host-only plus the host itself
        $this->assertContains(null, $domains);
        $this->assertContains('localhost', $domains);
    }

    public function test_google_translate_cookie_domains_cover_the_apex_domain(): void
    {
        $response = $this->get('http://www.roumdoul.com/lang/km');

        $domains = collect($response->headers->getCookies())
            ->filter(fn ($cookie) => $cookie->getName() === 'googtrans' && $cookie->isCleared())
            ->map(fn ($cookie) => $cookie->getDomain())
            ->all();

        $this->assertContains('roumdoul.com', $domains);
        $this->assertContains('.roumdoul.com', $domains);
        $this->assertContains('www.roumdoul.com', $domains);
        $this->assertNotContains('com', $domains);
    }

    public function test_html_lang_matches_the_chosen_language_in_every_layout(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        foreach (['km', 'en'] as $locale) {
            $this->withSession([SetLocale::SESSION_KEY => $locale]);

            // storefront layout
            $this->get('/')->assertSee("<html lang=\"{$locale}\">", false);
            // dashboard (storefront layout) and invitation workspace layout
            $this->actingAs($customer, 'customer')->get('/dashboard')->assertSee("<html lang=\"{$locale}\">", false);
            $this->actingAs($customer, 'customer')->get('/dashboard/invitations/'.$invitation->id)->assertSee("<html lang=\"{$locale}\">", false);
        }
    }

    public function test_pages_make_no_request_to_google_translate(): void
    {
        $this->withSession([SetLocale::SESSION_KEY => 'en'])
            ->get('/')
            ->assertDontSee('translate.google', false)
            ->assertDontSee('googtrans', false)
            ->assertDontSee('goog-te', false);
    }

    public function test_livewire_updates_run_through_the_locale_middleware(): void
    {
        $update = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => str_contains((string) $route->getName(), 'livewire.update')
                || str_ends_with($route->uri(), 'livewire/update')
                || str_contains($route->uri(), '/update'));

        $this->assertNotNull($update, 'Livewire update route not found');
        $this->assertContains('web', $update->gatherMiddleware());
        $this->assertContains(SetLocale::class, app('router')->getMiddlewareGroups()['web']);
    }

    public function test_a_khmer_guest_name_is_unchanged_in_english_mode(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->recipients()->create(['recipient_name' => self::KHMER_GUEST]);

        App::setLocale('en');

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'guests')
            ->assertSee(self::KHMER_GUEST)
            ->assertDontSee('His Excellency');

        $this->withSession([SetLocale::SESSION_KEY => 'en'])
            ->actingAs($customer, 'customer')
            ->get('/dashboard/invitations/'.$invitation->id.'?tab=guests')
            ->assertSee('<html lang="en">', false)
            ->assertSee(self::KHMER_GUEST);
    }

    public function test_adding_a_guest_stores_the_typed_khmer_name_as_is_in_english_mode(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        App::setLocale('en');

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('newRecipientName', self::KHMER_GUEST)
            ->call('addRecipient')
            ->assertDispatched('toast', type: 'success');

        $this->assertSame(self::KHMER_GUEST, $invitation->recipients()->first()->recipient_name);
    }

    public function test_validation_messages_follow_the_chosen_language(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        App::setLocale('km');
        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'guests')
            ->call('addRecipient')
            ->assertHasErrors('newRecipientName')
            ->assertSee('សូមបំពេញ ឈ្មោះភ្ញៀវ។')
            ->assertDispatched('toast', type: 'error', message: 'សូមវាយឈ្មោះភ្ញៀវជាមុនសិន។');

        App::setLocale('en');
        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'guests')
            ->call('addRecipient')
            ->assertSee('The guest name field is required.')
            ->assertDispatched('toast', type: 'error', message: "Please type the guest's name first.");
    }

    public function test_save_toast_follows_the_chosen_language(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name']]);

        App::setLocale('en');
        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.sender_name', 'សុខា')
            ->call('save')
            ->assertDispatched('toast', type: 'success', message: 'Saved! Guests will see the changes immediately.');

        $this->assertSame('សុខា', $invitation->fresh()->field_values['sender_name']);
    }

    public function test_editor_labels_stay_in_one_language_after_saving(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'groom_name']]);

        App::setLocale('km');
        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'details')
            ->set('fieldValues.sender_name', 'សុខា')
            ->call('save')
            ->assertSee(__(InvitationTemplate::FIELD_CATALOG['groom_name']['label']))
            ->assertDontSee("Groom's name")
            ->assertDontSee('Your name (sender)');

        App::setLocale('en');
        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'details')
            ->set('fieldValues.sender_name', 'Sokha')
            ->call('save')
            ->assertSee("Groom's name")
            ->assertSee('Your name (sender)');
    }

    public function test_guest_facing_invitation_pages_stay_khmer_in_english_mode(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $recipient = $invitation->recipients()->create(['recipient_name' => self::KHMER_GUEST]);

        $this->withSession([SetLocale::SESSION_KEY => 'en'])
            ->get(route('invitation.show', [$invitation->slug, $recipient->token]))
            ->assertOk()
            ->assertSee('<html lang="km">', false)
            ->assertSee(self::KHMER_GUEST);
    }

    public function test_the_preview_uses_an_honored_guest_placeholder_not_guest(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer, 'customer')
            ->get(route('dashboard.invitations.preview', $invitation))
            ->assertOk()
            ->assertSee('<html lang="km">', false)
            ->assertSee('ភ្ញៀវកិត្តិយស');

        $this->withSession([SetLocale::SESSION_KEY => 'en'])
            ->actingAs($customer, 'customer')
            ->get(route('dashboard.invitations.preview', $invitation))
            ->assertSee('<html lang="km">', false)
            ->assertSee('Honored guest');
    }

    public function test_every_translation_key_exists_in_both_languages(): void
    {
        $this->artisan('lang:missing')->assertSuccessful();
    }
}

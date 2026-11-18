<?php

namespace Tests\Feature;

use App\Livewire\Components\ContactForm;
use App\Livewire\Components\ContactPageForm;
use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\ContactInquiry;
use App\Models\ContactSubmission;
use App\Models\Customer;
use App\Models\Service;
use App\Services\CartService;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Pre-launch fixes: spam limits, hidden products, link previews, CSV export, login cap, images. */
class LaunchHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    // ---- contact forms ---------------------------------------------------------------------

    protected function fillMessage($component)
    {
        return $component->set('full_name', 'Sokha Chan')->set('phone', '012 345 678')
            ->set('email', 'sokha@example.com')->set('service_needed', 'order');
    }

    public function test_the_contact_message_form_is_limited_to_five_per_hour(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->fillMessage(Livewire::test(ContactPageForm::class))->call('submit')->assertHasNoErrors();
        }

        $this->fillMessage(Livewire::test(ContactPageForm::class))->call('submit')->assertHasErrors('full_name');

        $this->assertSame(5, ContactInquiry::count());
        Http::assertSentCount(5);
    }

    public function test_the_contact_message_honeypot_saves_nothing_and_sends_no_alert(): void
    {
        $this->fillMessage(Livewire::test(ContactPageForm::class))
            ->set('website', 'http://spam.example')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertSame(0, ContactInquiry::count());
        Http::assertNothingSent();
    }

    public function test_the_callback_form_is_limited_and_has_a_honeypot(): void
    {
        Livewire::test(ContactForm::class)->set('phone', '012')->set('address', 'PP')
            ->set('website', 'x')->call('submit')->assertSet('submitted', true);
        $this->assertSame(0, ContactSubmission::count());

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(ContactForm::class)->set('phone', '012')->set('address', 'PP')->call('submit')->assertHasNoErrors();
        }
        Livewire::test(ContactForm::class)->set('phone', '012')->set('address', 'PP')->call('submit')->assertHasErrors('phone');

        $this->assertSame(5, ContactSubmission::count());
    }

    // ---- hidden products -------------------------------------------------------------------

    public function test_an_inactive_product_page_is_404_for_customers(): void
    {
        $service = Service::factory()->create(['is_active' => false]);

        $this->get("/service/{$service->slug}")->assertNotFound();
    }

    public function test_an_inactive_product_already_in_a_cart_is_dropped(): void
    {
        $service = Service::factory()->create(['base_price' => 20]);
        $cart = app(CartService::class);
        $cart->add($service->id, null, 1);

        $service->update(['is_active' => false]);

        $this->assertCount(0, $cart->items());
    }

    // ---- link previews / titles ------------------------------------------------------------

    public function test_product_pages_have_their_own_title_description_and_preview_image(): void
    {
        Storage::fake('s3');
        $service = Service::factory()->create([
            'name_en' => 'Khmer Wedding Invitation',
            'short_description' => 'A beautiful Khmer wedding invitation.',
            'image_path' => 'services/cover.jpg',
        ]);

        $this->get("/service/{$service->slug}")
            ->assertOk()
            ->assertSee('<title>Khmer Wedding Invitation | ROUMDOUL</title>', false)
            ->assertSee('<meta name="description" content="A beautiful Khmer wedding invitation." />', false)
            ->assertSee('services/cover.jpg', false)
            ->assertSee('property="og:title"', false);
    }

    public function test_the_homepage_has_a_default_description(): void
    {
        $this->get('/')->assertOk()->assertSee('<meta name="description"', false)->assertSee('property="og:image"', false);
    }

    public function test_the_demo_preview_image_keeps_a_full_url_as_is(): void
    {
        $html = $this->get('/demo/birthday')->assertOk()->getContent();

        preg_match('/property="og:image" content="([^"]+)"/', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertSame(1, preg_match_all('#https?://#', $m[1]), 'og:image must not be a URL glued onto another URL: '.$m[1]);
    }

    // ---- guest CSV export ------------------------------------------------------------------

    public function test_csv_cells_that_would_run_as_formulas_become_text(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://evil\")", InvitationManagePage::csvSafe('=HYPERLINK("http://evil")'));
        $this->assertSame("'+1", InvitationManagePage::csvSafe('+1'));
        $this->assertSame("'-2", InvitationManagePage::csvSafe('-2'));
        $this->assertSame("'@SUM(A1)", InvitationManagePage::csvSafe('@SUM(A1)'));
        $this->assertSame('សួស្តី', InvitationManagePage::csvSafe('សួស្តី'));
        $this->assertSame('', InvitationManagePage::csvSafe(null));
    }

    // ---- login -----------------------------------------------------------------------------

    public function test_login_is_capped_per_email_even_across_ips(): void
    {
        Customer::factory()->create(['email' => 'dara@example.com', 'password' => Hash::make('password123')]);

        // 20 wrong passwords from other IPs already used up this email's hourly cap
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit('login-email|dara@example.com', 3600);
        }

        Livewire::test(LoginPage::class)
            ->set('email', 'dara@example.com')->set('password', 'password123')
            ->call('login')->assertHasErrors('email');

        $this->assertFalse(Auth::guard('customer')->check());
    }

    // ---- product images --------------------------------------------------------------------

    public function test_a_heavy_png_upload_is_re_encoded_smaller(): void
    {
        Storage::fake('s3');
        // a big, noisy screenshot-like PNG (well over the 400 KB threshold)
        $img = imagecreatetruecolor(2400, 1800);
        for ($i = 0; $i < 300; $i++) {
            imagefilledellipse($img, random_int(0, 2400), random_int(0, 1800), random_int(50, 600), random_int(50, 600), random_int(0, 0xFFFFFF));
        }
        for ($y = 0; $y < 1800; $y += 2) {
            for ($x = 0; $x < 2400; $x += 7) {
                imagesetpixel($img, $x, $y, random_int(0, 0xFFFFFF));
            }
        }
        ob_start();
        imagepng($img);
        Storage::disk('s3')->put('services/heavy.png', ob_get_clean());
        $before = Storage::disk('s3')->size('services/heavy.png');

        $service = Service::factory()->create(['image_path' => 'services/heavy.png']);
        $service->refresh();

        $this->assertNotSame('services/heavy.png', $service->image_path);
        $this->assertStringStartsWith('services/', $service->image_path);
        $this->assertLessThan($before, Storage::disk('s3')->size($service->image_path));
        Storage::disk('s3')->assertMissing('services/heavy.png');
    }

    public function test_a_small_image_is_left_alone(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('services/small.jpg', str_repeat('x', 1000));

        $this->assertNull(app(ImageOptimizer::class)->optimizeStored('services/small.jpg'));
    }
}

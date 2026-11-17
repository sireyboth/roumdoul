<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Notifications\InvitationExpiringNotification;
use App\Notifications\OrderPaidNotification;
use App\Services\InvitationAddons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationAddonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Notification::fake();
    }

    /** A paid invitation on a plan that sells every add-on. */
    private function invitation(array $plan = [], array $invitation = []): Invitation
    {
        $service = Service::factory()->create();
        $basic = ServicePlan::factory()->create(array_merge([
            'service_id' => $service->id, 'label' => 'Basic', 'price' => 10, 'max_recipients' => 100, 'max_gallery_photos' => 10,
            'retention_months' => 3, 'features' => ['photo_gallery'],
            'extra_guest_price' => 0.10, 'extra_photo_price' => 0.50, 'feature_prices' => ['rsvp_enabled' => 2],
            'extend_price' => 3, 'extend_months' => 6,
        ], $plan));
        $template = InvitationTemplate::factory()->create([
            'service_id' => $service->id,
            'fields' => ['sender_name', 'photo_gallery', 'rsvp_enabled', 'countdown_enabled'],
        ]);

        return Invitation::factory()->create(array_merge([
            'invitation_template_id' => $template->id,
            'service_plan_id' => $basic->id,
            'max_recipients' => 100,
            'max_gallery_photos' => 10,
            'features' => ['photo_gallery'],
            'expires_at' => now()->addMonth(),
        ], $invitation));
    }

    private function pay(Order $order): void
    {
        $order->markPaid($order->total, 'TXN-'.$order->id);
    }

    public function test_guest_addon_order_is_priced_on_the_server_and_applied_once_when_paid(): void
    {
        $inv = $this->invitation();
        $order = app(InvitationAddons::class)->order($inv, [['type' => 'guests', 'quantity' => 50]]);

        $this->assertSame(Order::TYPE_ADDON, $order->type);
        $this->assertSame($inv->id, $order->invitation_id);
        $this->assertSame('pending_payment', $order->status);
        $this->assertEquals(5.00, (float) $order->total);
        $this->assertSame(100, $inv->fresh()->max_recipients); // nothing until paid

        $this->pay($order);
        $this->assertSame(150, $inv->fresh()->max_recipients);
        $this->assertDatabaseCount('invitations', 1); // an add-on never creates a new invitation

        // paid → pending → paid must not apply it twice
        $order->update(['status' => 'pending_payment']);
        $order->update(['status' => 'paid']);
        $this->assertSame(150, $inv->fresh()->max_recipients);
    }

    public function test_photo_feature_and_extend_addons(): void
    {
        $inv = $this->invitation();
        $expires = $inv->expires_at->copy();

        $order = app(InvitationAddons::class)->order($inv, [
            ['type' => 'photos', 'quantity' => 10],
            ['type' => 'feature', 'key' => 'rsvp_enabled'],
            ['type' => 'extend', 'quantity' => 1],
        ]);
        $this->assertEquals(5 + 2 + 3, (float) $order->total);
        $this->pay($order);

        $inv->refresh();
        $this->assertSame(20, $inv->maxGalleryPhotos());
        $this->assertTrue($inv->fieldUnlocked('rsvp_enabled'));
        $this->assertTrue($inv->expires_at->equalTo($expires->addMonths(6)));
    }

    public function test_upgrade_costs_the_difference_and_keeps_earlier_addons(): void
    {
        $inv = $this->invitation();
        $premium = ServicePlan::factory()->create([
            'service_id' => $inv->plan->service_id, 'label' => 'Premium', 'price' => 25, 'max_recipients' => 300,
            'max_gallery_photos' => 30, 'retention_months' => 12, 'features' => ['photo_gallery', 'countdown_enabled'],
        ]);
        $inv->update(['max_recipients' => 150]); // already bought +50 guests

        $order = app(InvitationAddons::class)->order($inv, [['type' => 'upgrade', 'plan_id' => $premium->id]]);
        $this->assertEquals(15.00, (float) $order->total);
        $this->pay($order);

        $inv->refresh();
        $this->assertSame($premium->id, $inv->service_plan_id);
        $this->assertSame(350, $inv->max_recipients); // 150 + (300 − 100)
        $this->assertSame(30, $inv->maxGalleryPhotos());
        $this->assertTrue($inv->fieldUnlocked('countdown_enabled'));
    }

    public function test_addons_not_sold_on_the_plan_are_refused(): void
    {
        $inv = $this->invitation(['extra_guest_price' => null]);

        $this->expectException(ValidationException::class);
        app(InvitationAddons::class)->order($inv, [['type' => 'guests', 'quantity' => 10]]);
    }

    public function test_editing_a_plan_does_not_change_invitations_already_sold(): void
    {
        $inv = $this->invitation();
        $inv->plan->update(['max_gallery_photos' => 3, 'features' => []]);

        $inv->refresh();
        $this->assertSame(10, $inv->maxGalleryPhotos());
        $this->assertTrue($inv->fieldUnlocked('photo_gallery'));
    }

    public function test_mark_paid_requires_the_full_amount_and_a_unique_transaction_id(): void
    {
        $a = Order::factory()->create(['status' => 'pending_payment', 'total' => 10]);
        $b = Order::factory()->create(['status' => 'pending_payment', 'total' => 10]);

        try {
            $a->markPaid(8, 'TXN-1');
            $this->fail('A short payment was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount_received', $e->errors());
        }

        $a->markPaid(10, 'TXN-1');
        $this->assertSame('paid', $a->fresh()->status);
        $this->assertEquals(10, (float) $a->fresh()->amount_received);

        try {
            $b->markPaid(10, 'TXN-1');
            $this->fail('A reused transaction ID was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('transaction_ref', $e->errors());
        }
        $this->assertSame('pending_payment', $b->fresh()->status);
    }

    public function test_customer_is_emailed_once_when_their_order_is_paid(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 'pending_payment', 'total' => 5]);

        $order->markPaid(5, 'TXN-MAIL');
        $order->update(['status' => 'fulfilled']); // paid → fulfilled: no second email

        Notification::assertSentToTimes($customer, OrderPaidNotification::class, 1);
    }

    public function test_dashboard_buy_addon_creates_the_order_and_opens_its_payment_page(): void
    {
        $inv = $this->invitation();
        $this->actingAs($inv->customer, 'customer');

        Livewire::test(InvitationManagePage::class, ['invitation' => $inv])
            ->set('section', 'addons')
            ->assertSee(__('More guests'))
            ->set('addonQty.guests', 25)
            ->call('buyAddon', 'guests')
            ->assertRedirect(route('order.confirmation', Order::where('type', 'addon')->first()));

        $this->assertEquals(2.50, (float) Order::where('type', 'addon')->first()->total);
    }

    public function test_expiry_reminder_is_sent_once(): void
    {
        $inv = $this->invitation([], ['expires_at' => now()->addDays(10)]);
        $far = $this->invitation([], ['expires_at' => now()->addDays(40)]);

        $this->artisan('invitations:remind-expiring')->assertSuccessful();
        $this->artisan('invitations:remind-expiring')->assertSuccessful();

        Notification::assertSentToTimes($inv->customer, InvitationExpiringNotification::class, 1);
        Notification::assertNotSentTo($far->customer, InvitationExpiringNotification::class);
        $this->assertNotNull($inv->fresh()->expiry_reminded_at);
    }

    public function test_guest_visits_are_counted_but_a_quick_refresh_is_not(): void
    {
        $inv = $this->invitation(['features' => []], ['features' => []]);
        $inv->template->update(['view' => 'invitations.templates.date-asking']);
        $guest = InvitationRecipient::factory()->create(['invitation_id' => $inv->id]);

        $this->get(route('invitation.show', $guest))->assertOk();
        $this->get(route('invitation.show', $guest))->assertOk(); // refresh
        $this->assertSame(1, $guest->fresh()->open_count);

        $this->travel(2)->hours();
        $this->get(route('invitation.show', $guest))->assertOk();
        $guest->refresh();
        $this->assertSame(2, $guest->open_count);
        $this->assertTrue($guest->last_viewed_at->gt($guest->viewed_at));
    }
}

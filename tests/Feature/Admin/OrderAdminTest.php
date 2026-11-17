<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\PromoCodes\Pages\ListPromoCodes;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Actions\ViewAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_every_admin_list_page_renders(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach ([ListOrders::class, ListCategories::class, ListCustomers::class, ListPromoCodes::class, ListReviews::class, ListUsers::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }

    public function test_view_modal_opens_with_items_and_payment_tabs(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create();

        Livewire::test(ListOrders::class)
            ->mountAction(TestAction::make(ViewAction::class)->table($order))
            ->assertActionMounted(TestAction::make(ViewAction::class)->table($order))
            ->assertMountedActionModalSee(['Items', 'Payment', $order->order_number]);
    }

    public function test_mark_as_paid_rejects_a_short_amount(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create(['total' => 20, 'status' => 'pending_payment']);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('markPaid')->table($order), [
                'amount_received' => 15,
                'transaction_ref' => 'ABA-111',
            ])
            ->assertHasActionErrors(['amount_received']);

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->transaction_ref);
    }

    public function test_mark_as_paid_rejects_a_transaction_id_used_on_another_order(): void
    {
        $this->actingAs(User::factory()->create());
        Order::factory()->create(['status' => 'paid', 'transaction_ref' => 'ABA-222']);
        $order = Order::factory()->create(['total' => 20, 'status' => 'pending_payment']);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('markPaid')->table($order), [
                'amount_received' => 20,
                'transaction_ref' => 'ABA-222',
            ])
            ->assertHasActionErrors(['transaction_ref']);

        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_mark_as_paid_requires_a_transaction_id(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create(['total' => 20, 'status' => 'pending_payment']);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('markPaid')->table($order), [
                'amount_received' => 20,
                'transaction_ref' => '',
            ])
            ->assertHasActionErrors(['transaction_ref' => 'required']);

        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_mark_as_paid_with_the_right_amount_and_a_new_transaction_id_marks_the_order_paid(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $order = Order::factory()->create(['total' => 20, 'status' => 'pending_payment']);

        Livewire::test(ListOrders::class)
            ->assertActionVisible(TestAction::make('markPaid')->table($order))
            ->callAction(TestAction::make('markPaid')->table($order), [
                'amount_received' => 20,
                'transaction_ref' => 'ABA-333',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('20.00', $order->amount_received);
        $this->assertSame('ABA-333', $order->transaction_ref);
        $this->assertNotNull($order->paid_at);
        $this->assertSame($admin->id, $order->paid_by);
    }

    public function test_mark_as_paid_is_hidden_once_the_order_is_paid(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create(['status' => 'paid']);

        Livewire::test(ListOrders::class)
            ->assertActionHidden(TestAction::make('markPaid')->table($order));
    }

    public function test_change_status_never_offers_paid_and_only_offers_fulfilled_once_paid(): void
    {
        $this->actingAs(User::factory()->create());
        $pending = Order::factory()->create(['status' => 'pending_payment']);
        $paid = Order::factory()->create(['status' => 'paid']);

        // pending -> paid / fulfilled is refused (that would skip the payment check)
        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('changeStatus')->table($pending), ['status' => 'paid'])
            ->assertHasActionErrors(['status']);
        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('changeStatus')->table($pending), ['status' => 'fulfilled'])
            ->assertHasActionErrors(['status']);
        $this->assertSame('pending_payment', $pending->fresh()->status);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('changeStatus')->table($pending), ['status' => 'cancelled'])
            ->assertHasNoActionErrors();
        $this->assertSame('cancelled', $pending->fresh()->status);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('changeStatus')->table($paid), ['status' => 'fulfilled'])
            ->assertHasNoActionErrors();
        $this->assertSame('fulfilled', $paid->fresh()->status);
    }

    public function test_notes_modal_saves_notes_but_not_status(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create(['status' => 'pending_payment']);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make(EditAction::class)->table($order), ['notes' => 'Called the customer', 'status' => 'paid'])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame('Called the customer', $order->notes);
        $this->assertSame('pending_payment', $order->status);
    }

    public function test_mark_as_paid_also_works_from_inside_the_view_modal(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create(['total' => 12, 'status' => 'pending_payment']);

        Livewire::test(ListOrders::class)
            ->callAction([
                TestAction::make(ViewAction::class)->table($order),
                TestAction::make('markPaid'),
            ], ['amount_received' => 12, 'transaction_ref' => 'WING-9'])
            ->assertHasNoActionErrors();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('WING-9', $order->fresh()->transaction_ref);
    }

    public function test_every_create_and_edit_modal_renders(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        $promo = PromoCode::factory()->create();
        $user = User::factory()->create();
        $order = Order::factory()->create();

        foreach ([
            [ListCategories::class, TestAction::make(CreateAction::class)],
            [ListCategories::class, TestAction::make(EditAction::class)->table($category)],
            [ListCustomers::class, TestAction::make(EditAction::class)->table($customer)],
            [ListPromoCodes::class, TestAction::make(CreateAction::class)],
            [ListPromoCodes::class, TestAction::make(EditAction::class)->table($promo)],
            [ListUsers::class, TestAction::make(CreateAction::class)],
            [ListUsers::class, TestAction::make(EditAction::class)->table($user)],
            [ListOrders::class, TestAction::make(EditAction::class)->table($order)],
            [ListOrders::class, TestAction::make('markPaid')->table($order)],
            [ListOrders::class, TestAction::make('changeStatus')->table($order)],
        ] as [$page, $action]) {
            Livewire::test($page)
                ->mountAction($action)
                ->assertActionMounted($action)
                ->assertSuccessful();
        }
    }
}

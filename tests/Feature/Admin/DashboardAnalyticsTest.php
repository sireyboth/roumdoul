<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ActionCenter;
use App\Filament\Widgets\CompareProducts;
use App\Filament\Widgets\CustomersChart;
use App\Filament\Widgets\InvitationFunnel;
use App\Filament\Widgets\KpiOverview;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\PeakTimesChart;
use App\Filament\Widgets\ProductPerformance;
use App\Filament\Widgets\RevenueTrendChart;
use App\Filament\Widgets\SalesMixChart;
use App\Filament\Widgets\TopCustomers;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Support\Analytics\Advisor;
use App\Support\Analytics\BusinessMetrics;
use App\Support\Analytics\Period;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Service $rose;

    private Service $gemini;

    private Customer $loyal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(12, 0));
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->rose = Service::factory()->create(['name_en' => 'Rose Wedding']);
        $this->gemini = Service::factory()->create(['name_en' => 'Gemini Pro']);
        $template = InvitationTemplate::factory()->create(['service_id' => $this->rose->id]);
        $this->loyal = Customer::factory()->create(['created_at' => now()->subDays(100)]);

        // 40 days ago (previous period for «Last 30 days»): one $10 sale by the loyal customer
        $this->sale($this->rose, 10, now()->subDays(40), $this->loyal);

        // this period: loyal comes back ($20), a new buyer ($30 Gemini), an add-on ($5), one pending, one cancelled
        $order = $this->sale($this->rose, 20, now()->subDays(5), $this->loyal);
        $this->sale($this->gemini, 30, now()->subDays(3), null, 'new@example.com');
        $this->sale($this->rose, 5, now()->subDays(2), $this->loyal, type: Order::TYPE_ADDON, addon: 'guests');
        Order::factory()->create(['status' => 'pending_payment', 'total' => 15, 'created_at' => now()->subDays(1)]);
        Order::factory()->create(['status' => 'cancelled', 'total' => 15, 'created_at' => now()->subDays(1)]);

        // the invitation from the $20 order: 4 letters, 3 opened, 2 replied yes, 1 no
        $invitation = Invitation::factory()->create([
            'customer_id' => $this->loyal->id, 'invitation_template_id' => $template->id, 'order_id' => $order->id,
            'field_values' => ['groom_name' => 'Dara'], 'created_at' => now()->subDays(5),
        ]);
        foreach ([['yes', true], ['yes', true], ['no', true], [null, false]] as [$rsvp, $opened]) {
            InvitationRecipient::factory()->create([
                'invitation_id' => $invitation->id, 'rsvp_status' => $rsvp,
                'viewed_at' => $opened ? now()->subDays(4) : null, 'created_at' => now()->subDays(4),
            ]);
        }
    }

    private function sale(Service $service, float $total, $at, ?Customer $customer = null, ?string $email = null, string $type = Order::TYPE_PURCHASE, ?string $addon = null): Order
    {
        $order = Order::factory()->create([
            'customer_id' => $customer?->id,
            'customer_email' => $customer?->email ?? $email,
            'total' => $total,
            'status' => 'paid',
            'type' => $type,
            'paid_at' => $at,
            'created_at' => $at,
        ]);
        $order->items()->create([
            'service_id' => $service->id, 'service_name_snapshot' => $service->name_en, 'addon_type' => $addon,
            'unit_price' => $total, 'quantity' => 1, 'line_total' => $total,
        ]);

        return $order;
    }

    private function metrics(array $filters = ['period' => '30d']): BusinessMetrics
    {
        return BusinessMetrics::for(Period::fromFilters($filters));
    }

    public function test_the_summary_counts_this_period_only(): void
    {
        $s = $this->metrics()->summary();

        $this->assertEquals(55, $s['revenue']);
        $this->assertEquals(5, $s['addon_revenue']);
        $this->assertSame(3, $s['paid_orders']);
        $this->assertSame(5, $s['placed_orders']);
        $this->assertEquals(60.0, $s['conversion']); // 3 of 5 placed got paid
        $this->assertSame(2, $s['buyers']);
        $this->assertSame(1, $s['returning_buyers']); // the loyal customer bought 40 days ago too
        $this->assertSame(4, $s['letters_sent']);
        $this->assertEquals(75.0, $s['open_rate']);
        $this->assertSame(2, $s['rsvp_yes']);
    }

    public function test_it_compares_with_the_previous_period_or_last_year(): void
    {
        $m = $this->metrics();
        $this->assertEquals(10, $m->previousSummary()['revenue']);
        $this->assertEquals(450.0, BusinessMetrics::change(55, 10));

        $this->assertNull($this->metrics(['period' => '30d', 'compare' => 'none'])->previousSummary());

        $lastYear = Period::fromFilters(['period' => '7d', 'compare' => 'last_year'])->previous();
        $this->assertTrue($lastYear->localStart()->isSameDay(now(Period::timezone())->subDays(6)->subYear()));
    }

    public function test_periods_pick_sensible_chart_buckets(): void
    {
        $this->assertSame('day', Period::fromFilters(['period' => '30d'])->bucket());
        $this->assertSame('week', Period::fromFilters(['period' => '90d'])->bucket());
        $this->assertSame('month', Period::fromFilters(['period' => '12m'])->bucket());
        $this->assertCount(30, Period::fromFilters(['period' => '30d'])->buckets());

        $custom = Period::fromFilters(['period' => 'custom', 'start' => '2026-01-10', 'end' => '2026-01-01']);
        $this->assertSame('2026-01-01 00:00', $custom->localStart()->format('Y-m-d H:i')); // swapped when entered backwards
    }

    public function test_days_follow_phnom_penh_time_not_utc(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 20:00', 'UTC')); // already 03:00 on Nov 24 in Phnom Penh

        $today = Period::fromFilters(['period' => 'today']);
        $this->assertSame('2026-11-24 00:00', $today->localStart()->format('Y-m-d H:i'));
        $this->assertSame('2026-11-23 17:00', $today->start->format('Y-m-d H:i')); // stored/queried in UTC
        $this->assertSame('2026-11-23', Period::day(1)->localStart()->toDateString()); // «yesterday»
    }

    public function test_each_product_gets_its_own_numbers(): void
    {
        $products = $this->metrics()->products()->keyBy('name');

        $this->assertEquals(25, $products['Rose Wedding']['revenue']);
        $this->assertEquals(5, $products['Rose Wedding']['addon_revenue']);
        $this->assertSame(4, $products['Rose Wedding']['letters']);
        $this->assertEquals(150.0, $products['Rose Wedding']['change']); // $10 → $25
        $this->assertEquals(30, $products['Gemini Pro']['revenue']);
        $this->assertSame('Gemini Pro', $this->metrics()->products()->first()['name']); // best seller first
    }

    public function test_sales_mix_funnel_and_buyers(): void
    {
        $m = $this->metrics();

        $this->assertEquals(['purchase' => 50.0, 'guests' => 5.0], $m->salesMix());
        $this->assertSame(['sold' => 1, 'filled' => 1, 'with_guests' => 1, 'letters' => 4, 'opened' => 3, 'replied' => 3, 'attending' => 2], $m->funnel());
        $this->assertSame(1, array_sum($m->buyersSeries()['new']));
        $this->assertSame(2, array_sum($m->buyersSeries()['returning']));
        $this->assertSame('Gemini Pro', $m->products()->first()['name']);
        $this->assertEquals(30, $m->topBuyers()->firstWhere('key', 'c'.$this->loyal->id)['lifetime_spent'] - 5);
    }

    public function test_the_advisor_flags_pending_payments_and_suggests_ideas(): void
    {
        $advisor = new Advisor($this->metrics());

        $titles = collect($advisor->todos())->pluck('title')->implode(' | ');
        $this->assertStringContainsString('1 order waiting for payment', $titles);

        $ideas = collect($advisor->ideas())->pluck('title')->implode(' | ');
        $this->assertStringContainsString('Revenue up', $ideas);
        $this->assertStringContainsString('Gemini Pro', $ideas); // best seller, not featured
    }

    public function test_the_dashboard_and_every_widget_render(): void
    {
        $this->get(Dashboard::getUrl())->assertOk();

        foreach ([
            ActionCenter::class, KpiOverview::class, RevenueTrendChart::class, SalesMixChart::class,
            InvitationFunnel::class, CustomersChart::class, ProductPerformance::class, CompareProducts::class,
            PeakTimesChart::class, OrderStatusChart::class, TopCustomers::class, LatestOrders::class,
        ] as $widget) {
            foreach ([['period' => '30d'], ['period' => '12m', 'compare' => 'last_year'], ['period' => 'today', 'compare' => 'none']] as $filters) {
                Livewire::test($widget, ['pageFilters' => $filters])->assertSuccessful();
            }
        }
    }

    public function test_the_product_table_sorts_and_filters(): void
    {
        Livewire::test(ProductPerformance::class, ['pageFilters' => ['period' => '30d']])
            ->assertSee('Rose Wedding')
            ->assertSee('Gemini Pro')
            ->searchTable('rose')
            ->assertSee('Rose Wedding')
            ->assertDontSee('Gemini Pro');
    }

    public function test_compare_starts_with_the_top_sellers_and_names_the_leader(): void
    {
        Livewire::test(CompareProducts::class, ['pageFilters' => ['period' => '30d']])
            ->assertSet('data.products', [$this->gemini->id, $this->rose->id])
            ->assertSee('«Gemini Pro» leads with $30.00');
    }

    public function test_the_product_table_is_paginated(): void
    {
        Service::factory()->count(10)->create(); // 12 products in all

        $table = Livewire::test(ProductPerformance::class, ['pageFilters' => ['period' => '30d']]);
        $this->assertCount(10, $table->instance()->getTableRecords());
        $this->assertSame(12, $table->instance()->getTableRecords()->total());

        $table->call('gotoPage', 2, 'page');
        $this->assertCount(2, $table->instance()->getTableRecords());
    }

    public function test_the_dashboard_is_split_into_titled_sections(): void
    {
        $response = $this->get(Dashboard::getUrl())->assertOk();

        foreach (Dashboard::SECTIONS as [$title]) {
            $response->assertSee($title);
        }
    }
}

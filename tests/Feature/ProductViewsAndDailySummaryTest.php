<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Support\Analytics\BusinessMetrics;
use App\Support\Analytics\DailySummary;
use App\Support\Analytics\Period;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProductViewsAndDailySummaryTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';

    public function test_a_product_page_visit_is_counted_once_per_visitor_per_day(): void
    {
        $service = Service::factory()->create();

        $this->withHeader('User-Agent', self::BROWSER)->get("/service/{$service->slug}")->assertOk();
        $this->withHeader('User-Agent', self::BROWSER)->get("/service/{$service->slug}")->assertOk(); // refresh
        $this->assertSame(1, DB::table('product_views')->where(['service_id' => $service->id, 'kind' => 'page'])->count());

        $this->travel(1)->days();
        $this->withHeader('User-Agent', self::BROWSER)->get("/service/{$service->slug}");
        $this->assertSame(2, DB::table('product_views')->count());
    }

    public function test_bots_link_previews_and_the_admin_are_not_counted(): void
    {
        $service = Service::factory()->create();

        $this->withHeader('User-Agent', 'TelegramBot (like TwitterBot)')->get("/service/{$service->slug}");
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get("/service/{$service->slug}");
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->withHeader('User-Agent', self::BROWSER)->get("/service/{$service->slug}");

        $this->assertSame(0, DB::table('product_views')->count());
    }

    public function test_opening_a_template_demo_counts_for_its_product(): void
    {
        $service = Service::factory()->create();
        $template = InvitationTemplate::factory()->create(['service_id' => $service->id, 'is_active' => true]);

        $this->withHeader('User-Agent', self::BROWSER)->get("/templates/{$template->slug}/demo");

        $this->assertSame(1, DB::table('product_views')->where(['service_id' => $service->id, 'kind' => 'demo'])->count());
    }

    public function test_visitors_turn_into_a_view_to_order_rate_per_product(): void
    {
        $service = Service::factory()->create();
        foreach (range(1, 20) as $i) {
            DB::table('product_views')->insert(['service_id' => $service->id, 'kind' => 'page', 'visitor' => hash('sha256', (string) $i), 'viewed_on' => today(), 'created_at' => now()]);
        }
        $order = Order::factory()->create(['status' => 'paid', 'total' => 10, 'paid_at' => now()]);
        $order->items()->create(['service_id' => $service->id, 'service_name_snapshot' => 'X', 'unit_price' => 10, 'quantity' => 1, 'line_total' => 10]);

        $m = BusinessMetrics::for(Period::fromFilters(['period' => '7d']));
        $row = $m->products()->firstWhere('id', $service->id);

        $this->assertSame(20, $row['visitors']);
        $this->assertEquals(5.0, $row['conversion']); // 1 order ÷ 20 visitors
        $this->assertSame(20, $m->summary()['visitors']);
    }

    public function test_the_daily_summary_reports_yesterday_and_the_to_dos(): void
    {
        $this->travelTo(now(Period::timezone())->setTime(8, 0));
        $order = Order::factory()->create(['status' => 'paid', 'total' => 42, 'paid_at' => now()->subDay(), 'created_at' => now()->subDay()]);
        Order::factory()->create(['status' => 'pending_payment', 'total' => 7]);

        $text = app(DailySummary::class)->message();

        $this->assertStringContainsString('Revenue: <b>$42.00</b>', $text);
        $this->assertStringContainsString('1 order waiting for payment', $text);
        $this->assertStringContainsString('(yesterday)', $text);
    }

    public function test_the_command_sends_it_to_telegram(): void
    {
        config(['services.telegram.bot_token' => 'TOKEN', 'services.telegram.orders_chat_id' => '111', 'services.telegram.summary_chat_id' => '222']);
        Http::fake();

        $this->artisan('report:daily')->assertSuccessful();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'botTOKEN/sendMessage')
            && $request['chat_id'] === '222'
            && str_contains($request['text'], 'daily summary'));
    }

    public function test_without_telegram_set_up_the_command_says_so(): void
    {
        config(['services.telegram.bot_token' => null]);
        Http::fake();

        $this->artisan('report:daily')->assertFailed();
        Http::assertNothingSent();
    }

    public function test_the_dashboard_button_sends_the_summary(): void
    {
        config(['services.telegram.bot_token' => 'TOKEN', 'services.telegram.orders_chat_id' => '111']);
        Http::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(Dashboard::class)
            ->callAction('sendSummary')
            ->assertNotified('Summary sent to Telegram');

        Http::assertSentCount(1);
    }

    public function test_it_is_scheduled_every_morning_on_phnom_penh_time(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'report:daily'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * *', $event->expression);
        $this->assertSame('Asia/Phnom_Penh', $event->timezone);
    }
}

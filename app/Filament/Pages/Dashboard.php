<?php

namespace App\Filament\Pages;

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
use App\Services\TelegramNotifier;
use App\Support\Analytics\DailySummary;
use App\Support\Analytics\Period;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The owner's dashboard: pick a period (and what to compare it with) at the top — every widget below follows.
 * Order: what to do now → headline numbers → money → invitations & customers → each product → compare
 * products → when people buy → best customers → latest orders. Numbers: App\Support\Analytics\BusinessMetrics.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getSubheading(): ?string
    {
        $period = Period::fromFilters($this->filters);

        return $period->label().($period->hasComparison() ? ' · compared '.str_replace('vs ', 'with ', $period->compareLabel()) : '');
    }

    /** «Send summary to Telegram»: the morning message, now — to test it or get it on demand. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendSummary')
                ->label('Send summary to Telegram')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Send the daily summary now?')
                ->modalDescription('Sends yesterday\'s numbers, the month so far and your to-dos to Telegram. It also goes out by itself every morning.')
                ->modalSubmitActionLabel('Send')
                ->action(function (DailySummary $summary, TelegramNotifier $telegram): void {
                    $telegram->sendDailySummary($summary->message())
                        ? Notification::make()->title('Summary sent to Telegram')->success()->send()
                        : Notification::make()->title('Not sent')->body('Telegram isn\'t set up — add TELEGRAM_BOT_TOKEN and TELEGRAM_ORDERS_CHAT_ID to .env.')->danger()->send();
                }),
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                ->components([
                    Select::make('period')
                        ->label('Period')
                        ->options(Period::PRESETS)
                        ->default('30d')
                        ->selectablePlaceholder(false)
                        ->native(false),
                    Select::make('compare')
                        ->label('Compare with')
                        ->options(Period::COMPARE)
                        ->default('previous')
                        ->selectablePlaceholder(false)
                        ->native(false),
                    DatePicker::make('start')
                        ->label('From')
                        ->native(false)
                        ->maxDate(now())
                        ->visible(fn (callable $get) => $get('period') === 'custom'),
                    DatePicker::make('end')
                        ->label('To')
                        ->native(false)
                        ->maxDate(now())
                        ->visible(fn (callable $get) => $get('period') === 'custom'),
                ]),
        ]);
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 6];
    }

    /**
     * The dashboard in titled sections, top to bottom in the order a solo owner checks things.
     * key => [title, description, icon, widgets]. Each section folds away (remembered in the browser).
     */
    public const SECTIONS = [
        'attention' => ['Needs your attention', 'What is waiting on you, and ideas from your numbers.', 'heroicon-o-bell-alert', [ActionCenter::class]],
        'overview' => ['Overview', 'The headline numbers for this period.', 'heroicon-o-presentation-chart-line', [KpiOverview::class]],
        'sales' => ['Sales', 'How much you earned and where it came from.', 'heroicon-o-banknotes', [RevenueTrendChart::class, SalesMixChart::class]],
        'customers' => ['Customers & invitations', 'Who buys, and how guests respond to the invitations.', 'heroicon-o-users', [InvitationFunnel::class, CustomersChart::class]],
        'products' => ['Products', 'Every product\'s numbers, and a side-by-side comparison.', 'heroicon-o-squares-2x2', [ProductPerformance::class, CompareProducts::class]],
        'timing' => ['Orders & timing', 'When customers order, and how orders end up.', 'heroicon-o-clock', [PeakTimesChart::class, OrderStatusChart::class]],
        'people' => ['Best customers & latest orders', 'Your VIPs and the newest orders.', 'heroicon-o-star', [TopCustomers::class, LatestOrders::class]],
    ];

    public function getWidgets(): array
    {
        return collect(self::SECTIONS)->flatMap(fn (array $section) => $section[3])->all();
    }

    public function getWidgetsContentComponent(): Component
    {
        return Group::make(fn (): array => collect(self::SECTIONS)
            ->map(fn (array $section, string $key) => Section::make($section[0])
                ->description($section[1])
                ->icon($section[2])
                ->id("dashboard-{$key}")
                ->contained(false)
                ->collapsible()
                ->persistCollapsed()
                ->extraAttributes(['class' => 'rd-dash-section'])
                ->schema([
                    Grid::make($this->getColumns())->schema($this->getWidgetsSchemaComponents($section[3])),
                ]))
            ->values()
            ->all())
            ->extraAttributes(['class' => 'rd-dash']);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Who spent the most in the period, with their lifetime value — your VIPs to thank. */
class TopCustomers extends TableWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Best customers')
            ->description('Top 10 by spend in this period. Lifetime = everything they ever paid.')
            ->records(fn () => $this->metrics()->topBuyers(10)->map(fn ($row) => ['__key' => $row['key'], ...$row]))
            ->paginated(false)
            ->emptyStateHeading('No paid orders in this period')
            ->columns([
                TextColumn::make('name')->label('Customer')->weight('semibold')
                    ->description(fn (array $record) => $record['contact'])
                    ->url(fn (array $record) => $record['customer_id'] ? CustomerResource::getUrl('index', ['search' => $record['contact']]) : null),
                TextColumn::make('orders')->label('Orders')->numeric(),
                TextColumn::make('spent')->label('Spent')->money('USD'),
                TextColumn::make('lifetime_orders')->label('Lifetime orders')->numeric()
                    ->badge()->color(fn ($state) => $state > 1 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 1 ? $state.' · returning' : $state),
                TextColumn::make('lifetime_spent')->label('Lifetime spent')->money('USD'),
                TextColumn::make('letters')->label('Letters sent')->numeric(),
                TextColumn::make('last_order')->label('Last order')->since(),
            ]);
    }
}

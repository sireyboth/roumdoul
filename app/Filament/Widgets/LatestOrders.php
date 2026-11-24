<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Latest orders';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->latest()->limit(6))
            ->paginated(false)
            ->columns([
                TextColumn::make('order_number')->label('Order #'),
                TextColumn::make('customer_name')->label('Customer'),
                TextColumn::make('total')->money(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => OrderForm::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => OrderForm::statusColor($state)),
                TextColumn::make('created_at')->label('Placed')->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    // opens the order's View modal on the Orders list
                    ->url(fn (Order $record) => OrderResource::getUrl('index', ['tableAction' => 'view', 'tableActionRecord' => $record->getKey()])),
            ]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Latest Orders';

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
                    ->color(fn (string $state) => match ($state) {
                        'paid', 'fulfilled' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Placed')->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Order $record) => "/admin/orders/{$record->id}"),
            ]);
    }
}

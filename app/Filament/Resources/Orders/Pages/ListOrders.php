<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ManageRecords;

class ListOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        // Orders come from checkout (or the customer's «Add more» panel) — never created by hand.
        return [];
    }
}

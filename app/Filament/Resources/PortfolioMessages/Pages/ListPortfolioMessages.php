<?php

namespace App\Filament\Resources\PortfolioMessages\Pages;

use App\Filament\Resources\PortfolioMessages\PortfolioMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListPortfolioMessages extends ListRecords
{
    protected static string $resource = PortfolioMessageResource::class;
}

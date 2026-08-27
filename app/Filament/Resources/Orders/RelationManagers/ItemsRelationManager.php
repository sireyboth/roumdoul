<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('service_name_snapshot')
            ->columns([
                TextColumn::make('service_name_snapshot')
                    ->label('Service'),
                TextColumn::make('plan_label_snapshot')
                    ->label('Plan')
                    ->placeholder('—'),
                TextColumn::make('unit_price')
                    ->money(),
                TextColumn::make('quantity'),
                TextColumn::make('line_total')
                    ->money(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

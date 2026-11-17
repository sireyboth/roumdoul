<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereDoesntHave('invitationTemplates'))
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('s3')
                    ->circular()
                    ->imageSize(48),
                TextColumn::make('name_en')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->name_km),
                TextColumn::make('category.name_en')
                    ->label('Category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('base_price')
                    ->label('Base price')
                    ->money()
                    ->sortable(),
                TextColumn::make('discount_value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($record) => $record->hasDiscount()
                        ? ($record->discount_type === 'percentage'
                            ? '-'.rtrim(rtrim(number_format($record->discount_value, 2), '0'), '.').'%'
                            : '-$'.number_format($record->discount_value, 2))
                        : '—')
                    ->badge()
                    ->color(fn ($record) => $record->hasDiscount() ? 'danger' : 'gray'),
                TextColumn::make('plans_count')
                    ->label('Plans')
                    ->counts('plans')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('in_stock')
                    ->label('In stock')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en'),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                TernaryFilter::make('in_stock')
                    ->label('In stock'),
            ])
            ->emptyStateIcon(Heroicon::OutlinedShoppingBag)
            ->emptyStateHeading('No services yet')
            ->emptyStateDescription('Click «New service» to add a product to the shop. Digital Invitations are managed under Invitation Templates.')
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::SevenExtraLarge),
                DeleteAction::make()
                    ->modalDescription('The service and its plans are removed from the shop. Past orders keep their details.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

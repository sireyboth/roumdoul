<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Customer feedback';

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('customer_name')->label('Customer'),
            TextEntry::make('order.order_number')->label('Order #'),
            TextEntry::make('rating')
                ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
            TextEntry::make('comment')->placeholder('—')->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count() ?: null;
    }
}

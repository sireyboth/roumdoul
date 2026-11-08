<?php

namespace App\Filament\Resources\PortfolioMessages;

use App\Filament\Resources\PortfolioMessages\Pages\ListPortfolioMessages;
use App\Filament\Resources\PortfolioMessages\Pages\ViewPortfolioMessage;
use App\Models\PortfolioMessage;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only inbox for messages sent through personal portfolio contact forms. */
class PortfolioMessageResource extends Resource
{
    protected static ?string $model = PortfolioMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Portfolio';

    protected static ?string $navigationLabel = 'Messages';

    public static function getNavigationBadge(): ?string
    {
        $unread = PortfolioMessage::whereNull('read_at')->count();

        return $unread ? (string) $unread : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message')
                ->columns(2)
                ->components([
                    TextEntry::make('name'),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('subject')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('message')->columnSpanFull()->prose(),
                    TextEntry::make('portfolio')->badge(),
                    TextEntry::make('locale')->label('Language')->badge(),
                    TextEntry::make('created_at')->label('Received')->dateTime(),
                    TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')->label('Read')->boolean()->getStateUsing(fn (PortfolioMessage $r) => $r->read_at !== null),
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('subject')->limit(50)->placeholder('—'),
                TextColumn::make('portfolio')->badge(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('portfolio')->options(fn () => PortfolioMessage::query()->distinct()->pluck('portfolio', 'portfolio')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortfolioMessages::route('/'),
            'view' => ViewPortfolioMessage::route('/{record}'),
        ];
    }
}

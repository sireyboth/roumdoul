<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

/** The order «View» modal — tabs: Overview · Items · Payment. */
class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Order')
                    ->columnSpanFull()
                    ->tabs([
                        self::overviewTab(),
                        self::itemsTab(),
                        self::paymentTab(),
                    ]),
            ]);
    }

    protected static function overviewTab(): Tab
    {
        return Tab::make('Overview')
            ->icon(Heroicon::OutlinedShoppingCart)
            ->columns(2)
            ->components([
                Section::make('Order')
                    ->columns(3)
                    ->components([
                        TextEntry::make('order_number')->label('Order #')->copyable()->weight(FontWeight::Bold),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => OrderForm::STATUSES[$state] ?? $state)
                            ->color(fn (string $state) => OrderForm::statusColor($state)),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => OrderForm::TYPES[$state] ?? $state)
                            ->color(fn (?string $state) => OrderForm::typeColor($state)),
                        TextEntry::make('total')
                            ->money()
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->money()
                            ->helperText(fn (Order $record) => $record->promo_code ? "Code {$record->promo_code}" : null)
                            ->visible(fn (Order $record) => (float) $record->discount_amount > 0),
                        TextEntry::make('created_at')->label('Placed')->dateTime(),
                        TextEntry::make('invitation_id')
                            ->label('For invitation')
                            ->visible(fn (Order $record) => $record->isAddon())
                            ->state(fn (Order $record) => $record->invitation
                                ? ($record->invitation->template?->name ?? 'Invitation').' — #'.$record->invitation->id
                                    .($record->invitation->customer ? ' ('.$record->invitation->customer->name.')' : '')
                                : 'Invitation #'.$record->invitation_id.' (deleted)')
                            ->url(fn (Order $record) => $record->invitation?->invitation_template_id
                                ? InvitationTemplateResource::getUrl('view', ['record' => $record->invitation->invitation_template_id])
                                : null)
                            ->color('primary')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->columnSpanFull(),
                    ]),
                Section::make('Customer')
                    ->columns(2)
                    ->components([
                        TextEntry::make('customer_name')->label('Name'),
                        TextEntry::make('customer_phone')->label('Phone / Telegram')->copyable()->placeholder('—'),
                        TextEntry::make('customer_email')->label('Email')->copyable()->placeholder('—')->columnSpanFull(),
                        TextEntry::make('notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function itemsTab(): Tab
    {
        return Tab::make('Items')
            ->icon(Heroicon::OutlinedListBullet)
            ->badge(fn (?Order $record) => $record?->items()->count() ?: null)
            ->components([
                RepeatableEntry::make('items')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make('Item'),
                        TableColumn::make('Plan'),
                        TableColumn::make('Add-on'),
                        TableColumn::make('Unit price'),
                        TableColumn::make('Qty'),
                        TableColumn::make('Line total'),
                    ])
                    ->schema([
                        TextEntry::make('service_name_snapshot'),
                        TextEntry::make('plan_label_snapshot')->placeholder('—'),
                        TextEntry::make('addon_type')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(fn (?string $state, OrderItem $record) => (OrderForm::ADDON_TYPES[$state] ?? $state)
                                .(filled($record->addon_value) ? ' · '.$record->addon_value : ''))
                            ->placeholder('—'),
                        TextEntry::make('unit_price')->money(),
                        TextEntry::make('quantity'),
                        TextEntry::make('line_total')->money()->weight(FontWeight::Bold),
                    ]),
            ]);
    }

    protected static function paymentTab(): Tab
    {
        return Tab::make('Payment')
            ->icon(Heroicon::OutlinedBanknotes)
            ->badge(fn (?Order $record) => $record?->paid_at ? 'Checked' : null)
            ->badgeColor('success')
            ->components([
                Section::make()
                    ->columns(3)
                    ->components([
                        TextEntry::make('amount_received')
                            ->label('Amount received')
                            ->money()
                            ->placeholder('Not paid yet — use «Mark as paid» after checking your bank app.'),
                        TextEntry::make('transaction_ref')
                            ->label('Transaction ID')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('paid_at')
                            ->label('Paid at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('paidBy.name')
                            ->label('Checked by')
                            ->placeholder('—'),
                        TextEntry::make('addons_applied_at')
                            ->label('Add-ons applied at')
                            ->dateTime()
                            ->placeholder('Not yet')
                            ->visible(fn (Order $record) => $record->isAddon()),
                    ]),
            ]);
    }
}

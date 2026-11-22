<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Pagination\LengthAwarePaginator;

/** Every product for the period, side by side: money, buyers, invitations, letters — sortable. */
class ProductPerformance extends TableWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Each product')
            ->paginationPageOptions([10, 25, 50])
            ->description(fn () => $this->period()->label().' · click a column to sort · show more columns with the columns button')
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search, ?array $filters, int|string $page, int|string $recordsPerPage) => $this->rows($sortColumn, $sortDirection, $search, $filters, (int) $page, $recordsPerPage))
            ->defaultSort('revenue', 'desc')
            ->searchable()
            ->paginated()
            ->defaultPaginationPageOption(10)
            ->filters([
                SelectFilter::make('kind')
                    ->label('Type')
                    ->options(['invitation' => 'Invitations', 'service' => 'Other services']),
                SelectFilter::make('activity')
                    ->label('Activity')
                    ->options(['selling' => 'Sold this period', 'sleeping' => 'No sale this period']),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->weight('semibold')
                    ->description(fn (array $record) => collect([
                        $record['category'],
                        $record['is_featured'] ? '★ Featured' : null,
                        $record['is_active'] ? null : 'Hidden',
                    ])->filter()->implode(' · '))
                    ->sortable()
                    ->wrap(),
                TextColumn::make('revenue')->label('Revenue')->money('USD')->sortable()
                    ->description(fn (array $record) => $record['share'] > 0 ? $record['share'].'% of total' : null),
                TextColumn::make('change')->label('Change')->sortable()
                    ->state(fn (array $record) => $record['change'])
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').$state.'%')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->tooltip(fn (array $record) => 'Before: $'.number_format($record['prev_revenue'], 2)),
                TextColumn::make('orders')->label('Orders')->numeric()->sortable(),
                TextColumn::make('buyers')->label('Buyers')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visitors')->label('Visitors')->numeric()->sortable()
                    ->tooltip(fn (array $record) => number_format($record['views']).' page views · '.number_format($record['demo_views']).' demos opened'),
                TextColumn::make('demo_views')->label('Demos')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conversion')->label('View → order')->sortable()
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 5 ? 'success' : ($state < 1 ? 'danger' : 'warning')))
                    ->tooltip('New orders ÷ visitors. Many visitors but few orders = check price, photos or description.'),
                TextColumn::make('avg_order')->label('Avg. order')->money('USD')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('addon_revenue')->label('Add-ons')->money('USD')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invitations')->label('Invitations')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('letters')->label('Letters sent')->numeric()->sortable(),
                TextColumn::make('open_rate')->label('Opened')->sortable()
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->placeholder('—'),
                TextColumn::make('attending')->label('Attending')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_sold')->label('Last sale')->since()->sortable()->placeholder('Never')
                    ->color(fn ($state) => $state === null || $state->lt(now()->subDays(60)) ? 'danger' : null),
            ]);
    }

    /** Filtered + sorted rows, one page of them (an array-backed table only paginates when given a paginator). */
    protected function rows(?string $sortColumn, ?string $sortDirection, ?string $search, ?array $filters, int $page, int|string $perPage): LengthAwarePaginator
    {
        $rows = $this->metrics()->products()
            ->map(fn (array $row) => ['__key' => (string) $row['id'], ...$row]);

        if (filled($search)) {
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['name'].' '.$r['category']), mb_strtolower($search)));
        }

        $rows = match ($filters['kind']['value'] ?? null) {
            'invitation' => $rows->where('is_invitation', true),
            'service' => $rows->where('is_invitation', false),
            default => $rows,
        };

        $rows = match ($filters['activity']['value'] ?? null) {
            'selling' => $rows->where('revenue', '>', 0),
            'sleeping' => $rows->where('revenue', '<=', 0),
            default => $rows,
        };

        if ($sortColumn) {
            $rows = $rows->sortBy(fn ($r) => $r[$sortColumn] instanceof \DateTimeInterface ? $r[$sortColumn]->getTimestamp() : ($r[$sortColumn] ?? -INF),
                SORT_REGULAR, $sortDirection === 'desc');
        }

        $rows = $rows->values();
        $perPage = $perPage === 'all' ? max(1, $rows->count()) : max(1, (int) $perPage);
        $page = max(1, min($page, (int) ceil(max(1, $rows->count()) / $perPage)));

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page);
    }
}

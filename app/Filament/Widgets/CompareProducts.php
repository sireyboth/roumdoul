<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

/**
 * Pick 2–4 products and see them head to head for the period: each metric as bars, the leader marked.
 * Starts with the period's top 3 sellers.
 *
 * @property-read Schema $form
 */
class CompareProducts extends Widget implements HasSchemas
{
    use InteractsWithSchemas, UsesDashboardPeriod {
        // both define __get; the page-filters one only aliases the old `$this->filters` name, which we don't use
        InteractsWithSchemas::__get insteadof UsesDashboardPeriod;
    }

    public const COLORS = ['#b02361', '#d9b66a', '#6366f1', '#10b981'];

    /** metric => [label, format] (money | percent | number) */
    public const METRICS = [
        'revenue' => ['Revenue', 'money'],
        'change' => ['Revenue change', 'percent'],
        'orders' => ['Paid orders', 'number'],
        'buyers' => ['Buyers', 'number'],
        'visitors' => ['Visitors', 'number'],
        'demo_views' => ['Demos opened', 'number'],
        'conversion' => ['View → order', 'percent'],
        'avg_order' => ['Avg. order', 'money'],
        'addon_revenue' => ['Add-on revenue', 'money'],
        'invitations' => ['Invitations sold', 'number'],
        'letters' => ['Letters sent', 'number'],
        'open_rate' => ['Letters opened', 'percent'],
        'attending' => ['Guests attending', 'number'],
    ];

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.compare-products';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'products' => $this->metrics()->products()->where('revenue', '>', 0)->take(3)->pluck('id')->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('products')
                    ->hiddenLabel()
                    ->placeholder('Pick products to compare…')
                    ->multiple()
                    ->maxItems(4)
                    ->searchable()
                    ->options(fn () => Service::orderBy('name_en')->pluck('name_en', 'id')->all())
                    ->live(),
            ]);
    }

    protected function getViewData(): array
    {
        $ids = array_map('intval', array_values($this->data['products'] ?? []));
        $products = $ids === []
            ? collect()
            : $this->metrics()->products($ids)->sortBy(fn ($p) => array_search($p['id'], $ids, true))->values();

        $rows = collect(self::METRICS)->map(function (array $meta, string $key) use ($products) {
            $values = $products->pluck($key)->all();
            $numeric = array_filter($values, fn ($v) => $v !== null);
            $max = $numeric ? max(array_map('abs', $numeric)) : 0;
            $best = $numeric && max($numeric) > 0 ? max($numeric) : null;

            return [
                'label' => $meta[0],
                'format' => $meta[1],
                'cells' => array_map(fn ($v) => [
                    'value' => $v,
                    'width' => $v === null || $max <= 0 ? 0 : max(2, round(abs($v) / $max * 100)),
                    'best' => $best !== null && $v === $best && count($values) > 1,
                    'negative' => $v !== null && $v < 0,
                ], $values),
            ];
        })->values();

        $leader = $products->sortByDesc('revenue')->first();
        $runner = $products->sortByDesc('revenue')->skip(1)->first();

        return [
            'products' => $products,
            'rows' => $rows,
            'colors' => self::COLORS,
            'verdict' => $leader && $runner && $leader['revenue'] > 0
                ? '«'.$leader['name'].'» leads with $'.number_format($leader['revenue'], 2)
                    .($runner['revenue'] > 0 ? ' — '.round($leader['revenue'] / $runner['revenue'], 1).'× «'.$runner['name'].'»' : ' — «'.$runner['name'].'» had no sales')
                : null,
        ];
    }

    public static function format(mixed $value, string $format): string
    {
        if ($value === null) {
            return '—';
        }

        return match ($format) {
            'money' => '$'.number_format((float) $value, 2),
            'percent' => rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%',
            default => number_format((float) $value),
        };
    }
}

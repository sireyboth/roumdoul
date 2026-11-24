@php use App\Filament\Widgets\CompareProducts; @endphp
<x-filament-widgets::widget>
  <x-filament::section icon="heroicon-o-scale" heading="Compare products"
    description="Pick up to 4 products and see them head to head for this period. 👑 = the leader on that line.">
    <div class="rd-compare-picker">{{ $this->form }}</div>

    @if ($products->count() < 2)
      <p class="rd-empty">Pick at least two products above to compare them.</p>
    @else
      @if ($verdict)
        <p class="rd-verdict"><x-filament::icon icon="heroicon-m-trophy" class="rd-i-sm" /> {{ $verdict }}</p>
      @endif

      <div class="rd-compare" style="--cols: {{ $products->count() }}">
        <div class="rd-compare-row rd-compare-head">
          <span></span>
          @foreach ($products as $i => $product)
            <span class="rd-compare-name"><i style="background: {{ $colors[$i] }}"></i>{{ $product['name'] }}</span>
          @endforeach
        </div>

        @foreach ($rows as $row)
          <div class="rd-compare-row">
            <span class="rd-compare-metric">{{ $row['label'] }}</span>
            @foreach ($row['cells'] as $i => $cell)
              <div @class(['rd-cell', 'rd-cell-best' => $cell['best']])>
                <span class="rd-cell-value">
                  {{ ($row['format'] === 'percent' && $row['label'] === 'Revenue change' && $cell['value'] > 0) ? '+' : '' }}{{ CompareProducts::format($cell['value'], $row['format']) }}
                  @if ($cell['best']) 👑 @endif
                </span>
                <div class="rd-bar rd-bar-thin"><span style="width: {{ $cell['width'] }}%; background: {{ $cell['negative'] ? '#ef4444' : $colors[$i] }}"></span></div>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    @endif
  </x-filament::section>
</x-filament-widgets::widget>

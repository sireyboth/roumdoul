<x-filament-widgets::widget>
  <x-filament::section icon="heroicon-o-funnel" heading="From visit to guest"
    description="Where customers and guests drop off. % = kept from the step before.">
    <div class="rd-funnel">
      @foreach ($groups as $group => $steps)
        <div class="rd-funnel-group">
          <p class="rd-funnel-title">{{ $group }}</p>
          @php($top = max(1, $steps[0][1]))
          @foreach ($steps as $i => [$label, $value, $hint])
            @php($prev = $i > 0 ? $steps[$i - 1][1] : null)
            <div class="rd-step" title="{{ $hint }}">
              <div class="rd-step-head">
                <span class="rd-step-label">{{ $label }}</span>
                <span class="rd-step-value">
                  {{ number_format($value) }}
                  @if ($prev !== null)
                    @php($rate = $prev > 0 ? round($value / $prev * 100) : null)
                    <span @class(['rd-rate', 'rd-rate-low' => $rate !== null && $rate < 50])>{{ $rate === null ? '—' : $rate.'%' }}</span>
                  @endif
                </span>
              </div>
              <div class="rd-bar"><span style="width: {{ $value > 0 ? max(2, round($value / $top * 100)) : 0 }}%; opacity: {{ 1 - $i * 0.18 }}"></span></div>
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
  </x-filament::section>
</x-filament-widgets::widget>

<x-filament-widgets::widget>
  <div class="rd-ac">
    @foreach ([
        ['title' => 'Do now', 'hint' => 'Things waiting on you', 'icon' => 'heroicon-o-bolt', 'items' => $todos, 'empty' => 'Nothing waiting — you\'re all caught up. 🎉'],
        ['title' => 'Ideas to grow', 'hint' => 'Suggestions from your numbers', 'icon' => 'heroicon-o-light-bulb', 'items' => $ideas, 'empty' => null],
    ] as $column)
      <x-filament::section :icon="$column['icon']" :description="$column['hint']" compact>
        <x-slot name="heading">
          {{ $column['title'] }}
          @if (count($column['items']) && $loop->first)
            <span class="rd-count">{{ count($column['items']) }}</span>
          @endif
        </x-slot>

        @forelse ($column['items'] as $item)
          <div class="rd-item rd-tone-{{ $item['tone'] }}">
            <span class="rd-item-icon">
              <x-filament::icon :icon="$item['icon']" class="rd-i" />
            </span>
            <div class="rd-item-body">
              <p class="rd-item-title">{{ $item['title'] }}</p>
              <p class="rd-item-text">{{ $item['body'] }}</p>
            </div>
            @if ($item['url'])
              <x-filament::link :href="$item['url']" size="sm" icon="heroicon-m-arrow-right" icon-position="after" class="rd-item-link">
                {{ $item['action'] }}
              </x-filament::link>
            @endif
          </div>
        @empty
          <p class="rd-empty">{{ $column['empty'] }}</p>
        @endforelse
      </x-filament::section>
    @endforeach
  </div>
</x-filament-widgets::widget>

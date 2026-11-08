{{--
  Small line drawings, one per discipline — the only illustration on the site.
  technology: connected nodes (a system) · education: an open, ruled book
  creative: a viewfinder with thirds · products: a screen with a pointer
  Unknown keys fall back to a simple square.
--}}
@props(['name'])
<svg {{ $attributes->merge(['class' => 'pf-glyph pf-glyph--'.$name]) }} viewBox="0 0 96 96" aria-hidden="true" focusable="false">
  <g fill="none" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke">
    @switch($name)
      @case('technology')
        <path d="M20 24h28M48 24v24M48 48h28M48 48 24 72M48 48v24"/>
        <rect x="14" y="18" width="12" height="12"/><rect x="42" y="42" width="12" height="12" class="pf-glyph__accent"/>
        <rect x="70" y="42" width="12" height="12"/><rect x="18" y="66" width="12" height="12"/><rect x="42" y="66" width="12" height="12"/>
        @break
      @case('education')
        <path d="M48 26c-8-6-20-8-32-6v52c12-2 24 0 32 6 8-6 20-8 32-6V20c-12-2-24 0-32 6Z"/>
        <path d="M48 26v52"/>
        <path d="M22 34h18M22 42h18M22 50h14M56 34h18M56 42h18" />
        <path d="M56 50h12" class="pf-glyph__accent"/>
        @break
      @case('creative')
        <rect x="12" y="22" width="72" height="52"/>
        <path d="M36 22v52M60 22v52M12 39h72M12 57h72" stroke-dasharray="2 3"/>
        <circle cx="60" cy="39" r="6" class="pf-glyph__accent"/>
        <path d="M12 30v-8h8M76 22h8v8M84 66v8h-8M20 74h-8v-8"/>
        @break
      @case('products')
        <rect x="14" y="18" width="68" height="46"/>
        <path d="M14 28h68M40 76h16M48 64v12"/>
        <rect x="22" y="36" width="22" height="18"/>
        <path d="M52 38h22M52 46h16"/>
        <path d="M60 50v14l4-4 3 6 3-1-3-6h6Z" class="pf-glyph__accent"/>
        @break
      @default
        <rect x="20" y="20" width="56" height="56"/>
    @endswitch
  </g>
</svg>

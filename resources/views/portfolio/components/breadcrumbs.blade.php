{{-- @props items: list of [label, url|null]. Also emits BreadcrumbList structured data. --}}
@props(['items'])
<nav class="pf-breadcrumbs" aria-label="{{ $pf->t('nav.breadcrumb') }}">
  <ol>
    @foreach ($items as [$label, $url])
      <li>
        @if ($url && ! $loop->last)
          <a href="{{ $url }}">{{ $label }}</a>
        @else
          <span aria-current="page">{{ $label }}</span>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($items)->values()->map(fn ($item, $i) => array_filter([
        '@type' => 'ListItem',
        'position' => $i + 1,
        'name' => $item[0],
        'item' => $item[1] ?? null,
    ]))->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

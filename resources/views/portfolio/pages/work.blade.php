@extends('portfolio.layouts.site')

@section('title', $pf->t('nav.work'))
@section('description', $pf->copy('work.meta_description'))
@section('body_class', 'pf-page-work')

@php
  $disciplines = $pf->portfolio->disciplineKeys();
  $statusesInUse = $projects->map->status()->unique()->values();
  $matches = fn ($p) => (! $activeCategory || $p->category() === $activeCategory) && (! $activeStatus || $p->status() === $activeStatus);
  $visibleCount = $projects->filter($matches)->count();
  $query = fn (array $change) => array_filter(array_merge(['category' => $activeCategory, 'status' => $activeStatus, 'view' => $view === 'gallery' ? 'gallery' : null], $change));
  // Gallery tiles take their width from the cover's shape, so the grid never repeats one card size.
  $span = function (?string $ratio) {
      [$w, $h] = array_map('floatval', explode('/', $ratio ?: '4/3') + [1 => 1]);
      $r = $h > 0 ? $w / $h : 1.33;
      return $r >= 2 ? 'xl' : ($r >= 1.45 ? 'l' : ($r >= 0.95 ? 'm' : 's'));
  };
@endphp

@section('content')
  <header class="pf-page-head pf-container pf-page-head--work">
    <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.work'), null]]" />
    <h1 class="pf-page-head__mega pf-display" data-reveal>{{ $pf->copy('work.title') }}<sup class="pf-page-head__count">{{ $pf->num($projects->count()) }}</sup></h1>
    <p class="pf-page-head__lede" data-reveal>{{ $pf->copy('work.intro') }}</p>
  </header>

  <div class="pf-container" data-work data-view="{{ $view }}">
    <div class="pf-filters" data-reveal>
      <nav class="pf-filters__cats" aria-label="{{ $pf->t('work.filter_label') }}">
        <a href="{{ $pf->url('work', $query(['category' => null])) }}" data-filter="category" data-value=""
           @class(['pf-chip', 'is-active' => ! $activeCategory]) aria-current="{{ $activeCategory ? 'false' : 'true' }}">
          {{ $pf->t('work.all') }}<sup>{{ $pf->num($projects->count()) }}</sup>
        </a>
        @foreach ($disciplines as $key)
          <a href="{{ $pf->url('work', $query(['category' => $key])) }}" data-filter="category" data-value="{{ $key }}"
             @class(['pf-chip', 'is-active' => $activeCategory === $key]) aria-current="{{ $activeCategory === $key ? 'true' : 'false' }}">
            {{ $pf->portfolio->discipline($pf->locale, $key)['title'] ?? $key }}<sup>{{ $pf->num($projects->filter(fn ($p) => $p->category() === $key)->count()) }}</sup>
          </a>
        @endforeach
      </nav>

      <div class="pf-filters__tools">
        <form method="get" action="{{ $pf->url('work') }}" class="pf-select-wrap" data-status-form>
          @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
          @if ($view === 'gallery')<input type="hidden" name="view" value="gallery">@endif
          <label for="status-filter" class="pf-visually-hidden">{{ $pf->t('work.status_label') }}</label>
          <select id="status-filter" name="status" class="pf-select" data-filter-select="status">
            <option value="">{{ $pf->t('work.any_status') }}</option>
            @foreach (\App\Portfolio\Project::STATUSES as $status)
              @if ($statusesInUse->contains($status))
                <option value="{{ $status }}" @selected($activeStatus === $status)>{{ $pf->t('status.'.$status) }}</option>
              @endif
            @endforeach
          </select>
          <noscript><button type="submit" class="pf-chip">OK</button></noscript>
        </form>

        <div class="pf-toggle" role="group" aria-label="{{ $pf->t('work.view_label') }}">
          <a href="{{ $pf->url('work', $query(['view' => null])) }}" data-view-btn="index" @class(['is-active' => $view === 'index']) aria-pressed="{{ $view === 'index' ? 'true' : 'false' }}">{{ $pf->t('work.view_index') }}</a>
          <a href="{{ $pf->url('work', $query(['view' => 'gallery'])) }}" data-view-btn="gallery" @class(['is-active' => $view === 'gallery']) aria-pressed="{{ $view === 'gallery' ? 'true' : 'false' }}">{{ $pf->t('work.view_gallery') }}</a>
        </div>
      </div>
    </div>

    <p class="pf-filters__count" aria-live="polite" data-count data-template="{{ $pf->t('work.showing', ['count' => '{count}', 'total' => $pf->num($projects->count())]) }}" data-khmer="{{ $pf->isKhmer() ? '1' : '0' }}">
      {{ $pf->t('work.showing', ['count' => $pf->num($visibleCount), 'total' => $pf->num($projects->count())]) }}
    </p>

    {{-- Index view: a typeset table of contents, with a floating preview on hover (desktop). --}}
    <ol class="pf-index" data-view-panel="index" @if ($view !== 'index') hidden @endif>
      <li class="pf-index__head" aria-hidden="true">
        <span>{{ $pf->t('work.col_no') }}</span><span>{{ $pf->t('work.col_project') }}</span><span>{{ $pf->t('common.category') }}</span><span>{{ $pf->t('common.status') }}</span><span>{{ $pf->t('common.year') }}</span>
      </li>
      @foreach ($projects as $project)
        @php $cover = $project->cover(); @endphp
        <li class="pf-index__row" data-item data-category="{{ $project->category() }}" data-status="{{ $project->status() }}" @if (! $matches($project)) hidden @endif>
          <a href="{{ $pf->url('work.show', ['project' => $project->slug]) }}" class="pf-index__link">
            <span class="pf-index__num">{{ $pf->num($loop->iteration) }}</span>
            <span class="pf-index__title pf-display">{{ $project->title($pf->locale) }}</span>
            <span class="pf-index__summary">{{ $project->summary($pf->locale) }}</span>
            <span class="pf-index__cat">{{ $pf->portfolio->discipline($pf->locale, $project->category())['title'] ?? '' }}</span>
            <x-pf::status :status="$project->status()" class="pf-index__status" />
            <span class="pf-index__year">{{ $project->year() ? $pf->num($project->year(), 4) : '—' }}</span>
            <span class="pf-index__preview" aria-hidden="true">
              <x-pf::figure :src="$cover['src'] ?? null" :ratio="$cover['ratio'] ?? '4/3'" :label="$cover['label'] ?? null" sizes="320px" />
            </span>
          </a>
        </li>
      @endforeach
    </ol>

    {{-- Gallery view: tiles sized by their own image shape. --}}
    <ul class="pf-mosaic" data-view-panel="gallery" @if ($view !== 'gallery') hidden @endif>
      @foreach ($projects as $project)
        @php $cover = $project->cover(); @endphp
        <li class="pf-tile pf-tile--{{ $span($cover['ratio'] ?? null) }}" data-item data-category="{{ $project->category() }}" data-status="{{ $project->status() }}" @if (! $matches($project)) hidden @endif>
          <a href="{{ $pf->url('work.show', ['project' => $project->slug]) }}" class="pf-tile__link">
            <x-pf::figure :src="$cover['src'] ?? null" :ratio="$cover['ratio'] ?? '4/3'" :label="$cover['label'] ?? null"
                          :alt="$project->alt($pf->locale)" sizes="(min-width: 1024px) 50vw, 100vw" />
            <span class="pf-tile__meta">
              <span class="pf-tile__num">{{ $pf->num($loop->iteration) }}</span>
              <span class="pf-tile__title pf-display">{{ $project->title($pf->locale) }}</span>
              <x-pf::status :status="$project->status()" />
            </span>
          </a>
        </li>
      @endforeach
    </ul>

    <div class="pf-empty" data-empty @if ($visibleCount > 0) hidden @endif>
      <p>{{ $pf->t('work.empty') }}</p>
      <a href="{{ $pf->url('work') }}" class="pf-chip" data-reset>{{ $pf->t('work.reset') }}</a>
    </div>
  </div>
@endsection

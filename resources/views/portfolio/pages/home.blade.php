@extends('portfolio.layouts.site')

@section('body_class', 'pf-page-home')

@push('head')
  @php
    $socialUrls = collect($pf->portfolio->get('socials', []))->pluck('url')->filter()->values()->all();
  @endphp
  <script type="application/ld+json">{!! json_encode(array_filter([
      '@context' => 'https://schema.org',
      '@type' => 'Person',
      'name' => $pf->name(),
      'url' => $pf->canonical(),
      'description' => $pf->copy('role'),
      'image' => $pf->portfolio->media()->url($pf->portfolio->get('portrait.src')),
      'sameAs' => $socialUrls ?: null,
      'knowsAbout' => collect($pf->portfolio->disciplineKeys())->map(fn ($k) => $pf->portfolio->discipline($pf->locale, $k)['title'] ?? $k)->all(),
  ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@php
  /** Wraps a closing full stop (. or ។) in the accent colour. */
  $accentStop = fn (?string $s) => preg_replace('/([.។?])$/u', '<span class="pf-accent">$1</span>', e((string) $s));
  $headline = (array) $pf->copy('home.hero.headline', []);
  $variants = ['wide', 'left', 'right', 'inset'];
@endphp

@section('content')

  {{-- 01 — Hero ----------------------------------------------------- --}}
  <section class="pf-hero" aria-labelledby="hero-title">
    <div class="pf-container pf-hero__grid">
      <div class="pf-hero__meta" data-reveal>
        <span>{{ $pf->copy('home.hero.kicker') }}</span>
        <span class="pf-hero__meta-rule" aria-hidden="true"></span>
        <span>{{ $pf->num(now()->year, 4) }}</span>
      </div>

      <h1 id="hero-title" class="pf-hero__title pf-display" data-reveal>
        @foreach ($headline as $line)
          <span class="pf-hero__line pf-hero__line--{{ $loop->iteration }}">{!! $loop->last ? '<em>'.$accentStop($line).'</em>' : $accentStop($line) !!}</span>
        @endforeach
      </h1>

      <div class="pf-hero__portrait" data-reveal="image">
        <x-pf::figure :src="$pf->portfolio->get('portrait.src')" ratio="4/5" :label="$pf->copy('home.hero.caption')"
                      :alt="$pf->name()" sizes="(min-width: 1024px) 34vw, 90vw" eager localized
                      :focus="$pf->portfolio->get('portrait.focus')" />
        <p class="pf-hero__fig" aria-hidden="true">{{ $pf->t('common.index') }} {{ $pf->num(1) }} — {{ $pf->name() }}</p>
      </div>

      <div class="pf-hero__aside" data-reveal>
        <p class="pf-hero__name">{{ $pf->name() }}</p>
        <p class="pf-hero__role">{{ $pf->copy('role') }}</p>
        <p class="pf-hero__lede">{{ $pf->copy('home.hero.lede') }}</p>
        <div class="pf-hero__actions">
          <x-pf::arrow-link :href="$pf->url('work')" class="pf-arrow-link--strong">{{ $pf->copy('home.hero.cta_work') }}</x-pf::arrow-link>
          <x-pf::arrow-link :href="$pf->url('about')">{{ $pf->copy('home.hero.cta_about') }}</x-pf::arrow-link>
        </div>
      </div>

      <ol class="pf-hero__index" data-reveal>
        @foreach ($pf->portfolio->disciplineKeys() as $key)
          <li>
            <a href="{{ $pf->url('work', ['category' => $key]) }}">
              <span class="pf-hero__index-num">{{ $pf->num($loop->iteration) }}</span>
              <span>{{ $pf->portfolio->discipline($pf->locale, $key)['title'] ?? $key }}</span>
            </a>
          </li>
        @endforeach
      </ol>

      <span class="pf-reg pf-reg--a" aria-hidden="true"></span>
      <span class="pf-reg pf-reg--b" aria-hidden="true"></span>
    </div>
  </section>

  {{-- 02 — Introduction --------------------------------------------- --}}
  <section class="pf-section pf-intro" aria-labelledby="intro-title">
    <div class="pf-container pf-grid">
      <x-pf::section-label :number="2" class="pf-col-label">{{ $pf->copy('home.intro.label') }}</x-pf::section-label>
      <h2 id="intro-title" class="pf-intro__title pf-display" data-reveal>{!! $accentStop($pf->copy('home.intro.title')) !!}</h2>
      <div class="pf-intro__body pf-prose" data-reveal>{!! \App\Portfolio\Markdown::render($pf->copy('home.intro.body')) !!}</div>
      <ol class="pf-process" data-reveal aria-label="{{ $pf->copy('home.intro.title') }}">
        @foreach ((array) $pf->copy('home.intro.process', []) as $step)
          <li><span class="pf-process__num">{{ $pf->num($loop->iteration) }}</span><span class="pf-process__word">{{ $step }}</span></li>
        @endforeach
      </ol>
    </div>
  </section>

  {{-- 03 — Selected work -------------------------------------------- --}}
  <section class="pf-section pf-selected" aria-labelledby="selected-title">
    <div class="pf-container">
      <div class="pf-section-head">
        <x-pf::section-label :number="3">{{ $pf->copy('home.work.label') }}</x-pf::section-label>
        <h2 id="selected-title" class="pf-section-head__title pf-display">{{ $pf->copy('home.work.title') }}</h2>
        <x-pf::arrow-link :href="$pf->url('work')" class="pf-section-head__more">{{ $pf->copy('home.work.more') }} ({{ $pf->num($projects->count()) }})</x-pf::arrow-link>
      </div>

      <div class="pf-features">
        @foreach ($featured as $project)
          @php
            $variant = $variants[$loop->index % count($variants)];
            $cover = $project->cover();
            $url = $pf->url('work.show', ['project' => $project->slug]);
            $discipline = $pf->portfolio->discipline($pf->locale, $project->category());
            $sizes = match ($variant) { 'wide' => '(min-width: 1024px) 90vw, 100vw', 'inset' => '(min-width: 1024px) 34vw, 100vw', default => '(min-width: 1024px) 55vw, 100vw' };
            $ratio = match ($variant) { 'wide' => '21/9', 'left' => '4/3', 'right' => '4/5', default => '1/1' };
          @endphp
          <article class="pf-feature pf-feature--{{ $variant }}" data-reveal>
            <a href="{{ $url }}" class="pf-feature__media" tabindex="-1" aria-hidden="true">
              <x-pf::figure :src="$cover['src'] ?? null" :ratio="$ratio" :label="$cover['label'] ?? null"
                            :alt="$project->alt($pf->locale)" :sizes="$sizes" />
            </a>
            <div class="pf-feature__body">
              <p class="pf-feature__meta">
                <span class="pf-feature__index">({{ $pf->num($loop->iteration) }})</span>
                <span>{{ $discipline['title'] ?? '' }}</span>
                <x-pf::status :status="$project->status()" />
              </p>
              <h3 class="pf-feature__title pf-display"><a href="{{ $url }}">{{ $project->title($pf->locale) }}</a></h3>
              <p class="pf-feature__summary">{{ $project->summary($pf->locale) }}</p>
              <x-pf::arrow-link :href="$url">{{ $pf->t('common.read_case_study') }}</x-pf::arrow-link>
            </div>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  {{-- 04 — Areas of exploration ------------------------------------- --}}
  <section class="pf-section pf-areas" aria-labelledby="areas-title">
    <div class="pf-container">
      <div class="pf-section-head">
        <x-pf::section-label :number="4">{{ $pf->copy('home.areas.label') }}</x-pf::section-label>
        <h2 id="areas-title" class="pf-section-head__title pf-display">{{ $pf->copy('home.areas.title') }}</h2>
      </div>

      <ol class="pf-areas__list">
        @foreach ($pf->portfolio->disciplineKeys() as $key)
          @php $d = $pf->portfolio->discipline($pf->locale, $key); @endphp
          <li class="pf-area pf-area--{{ $key }}" data-reveal>
            <span class="pf-area__num pf-display">{{ $pf->num($loop->iteration) }}</span>
            <x-pf::glyph :name="$key" class="pf-area__glyph" />
            <div class="pf-area__main">
              <h3 class="pf-area__title pf-display">{{ $d['title'] ?? $key }}</h3>
              <p class="pf-area__desc">{{ $d['description'] ?? '' }}</p>
            </div>
            <div class="pf-area__side">
              <p class="pf-area__examples-label">{{ $pf->t('common.examples') }}</p>
              <ul class="pf-area__examples">
                @foreach ($d['examples'] ?? [] as $example)
                  <li>{{ $example }}</li>
                @endforeach
              </ul>
              <x-pf::arrow-link :href="$pf->url('work', ['category' => $key])">{{ $pf->t('common.see_related') }}</x-pf::arrow-link>
            </div>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  {{-- 05 — Current exploration -------------------------------------- --}}
  <section class="pf-section pf-now" aria-labelledby="now-title">
    <div class="pf-container pf-grid">
      <div class="pf-now__head">
        <x-pf::section-label :number="5">{{ $pf->copy('home.now.label') }}</x-pf::section-label>
        <h2 id="now-title" class="pf-now__title pf-display">{{ $pf->copy('home.now.title') }}</h2>
        <p class="pf-now__intro">{{ $pf->copy('home.now.intro') }}</p>
        @if ($updated = $pf->portfolio->get('now_updated'))
          <p class="pf-now__updated">
            <time datetime="{{ $updated }}">{{ $pf->t('date.updated', ['date' => \Illuminate\Support\Carbon::parse($updated)->locale($pf->locale)->translatedFormat('j F Y')]) }}</time>
          </p>
        @endif
      </div>

      <div class="pf-now__table" role="table" aria-labelledby="now-title">
        <div class="pf-now__row pf-now__row--head" role="row">
          <span role="columnheader">{{ $pf->copy('home.now.columns.subject') }}</span>
          <span role="columnheader">{{ $pf->copy('home.now.columns.kind') }}</span>
          <span role="columnheader">{{ $pf->copy('home.now.columns.status') }}</span>
        </div>
        @foreach ((array) $pf->copy('home.now.items', []) as $item)
          <div class="pf-now__row" role="row" data-reveal>
            <span role="cell" class="pf-now__subject">
              <span class="pf-now__name">{{ $item['subject'] }}</span>
              <span class="pf-now__detail">{{ $item['detail'] ?? '' }}</span>
            </span>
            <span role="cell" class="pf-now__kind pf-now__kind--{{ $item['kind'] ?? 'learning' }}">{{ $pf->t('kind.'.($item['kind'] ?? 'learning')) }}</span>
            <span role="cell" class="pf-now__status">{{ $item['status'] ?? '' }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- 06 — Philosophy ------------------------------------------------ --}}
  <section class="pf-section pf-philosophy" aria-labelledby="philosophy-title">
    <div class="pf-container">
      <div class="pf-philosophy__head">
        <x-pf::section-label :number="6">{{ $pf->copy('home.philosophy.label') }}</x-pf::section-label>
        <h2 id="philosophy-title" class="pf-philosophy__title">{{ $pf->copy('home.philosophy.title') }}</h2>
      </div>
      <ol class="pf-principles">
        @foreach ((array) $pf->copy('home.philosophy.principles', []) as $principle)
          <li class="pf-principle pf-principle--{{ $loop->iteration }}" data-reveal>
            <p class="pf-principle__line pf-display">{!! $accentStop($principle['line']) !!}</p>
            <p class="pf-principle__note"><span class="pf-principle__num">{{ $pf->num($loop->iteration) }}</span>{{ $principle['note'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  {{-- 07 — Closing --------------------------------------------------- --}}
  <section class="pf-section pf-closing" aria-labelledby="closing-title">
    <div class="pf-container pf-grid">
      <x-pf::section-label :number="7" class="pf-col-label">{{ $pf->t('nav.contact') }}</x-pf::section-label>
      <h2 id="closing-title" class="pf-closing__title pf-display" data-reveal>{!! $accentStop($pf->copy('home.closing.headline')) !!}</h2>
      <p class="pf-closing__body" data-reveal>{{ $pf->copy('home.closing.body') }}</p>
      <a class="pf-closing__cta" href="{{ $pf->url('contact') }}" data-reveal>
        <span>{{ $pf->copy('home.closing.cta') }}</span>
        <svg width="44" height="16" viewBox="0 0 44 16" aria-hidden="true"><path d="M0 8h42M35 1l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
      </a>
    </div>
  </section>
@endsection

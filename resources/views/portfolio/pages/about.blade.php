@extends('portfolio.layouts.site')

@section('title', $pf->t('nav.about'))
@section('description', $pf->copy('about.meta_description'))
@section('body_class', 'pf-page-about')

@php
  use App\Portfolio\Markdown;
  $file = 'portfolios/'.$pf->portfolio->slug.'/content/'.$pf->locale.'.php';
  $entries = collect((array) $pf->copy('about.journey.entries', []));
  $verified = $entries->filter(fn ($e) => ! empty($e['verified']));
  $unverified = $entries->reject(fn ($e) => ! empty($e['verified']));
  $beyond = $pf->copy('about.beyond.body');
  $interests = (array) $pf->copy('about.beyond.interests', []);
@endphp

@section('content')
  <header class="pf-page-head pf-container">
    <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.about'), null]]" />
    <x-pf::section-label :number="1" as="p">{{ $pf->copy('about.label') }}</x-pf::section-label>
    <h1 class="pf-page-head__title pf-display" data-reveal>{{ $pf->copy('about.title') }}</h1>
    <p class="pf-page-head__lede" data-reveal>{{ $pf->copy('about.intro') }}</p>
  </header>

  <div class="pf-container pf-about">
    <aside class="pf-about__portrait" data-reveal="image">
      <x-pf::figure :src="$pf->portfolio->get('portrait.secondary') ?: $pf->portfolio->get('portrait.src')" ratio="3/4"
                    :label="$pf->copy('home.hero.caption')" :alt="$pf->name()" sizes="(min-width: 1024px) 36vw, 100vw"
                    :caption="$pf->copy('about.portrait_caption')" localized
                    :focus="$pf->portfolio->get('portrait.focus')" />
    </aside>

    <div class="pf-about__text">
      {{-- Story --}}
      <section class="pf-about__block" aria-labelledby="story-title" lang="{{ $pf->copyLang('about.story.body') }}">
        <x-pf::section-label :number="2" as="h2" id="story-title">{{ $pf->copy('about.story.label') }}</x-pf::section-label>
        @if ($pf->copy('about.story.draft'))
          <x-pf::editor-note>Draft written from your brief — rewrite “about.story.body” in {{ $file }} in your own words, then remove 'draft' => true.</x-pf::editor-note>
        @endif
        <div class="pf-prose pf-prose--lead">{!! Markdown::render($pf->copy('about.story.body')) !!}</div>
      </section>

      {{-- How I think --}}
      <section class="pf-about__block" aria-labelledby="thinking-title">
        <x-pf::section-label :number="3" as="p">{{ $pf->copy('about.thinking.label') }}</x-pf::section-label>
        <h2 id="thinking-title" class="pf-about__h pf-display">{{ $pf->copy('about.thinking.title') }}</h2>
        <dl class="pf-traits">
          @foreach ((array) $pf->copy('about.thinking.items', []) as $item)
            <div class="pf-trait" data-reveal>
              <dt><span class="pf-trait__num">{{ $pf->num($loop->iteration) }}</span>{{ $item['title'] }}</dt>
              <dd>{{ $item['body'] }}</dd>
            </div>
          @endforeach
        </dl>
      </section>
    </div>
  </div>

  {{-- Journey --}}
  <section class="pf-section pf-journey" aria-labelledby="journey-title">
    <div class="pf-container">
      <div class="pf-section-head">
        <x-pf::section-label :number="4">{{ $pf->copy('about.journey.label') }}</x-pf::section-label>
        <h2 id="journey-title" class="pf-section-head__title pf-display">{{ $pf->copy('about.journey.title') }}</h2>
      </div>

      @if ($verified->isNotEmpty())
        <ol class="pf-timeline">
          @foreach ($verified as $entry)
            <li class="pf-timeline__item" data-reveal>
              <span class="pf-timeline__year pf-display">{{ $entry['year'] ? $pf->num((string) $entry['year']) : '—' }}</span>
              <span class="pf-timeline__type">{{ $entry['type'] ?? '' }}</span>
              <div class="pf-timeline__main">
                <h3 class="pf-timeline__title">{{ $entry['title'] }}</h3>
                @if (! empty($entry['body']))<p>{{ $entry['body'] }}</p>@endif
              </div>
            </li>
          @endforeach
        </ol>
      @else
        <p class="pf-muted pf-journey__empty">{{ $pf->copy('about.journey.empty') }}</p>
      @endif

      @if ($pf->editorHints() && $unverified->isNotEmpty())
        <x-pf::editor-note>
          {{ $unverified->count() }} placeholder timeline entries are hidden from visitors. Fill them in at “about.journey.entries” in {{ $file }} and set 'verified' => true:
          {{ $unverified->pluck('title')->implode(' · ') }}
        </x-pf::editor-note>
      @endif
    </div>
  </section>

  {{-- Beyond work --}}
  @if ($beyond || $interests)
    <section class="pf-section pf-beyond" aria-labelledby="beyond-title">
      <div class="pf-container pf-grid">
        <x-pf::section-label :number="5" class="pf-col-label">{{ $pf->copy('about.beyond.label') }}</x-pf::section-label>
        <h2 id="beyond-title" class="pf-beyond__title pf-display">{{ $pf->copy('about.beyond.title') }}</h2>
        <div class="pf-beyond__body pf-prose">{!! Markdown::render($beyond) !!}</div>
        @if ($interests)
          <ul class="pf-beyond__list">
            @foreach ($interests as $interest)<li>{{ $interest }}</li>@endforeach
          </ul>
        @endif
      </div>
    </section>
  @else
    <div class="pf-container">
      <x-pf::editor-note>“Beyond work” is hidden until you add about.beyond.body or about.beyond.interests in {{ $file }}.</x-pf::editor-note>
    </div>
  @endif

  <section class="pf-section pf-about-next">
    <div class="pf-container pf-about-next__inner">
      <x-pf::arrow-link :href="$pf->url('work')" class="pf-arrow-link--strong">{{ $pf->t('common.all_work') }}</x-pf::arrow-link>
      <x-pf::arrow-link :href="$pf->url('expertise')">{{ $pf->t('nav.expertise') }}</x-pf::arrow-link>
      <x-pf::arrow-link :href="$pf->url('contact')">{{ $pf->t('nav.contact') }}</x-pf::arrow-link>
    </div>
  </section>
@endsection

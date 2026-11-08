@extends('portfolio.layouts.site')

@php
  /** @var \App\Portfolio\Note $note */
  $L = $pf->locale;
  $lang = $note->resolvedLocale($L);
  $cover = $note->get('cover');
  $ogCover = $pf->portfolio->media()->url($cover);
@endphp

@section('title', $note->title($L))
@section('description', $note->excerpt($L))
@section('og_type', 'article')
@if ($ogCover)
  @section('og_image', $ogCover)
@endif
@section('body_class', 'pf-page-note')

@push('head')
  @if ($note->isDraft())<meta name="robots" content="noindex">@endif
  <script type="application/ld+json">{!! json_encode(array_filter([
      '@context' => 'https://schema.org',
      '@type' => 'BlogPosting',
      'headline' => $note->title($L),
      'description' => $note->excerpt($L),
      'datePublished' => $note->date()?->toDateString(),
      'inLanguage' => $lang,
      'url' => $pf->canonical(),
      'image' => $ogCover,
      'author' => ['@type' => 'Person', 'name' => $pf->name(), 'url' => $pf->url('home')],
  ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
  <article class="pf-article">
    <header class="pf-article__head pf-container">
      <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.notes_short'), $pf->url('notes')], [$note->title($L), null]]" />
      <p class="pf-article__meta">
        <span>{{ $pf->t('notes.types.'.$note->type()) }}</span>
        <time datetime="{{ $note->date()?->toDateString() }}">{{ $note->date()?->locale($L)->translatedFormat('j F Y') }}</time>
        <span>{{ $pf->t('common.reading_time', ['min' => $pf->num($note->readingTime($L), 1)]) }}</span>
      </p>
      <h1 class="pf-article__title pf-display" lang="{{ $lang }}" data-reveal>{{ $note->title($L) }}</h1>
      @if ($excerpt = $note->excerpt($L))
        <p class="pf-article__lede" lang="{{ $lang }}" data-reveal>{{ $excerpt }}</p>
      @endif
      @if ($note->isDraft())
        <x-pf::editor-note>{{ $pf->t('notes.draft') }}</x-pf::editor-note>
      @endif
      @if ($lang !== $L)
        <p class="pf-notice" role="note">{{ $pf->t('notes.only_in') }}</p>
      @endif
    </header>

    @if ($cover)
      <div class="pf-container pf-article__cover">
        <x-pf::figure :src="$cover" ratio="16/9" :alt="$note->title($L)" sizes="(min-width: 1280px) 1200px, 100vw" eager />
      </div>
    @endif

    <div class="pf-container">
      <div class="pf-article__body pf-prose pf-prose--article" lang="{{ $lang }}">{!! $note->html($L) !!}</div>
    </div>

    <footer class="pf-container pf-article__foot">
      @if ($related->isNotEmpty())
        <div class="pf-article__related">
          <p class="pf-label"><span class="pf-label__text">{{ $pf->t('notes.related_projects') }}</span></p>
          <ul>
            @foreach ($related as $project)
              <li><x-pf::arrow-link :href="$pf->url('work.show', ['project' => $project->slug])">{{ $project->title($L) }}</x-pf::arrow-link></li>
            @endforeach
          </ul>
        </div>
      @endif
      <x-pf::arrow-link :href="$pf->url('notes')">{{ $pf->t('notes.back') }}</x-pf::arrow-link>
    </footer>
  </article>
@endsection

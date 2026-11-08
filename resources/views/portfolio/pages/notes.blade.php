@extends('portfolio.layouts.site')

@section('title', $pf->t('nav.notes'))
@section('description', $pf->copy('notes.meta_description'))
@section('body_class', 'pf-page-notes')

@php
  $typesInUse = $notes->map->type()->unique()->values();
  $visible = $activeType ? $notes->filter(fn ($n) => $n->type() === $activeType) : $notes;
@endphp

@section('content')
  <header class="pf-page-head pf-container">
    <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.notes'), null]]" />
    <x-pf::section-label :number="1">{{ $pf->copy('notes.label') }}</x-pf::section-label>
    <h1 class="pf-page-head__title pf-display" data-reveal>{{ $pf->copy('notes.title') }}</h1>
    <p class="pf-page-head__lede" data-reveal>{{ $pf->copy('notes.intro') }}</p>
  </header>

  <div class="pf-container pf-notes">
    @if ($typesInUse->count() > 1)
      <nav class="pf-filters__cats" aria-label="{{ $pf->t('notes.filter_label') }}">
        <a href="{{ $pf->url('notes') }}" @class(['pf-chip', 'is-active' => ! $activeType])>{{ $pf->t('notes.all') }}</a>
        @foreach ($typesInUse as $type)
          <a href="{{ $pf->url('notes', ['type' => $type]) }}" @class(['pf-chip', 'is-active' => $activeType === $type])>{{ $pf->t('notes.types.'.$type) }}</a>
        @endforeach
      </nav>
    @endif

    @if ($visible->isNotEmpty())
      <ol class="pf-notes__list">
        @foreach ($visible as $note)
          @php $lang = $note->resolvedLocale($pf->locale); @endphp
          <li class="pf-note-row" data-reveal>
            <a href="{{ $pf->url('notes.show', ['note' => $note->slug]) }}" class="pf-note-row__link">
              <time class="pf-note-row__date" datetime="{{ $note->date()?->toDateString() }}">{{ $note->date()?->locale($pf->locale)->translatedFormat('j M Y') }}</time>
              <span class="pf-note-row__type">{{ $pf->t('notes.types.'.$note->type()) }}@if ($note->isDraft()) · <em>{{ $pf->t('notes.draft') }}</em>@endif</span>
              <span class="pf-note-row__main" lang="{{ $lang }}">
                <span class="pf-note-row__title pf-display">{{ $note->title($pf->locale) }}</span>
                @if ($excerpt = $note->excerpt($pf->locale))<span class="pf-note-row__excerpt">{{ $excerpt }}</span>@endif
              </span>
              <span class="pf-note-row__time">{{ $pf->t('common.reading_time', ['min' => $pf->num($note->readingTime($pf->locale), 1)]) }}</span>
            </a>
          </li>
        @endforeach
      </ol>
    @else
      <div class="pf-notes__empty">
        <p class="pf-notes__empty-text pf-display">{{ $pf->copy('notes.empty') }}</p>
        @if ($planned = (array) $pf->copy('notes.planned', []))
          <div class="pf-notes__planned">
            <p class="pf-label"><span class="pf-label__text">{{ $pf->copy('notes.planned_label') }}</span></p>
            <ul>
              @foreach ($planned as $topic)<li><span class="pf-status pf-status--concept"><span class="pf-status__dot" aria-hidden="true"></span></span>{{ $topic }}</li>@endforeach
            </ul>
          </div>
        @endif
      </div>
    @endif

    <x-pf::editor-note>To publish a note, copy portfolios/{{ $pf->portfolio->slug }}/notes/_example to a new folder (the folder name becomes the URL) and set 'draft' => false.</x-pf::editor-note>
  </div>
@endsection

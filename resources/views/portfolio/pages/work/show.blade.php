@extends('portfolio.layouts.site')

@php
  use App\Portfolio\Markdown;
  /** @var \App\Portfolio\Project $project */
  $L = $pf->locale;
  $template = $project->template();
  $sections = $project->sections($L);
  $sectionLang = $sections ? reset($sections)['locale'] : $L;
  $gallery = $project->gallery($L);
  $discipline = $pf->portfolio->discipline($L, $project->category());
  $cover = $project->cover();
  $file = 'portfolios/'.$pf->portfolio->slug.'/projects/'.$project->slug.'/'.$pf->portfolio->baseLocale().'.php';
  $toc = collect(array_keys($sections))->map(fn ($k) => [$k, $pf->t('case.'.$k)]);
  if ($gallery) { $toc->push(['gallery', $pf->t('case.gallery')]); }
@endphp

@section('title', $project->title($L))
@section('description', $project->summary($L))
@section('og_type', 'article')
@if ($ogCover = $pf->portfolio->media()->url($cover['src'] ?? null))
  @section('og_image', $ogCover)
@endif
@section('body_class', 'pf-page-case pf-page-case--'.$template)

@push('head')
  <script type="application/ld+json">{!! json_encode(array_filter([
      '@context' => 'https://schema.org',
      '@type' => 'CreativeWork',
      'name' => $project->title($L),
      'description' => $project->summary($L),
      'url' => $pf->canonical(),
      'inLanguage' => $L,
      'genre' => $discipline['title'] ?? null,
      'dateCreated' => $project->get('date'),
      'creator' => ['@type' => 'Person', 'name' => $pf->name(), 'url' => $pf->url('home')],
      'image' => $ogCover ?: null,
  ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<article class="pf-case pf-case--{{ $template }}">

  {{-- 01 — Hero (layout depends on the kind of project) ----------------- --}}
  @include('portfolio.pages.work.partials.hero-'.$template)

  @if ($sectionLang !== $L && $sections)
    <div class="pf-container"><p class="pf-notice" role="note">{{ $pf->t('case.only_in') }}</p></div>
  @endif

  <div class="pf-container pf-case__layout">
    @if ($toc->count() > 1)
      <nav class="pf-case__toc" aria-label="{{ $pf->t('case.contents') }}">
        <p class="pf-case__toc-title">{{ $pf->t('case.contents') }}</p>
        <ol>
          @foreach ($toc as [$id, $label])
            <li><a href="#{{ $id }}" data-toc-link><span>{{ $pf->num($loop->iteration + 1) }}</span>{{ $label }}</a></li>
          @endforeach
        </ol>
      </nav>
    @endif

    <div class="pf-case__body" lang="{{ $sectionLang }}">
      @if (in_array($project->status(), ['in_development', 'ongoing', 'exploring', 'concept'], true) && count($sections) < 4)
        <p class="pf-case__progress" lang="{{ $L }}"><x-pf::status :status="$project->status()" /> {{ $pf->t('case.in_progress_note') }}</p>
      @endif

      @foreach ($sections as $key => $section)
        <section id="{{ $key }}" class="pf-case__section pf-case__section--{{ $key }}" aria-labelledby="h-{{ $key }}" data-section>
          <div class="pf-case__section-head">
            <span class="pf-case__section-num">{{ $pf->num($loop->iteration + 1) }}</span>
            <h2 id="h-{{ $key }}" class="pf-case__section-title" lang="{{ $L }}">{{ $pf->t('case.'.$key) }}</h2>
          </div>

          @if ($key === 'features')
            <div class="pf-case-features">
              @foreach ((array) $section['value'] as $feature)
                <div class="pf-case-feature" data-reveal>
                  <div class="pf-case-feature__text">
                    <h3 class="pf-case-feature__title pf-display"><span class="pf-case-feature__num">{{ $pf->num($loop->iteration) }}</span>{{ $feature['title'] ?? '' }}</h3>
                    <div class="pf-prose">{!! Markdown::render($feature['body'] ?? '') !!}</div>
                  </div>
                  @if (! empty($feature['image']))
                    <x-pf::figure class="pf-case-feature__media" :src="$feature['image']" :ratio="$feature['ratio'] ?? '16/10'"
                                  :label="$feature['title'] ?? null" :alt="$feature['title'] ?? ''" zoom="features"
                                  :caption="$feature['title'] ?? null" sizes="(min-width: 1024px) 50vw, 100vw" />
                  @endif
                </div>
              @endforeach
            </div>
          @elseif ($key === 'technical')
            <div class="pf-prose pf-case__spec">{!! Markdown::render($section['value']) !!}</div>
            @if ($project->get('tech'))
              <ul class="pf-tags" aria-label="{{ $pf->t('common.tools') }}">
                @foreach ($project->get('tech') as $tool)<li>{{ $tool }}</li>@endforeach
              </ul>
            @endif
          @elseif (in_array($key, ['idea', 'outcome'], true))
            <div class="pf-prose pf-prose--statement">{!! Markdown::render($section['value']) !!}</div>
          @else
            <div class="pf-prose">{!! Markdown::render($section['value']) !!}</div>
          @endif
        </section>
      @endforeach

      @if ($pf->editorHints() && ($missing = $project->missingSections($L)))
        <x-pf::editor-note>
          Not written yet (hidden from visitors): {{ collect($missing)->map(fn ($s) => $pf->t('case.'.$s))->implode(' · ') }} — add them in {{ $file }}
        </x-pf::editor-note>
      @endif
    </div>
  </div>

  {{-- Gallery ------------------------------------------------------------- --}}
  @if ($gallery)
    <section id="gallery" class="pf-section pf-case-gallery" aria-labelledby="h-gallery">
      <div class="pf-container">
        <div class="pf-case__section-head">
          <span class="pf-case__section-num">{{ $pf->num(count($sections) + 2) }}</span>
          <h2 id="h-gallery" class="pf-case__section-title">{{ $pf->t('case.gallery') }}</h2>
        </div>
        <div class="pf-case-gallery__grid">
          @foreach ($gallery as $item)
            <x-pf::figure class="pf-case-gallery__item" :src="$item['src']" :ratio="$item['ratio'] ?? '4/3'" :label="$item['label'] ?? null"
                          :alt="$item['caption'] ?? ($item['label'] ?? $project->title($L))" :caption="$item['caption']"
                          zoom="gallery" sizes="(min-width: 1024px) 50vw, 100vw" :tone="$template === 'creative' ? 'deep' : null" />
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Related + next ---------------------------------------------------- --}}
  @if ($related->isNotEmpty())
    <section class="pf-section pf-related" aria-labelledby="h-related">
      <div class="pf-container">
        <x-pf::section-label as="h2" id="h-related">{{ $pf->t('common.related') }}</x-pf::section-label>
        <ul class="pf-related__list">
          @foreach ($related as $item)
            @php $c = $item->cover(); @endphp
            <li>
              <a href="{{ $pf->url('work.show', ['project' => $item->slug]) }}" class="pf-related__link">
                <x-pf::figure :src="$c['src'] ?? null" :ratio="'4/3'" :label="$c['label'] ?? null" sizes="(min-width: 1024px) 28vw, 100vw" />
                <span class="pf-related__cat">{{ $pf->portfolio->discipline($L, $item->category())['title'] ?? '' }}</span>
                <span class="pf-related__title pf-display">{{ $item->title($L) }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    </section>
  @endif

  @if ($next->slug !== $project->slug)
    <nav class="pf-next" aria-label="{{ $pf->t('common.next_project') }}">
      <a href="{{ $pf->url('work.show', ['project' => $next->slug]) }}" class="pf-container pf-next__link">
        <span class="pf-next__label">{{ $pf->t('common.next_project') }} — {{ $pf->num($position % $total + 1) }}/{{ $pf->num($total) }}</span>
        <span class="pf-next__title pf-display">{{ $next->title($L) }}</span>
        <svg width="64" height="20" viewBox="0 0 64 20" aria-hidden="true"><path d="M0 10h62M53 1l9 9-9 9" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
      </a>
    </nav>
  @endif
</article>
@endsection

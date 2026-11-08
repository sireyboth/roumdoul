{{-- Creative production: a dark, letterboxed "screening" hero with slate-style metadata. --}}
<header class="pf-case-hero pf-case-hero--creative">
  <div class="pf-container">
    <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.work'), $pf->url('work')], [$project->title($pf->locale), null]]" />
    <p class="pf-case-hero__slate" aria-hidden="true">
      <span>{{ $pf->num(1) }}</span><span>{{ $discipline['title'] ?? '' }}</span><span>{{ $project->year() ? $pf->num($project->year(), 4) : '—' }}</span>
    </p>
  </div>
  <div class="pf-case-hero__screen" data-reveal="image">
    <x-pf::figure :src="$cover['src'] ?? null" :ratio="$cover['ratio'] ?? '21/9'" :label="$cover['label'] ?? null"
                  :alt="$project->alt($pf->locale)" sizes="100vw" eager tone="deep" />
  </div>
  <div class="pf-container pf-case-hero__grid">
    <h1 class="pf-case-hero__title pf-display" data-reveal>{{ $project->title($pf->locale) }}</h1>
    <div class="pf-case-hero__side">
      <p class="pf-case-hero__intro" data-reveal>{{ $project->text($pf->locale, 'intro', $project->summary($pf->locale)) }}</p>
      @include('portfolio.pages.work.partials.meta', ['class' => 'pf-case-hero__meta'])
    </div>
  </div>
</header>

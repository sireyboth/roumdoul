{{-- Software / systems: a "specification sheet" hero — title and facts side by side, screenshot on a plate. --}}
<header class="pf-case-hero pf-case-hero--system">
  <div class="pf-container">
    <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.work'), $pf->url('work')], [$project->title($pf->locale), null]]" />
    <div class="pf-case-hero__grid">
      <div class="pf-case-hero__main">
        <p class="pf-case-hero__kicker"><span class="pf-label__num">{{ $pf->num(1) }}</span> {{ $discipline['title'] ?? '' }}</p>
        <h1 class="pf-case-hero__title pf-display" data-reveal>{{ $project->title($pf->locale) }}</h1>
        <p class="pf-case-hero__intro" data-reveal>{{ $project->text($pf->locale, 'intro', $project->summary($pf->locale)) }}</p>
      </div>
      @include('portfolio.pages.work.partials.meta', ['class' => 'pf-case-hero__meta'])
    </div>
    <div class="pf-case-hero__plate" data-reveal="image">
      <x-pf::figure :src="$cover['src'] ?? null" :ratio="$cover['ratio'] ?? '16/10'" :label="$cover['label'] ?? null"
                    :alt="$project->alt($pf->locale)" sizes="(min-width: 1280px) 1200px, 100vw" eager />
    </div>
  </div>
</header>

@extends('portfolio.layouts.site')

@section('title', $pf->t('nav.expertise'))
@section('description', $pf->copy('expertise.meta_description'))
@section('body_class', 'pf-page-expertise')

@php
  $groupKeys = $pf->portfolio->get('expertise', []);
  $groups = collect($groupKeys)->mapWithKeys(fn ($k) => [$k => (array) $pf->copy("expertise.groups.{$k}", [])]);
  $anyLevel = $groups->flatMap(fn ($g) => $g['items'] ?? [])->contains(fn ($i) => ! empty($i['level']));
@endphp

@section('content')
  <header class="pf-page-head pf-container">
    <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.expertise'), null]]" />
    <x-pf::section-label :number="1">{{ $pf->copy('expertise.label') }}</x-pf::section-label>
    <h1 class="pf-page-head__title pf-display" data-reveal>{{ $pf->copy('expertise.title') }}</h1>
    <p class="pf-page-head__lede" data-reveal>{{ $pf->copy('expertise.intro') }}</p>
    @unless ($anyLevel)
      <x-pf::editor-note>No proficiency levels are set, so none are shown. Add 'level' => 'confident' | 'working' | 'learning' to items in content/{{ $pf->locale }}.php → expertise.groups — only where accurate.</x-pf::editor-note>
    @endunless
  </header>

  <div class="pf-container pf-capabilities">
    @foreach ($groups as $key => $group)
      <section class="pf-capability" id="cap-{{ $key }}" aria-labelledby="cap-{{ $key }}-title" data-reveal>
        <div class="pf-capability__head">
          <span class="pf-capability__num pf-display">{{ $pf->num($loop->iteration) }}</span>
          <h2 id="cap-{{ $key }}-title" class="pf-capability__title pf-display">{{ $group['title'] ?? $key }}</h2>
          <p class="pf-capability__summary">{{ $group['summary'] ?? '' }}</p>
        </div>
        <ul class="pf-capability__list">
          @foreach ($group['items'] ?? [] as $item)
            <li>
              <span>{{ $item['name'] }}</span>
              @if (! empty($item['level']))
                <span class="pf-level pf-level--{{ $item['level'] }}">{{ $pf->t('level.'.$item['level']) }}</span>
              @endif
            </li>
          @endforeach
        </ul>
      </section>
    @endforeach
  </div>

  {{-- Capability × project matrix: shows how the disciplines actually overlap in real work. --}}
  <section class="pf-section pf-matrix" aria-labelledby="matrix-title">
    <div class="pf-container">
      <div class="pf-section-head">
        <x-pf::section-label :number="2">{{ $pf->copy('expertise.matrix.label') }}</x-pf::section-label>
        <h2 id="matrix-title" class="pf-section-head__title pf-display">{{ $pf->copy('expertise.matrix.caption') }}</h2>
      </div>

      <div class="pf-matrix__scroll" tabindex="0" role="region" aria-labelledby="matrix-title">
        <table class="pf-matrix__table">
          <thead>
            <tr>
              <td></td>
              @foreach ($projects as $project)
                <th scope="col"><a href="{{ $pf->url('work.show', ['project' => $project->slug]) }}" title="{{ $project->title($pf->locale) }}"><span class="pf-matrix__colnum">{{ $pf->num($loop->iteration) }}</span><span class="pf-matrix__coltitle">{{ $project->title($pf->locale) }}</span></a></th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($groups as $key => $group)
              <tr>
                <th scope="row">{{ $group['title'] ?? $key }}</th>
                @foreach ($projects as $project)
                  @php $on = in_array($key, $project->get('expertise', []), true); @endphp
                  <td @class(['is-on' => $on])>
                    @if ($on)<span class="pf-matrix__dot" aria-hidden="true"></span><span class="pf-visually-hidden">✓</span>@endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </section>

  {{-- Tools, deliberately separate from capabilities. --}}
  <section class="pf-section pf-tools" aria-labelledby="tools-title">
    <div class="pf-container pf-grid">
      <x-pf::section-label :number="3" class="pf-col-label">{{ $pf->copy('expertise.tools.label') }}</x-pf::section-label>
      <h2 id="tools-title" class="pf-tools__title pf-display">{{ $pf->copy('expertise.tools.title') }}</h2>
      <div class="pf-tools__groups">
        @foreach ((array) $pf->copy('expertise.tools.groups', []) as $group)
          <div class="pf-tools__group" data-reveal>
            <h3 class="pf-tools__group-title">{{ $group['title'] }}</h3>
            <ul>
              @foreach ($group['items'] as $tool)<li class="pf-display">{{ $tool }}</li>@endforeach
            </ul>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endsection

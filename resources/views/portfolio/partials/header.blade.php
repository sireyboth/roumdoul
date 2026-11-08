@php
    $nav = [
        'home' => 'nav.home',
        'work' => 'nav.work',
        'about' => 'nav.about',
        'expertise' => 'nav.expertise',
        'notes' => 'nav.notes_short',
        'contact' => 'nav.contact',
    ];
    $locales = config('portfolio.locales');
@endphp
<header class="pf-header" data-header>
  <div class="pf-container pf-header__inner">
    <x-pf::wordmark :href="$pf->url('home')" :name="$pf->name()" />

    <nav class="pf-nav" aria-label="{{ $pf->t('nav.primary') }}">
      <ul>
        @foreach ($nav as $page => $label)
          <li>
            <a href="{{ $pf->url($page) }}" @class(['is-active' => $pf->isActive($page)]) @if ($pf->page === $page) aria-current="page" @endif>
              <span class="pf-nav__num" aria-hidden="true">{{ $pf->num($loop->iteration) }}</span>{{ $pf->t($label) }}
            </a>
          </li>
        @endforeach
      </ul>
    </nav>

    <div class="pf-header__tools">
      @if (count($pf->portfolio->locales()) > 1)
        <div class="pf-lang" role="group" aria-label="{{ $pf->t('language.label') }}">
          @foreach ($pf->portfolio->locales() as $code)
            {{-- keep ?category=… etc. so filters survive a language switch --}}
            <a href="{{ $pf->switchUrl($code) }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" hreflang="{{ $locales[$code]['hreflang'] }}" lang="{{ $code }}"
               @class(['pf-lang__opt', 'is-active' => $code === $pf->locale]) @if ($code === $pf->locale) aria-current="true" @endif>{{ $locales[$code]['short'] }}</a>
          @endforeach
        </div>
      @endif

      <button type="button" class="pf-theme" data-theme-toggle
              data-label-light="{{ $pf->t('theme.switch_to_light') }}" data-label-dark="{{ $pf->t('theme.switch_to_dark') }}"
              aria-label="{{ $pf->t('theme.switch_to_dark') }}">
        <span class="pf-theme__icon" aria-hidden="true"></span>
        <span class="pf-theme__text pf-theme__text--light">{{ $pf->t('theme.light') }}</span>
        <span class="pf-theme__text pf-theme__text--dark">{{ $pf->t('theme.dark') }}</span>
      </button>

      <button type="button" class="pf-menu-btn" data-menu-toggle aria-expanded="false" aria-controls="pf-menu">
        <span class="pf-menu-btn__open">{{ $pf->t('nav.menu') }}</span>
        <span class="pf-menu-btn__close">{{ $pf->t('nav.close') }}</span>
      </button>
    </div>
  </div>

  {{-- Mobile / tablet menu: a full-height index, not a shrunken desktop bar. --}}
  <div class="pf-menu" id="pf-menu" data-menu hidden>
    <nav class="pf-container" aria-label="{{ $pf->t('nav.primary') }}">
      <ol class="pf-menu__list">
        @foreach ($nav as $page => $label)
          <li style="--i: {{ $loop->index }}">
            <a href="{{ $pf->url($page) }}" @class(['is-active' => $pf->isActive($page)]) @if ($pf->page === $page) aria-current="page" @endif>
              <span class="pf-menu__num" aria-hidden="true">{{ $pf->num($loop->iteration) }}</span>
              <span class="pf-menu__label">{{ $pf->t($page === 'notes' ? 'nav.notes' : $label) }}</span>
            </a>
          </li>
        @endforeach
      </ol>
      <p class="pf-menu__foot">{{ $pf->copy('footer.statement') }}</p>
    </nav>
  </div>
</header>

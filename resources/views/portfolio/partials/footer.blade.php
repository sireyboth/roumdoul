@php
    $socials = collect($pf->portfolio->get('socials', []))->filter(fn ($s) => ! empty($s['url']));
    $email = $pf->portfolio->get('contact.email');
@endphp
<footer class="pf-footer">
  <div class="pf-container">
    <div class="pf-footer__top">
      <p class="pf-footer__statement pf-display">{{ $pf->copy('footer.statement') }}</p>
      <a class="pf-footer__top-link" href="#main" data-to-top>{{ $pf->t('nav.back_to_top') }} ↑</a>
    </div>

    <div class="pf-footer__grid">
      <div class="pf-footer__mark">
        <x-pf::monogram :size="56" :label="$pf->name()" />
      </div>

      <div>
        <p class="pf-footer__head">{{ $pf->t('footer.index') }}</p>
        <ul class="pf-footer__list">
          @foreach (['home', 'work', 'about', 'expertise', 'notes', 'contact'] as $page)
            <li><a href="{{ $pf->url($page) }}">{{ $pf->t('nav.'.$page) }}</a></li>
          @endforeach
        </ul>
      </div>

      <div>
        <p class="pf-footer__head">{{ $pf->t('footer.elsewhere') }}</p>
        <ul class="pf-footer__list">
          @if ($email)
            <li><a href="mailto:{{ $email }}">{{ $email }}</a></li>
          @endif
          @forelse ($socials as $social)
            <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener me">{{ $social['label'] }} ↗</a></li>
          @empty
            @unless ($email)
              <li><a href="{{ $pf->url('contact') }}">{{ $pf->t('nav.contact') }}</a></li>
            @endunless
          @endforelse
        </ul>
        @if ($socials->isEmpty())
          <x-pf::editor-note>{{ 'Add social links in portfolios/'.$pf->portfolio->slug.'/portfolio.php → socials' }}</x-pf::editor-note>
        @endif
      </div>

      <div>
        <p class="pf-footer__head">{{ $pf->t('footer.colophon') }}</p>
        <p class="pf-footer__small">{{ $pf->t('footer.colophon_text') }}</p>
      </div>
    </div>

    <div class="pf-footer__base">
      <x-pf::wordmark :name="$pf->name()" />
      <span>© {{ $pf->num(now()->year, 4) }} {{ $pf->name() }}. {{ $pf->t('footer.rights') }}</span>
    </div>
  </div>
</footer>

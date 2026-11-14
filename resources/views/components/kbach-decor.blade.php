{{-- Khmer kbach decoration for a white card, kept to the top-right corner (behind the section
     title, never behind form fields). Put it first inside a `relative overflow-hidden` card and
     give the card's content `relative` so it sits above. Sizes/opacity are inline so the
     decoration can never render full-strength if the CSS build is stale. --}}
<div aria-hidden="true" class="pointer-events-none absolute right-0 top-0 dark:invert"
  style="width: 55%; height: 5.5rem; opacity: .08; background-image: url('{{ asset('images/kbach-pattern.webp') }}'); background-size: 180px auto; -webkit-mask-image: radial-gradient(ellipse at top right, #000 20%, transparent 75%); mask-image: radial-gradient(ellipse at top right, #000 20%, transparent 75%);"></div>
<img src="{{ asset('images/roumdolkbach.png') }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
  class="pointer-events-none absolute dark:opacity-20"
  style="right: -2rem; top: -2rem; width: 8.5rem; height: auto; opacity: .9;" />

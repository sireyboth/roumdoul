{{--
  Personal mark: the N's right stem doubles as the L's upright — two initials,
  one continuous line ("different disciplines, one way of thinking").
  The small square is the accent "full stop". Inherits currentColor.
--}}
@props(['size' => 28, 'label' => null])
<svg {{ $attributes->merge(['class' => 'pf-monogram']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40"
     @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif focusable="false">
  <path d="M8 32V8l14 24V8M22 32h11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter" vector-effect="non-scaling-stroke"/>
  <rect x="30" y="7" width="3.2" height="3.2" class="pf-monogram__dot"/>
</svg>

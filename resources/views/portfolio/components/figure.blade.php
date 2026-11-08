{{--
  Art-directed image slot. Always reserves its aspect ratio (no layout shift).
  With a real file: responsive <img>, lazy by default, optional lightbox.
  Without one: a quiet "image required" plate stating the expected shape —
  editors additionally see which file to add.

  <x-pf::figure src="projects/x/cover.jpg" ratio="16/10" label="Screenshot" alt="…" sizes="100vw" />
--}}
@props([
    'src' => null,
    'ratio' => '4/3',
    'label' => null,
    'alt' => '',
    'sizes' => '100vw',
    'eager' => false,
    'zoom' => null,      // lightbox group name, or null
    'caption' => null,
    'tone' => null,      // optional placeholder tone: 'deep' for dark plates
    'localized' => false, // true when `label` is already in the page language
    'focus' => null,      // crop focus point, e.g. '55% 30%' (x y) — keeps a face in frame
])
@php
    $image = $pf->image($src);
    $ratioLabel = str_replace('/', ':', $ratio);
    // Slot labels in project.php are written once (base language); don't show them on other-language pages.
    if ($pf->locale !== $pf->portfolio->baseLocale() && ! $pf->editorHints() && ! $localized) {
        $label = null;
    }
@endphp
<figure {{ $attributes->class(['pf-figure', 'pf-figure--empty' => ! $image, 'pf-figure--deep' => $tone === 'deep']) }}>
  <div class="pf-figure__frame" style="aspect-ratio: {{ $ratio }}">
    @if ($image)
      @if ($zoom)
        <button type="button" class="pf-figure__zoom" data-lightbox="{{ $zoom }}" data-full="{{ $image['src'] }}" data-caption="{{ $caption }}" aria-label="{{ $pf->t('common.enlarge') }}{{ $alt ? ': '.$alt : '' }}">
      @endif
      <img src="{{ $image['src'] }}"
           @if ($image['srcset']) srcset="{{ $image['srcset'] }}" sizes="{{ $sizes }}" @endif
           @if ($image['width']) width="{{ $image['width'] }}" height="{{ $image['height'] }}" @endif
           alt="{{ $alt }}"
           @if ($focus && preg_match('/^\d{1,3}% \d{1,3}%$/', $focus)) style="object-position: {{ $focus }}" @endif
           @if ($eager) fetchpriority="high" @else loading="lazy" @endif
           decoding="async">
      @if ($zoom)
        </button>
      @endif
    @else
      <div class="pf-placeholder" role="img" aria-label="{{ $pf->t('common.image_required') }}{{ $label ? ' — '.$label : '' }}">
        <svg class="pf-placeholder__marks" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
          <line x1="0" y1="0" x2="100" y2="100" /><line x1="100" y1="0" x2="0" y2="100" />
        </svg>
        <span class="pf-placeholder__text">
          <span class="pf-placeholder__title">{{ $pf->t('common.image_required') }}</span>
          <span class="pf-placeholder__spec">{{ $label ? $label.' · ' : '' }}{{ $ratioLabel }}</span>
          @if ($pf->editorHints() && $src)
            <span class="pf-placeholder__path">media/{{ $src }}</span>
          @endif
        </span>
      </div>
    @endif
  </div>
  @if ($caption)
    <figcaption class="pf-figure__caption">{{ $caption }}</figcaption>
  @endif
</figure>

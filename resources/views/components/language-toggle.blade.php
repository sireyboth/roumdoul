{{--
  Two-option language switch (ខ្មែរ | EN). Plain links, no wire:navigate: the switch saves the
  choice in the session and reloads the current page (same tab/filters) in the new language.
--}}
@php $current = app()->getLocale(); @endphp
<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-plum-100 p-0.5 dark:bg-plum-800']) }}
  role="group" aria-label="{{ __('Choose language') }}">
  @foreach (['km' => 'ខ្មែរ', 'en' => 'EN'] as $code => $name)
    <a href="{{ route('locale.switch', $code) }}" rel="nofollow" lang="{{ $code }}" data-lang-switch
      @if ($current === $code) aria-current="true" @endif
      @class([
        'rounded-full px-3 py-1 text-xs font-bold transition',
        'bg-white text-brand-700 shadow-sm dark:bg-plum-950 dark:text-brand-300' => $current === $code,
        'text-plum-500 hover:text-plum-800 dark:text-plum-400 dark:hover:text-white' => $current !== $code,
      ])>{{ $name }}</a>
  @endforeach
</div>

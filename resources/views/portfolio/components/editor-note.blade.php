{{-- Dashed note for the portfolio owner. Never rendered unless editor hints are on. --}}
@if ($pf->editorHints())
  <aside {{ $attributes->merge(['class' => 'pf-editor-note']) }} lang="{{ $pf->locale }}">
    <span class="pf-editor-note__label">{{ $pf->t('editor.label') }}</span>
    <span>{{ $slot }}</span>
  </aside>
@endif

@props(['status'])
<span {{ $attributes->merge(['class' => 'pf-status pf-status--'.$status]) }}>
  <span class="pf-status__dot" aria-hidden="true"></span>{{ $pf->t('status.'.$status) }}
</span>

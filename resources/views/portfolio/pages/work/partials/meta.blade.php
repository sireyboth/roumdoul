{{-- Project facts table, shared by all case-study heroes. --}}
<dl class="pf-case-meta {{ $class ?? '' }}">
  <div><dt>{{ $pf->t('common.category') }}</dt><dd><a href="{{ $pf->url('work', ['category' => $project->category()]) }}">{{ $discipline['title'] ?? '' }}</a></dd></div>
  <div><dt>{{ $pf->t('common.status') }}</dt><dd><x-pf::status :status="$project->status()" /></dd></div>
  @if ($date = $project->dateLabel($pf->locale))
    <div><dt>{{ $pf->t('common.date') }}</dt><dd>{{ $date }}</dd></div>
  @endif
  @if ($project->get('tech'))
    <div><dt>{{ $pf->t('common.tools') }}</dt><dd>{{ implode(', ', $project->get('tech')) }}</dd></div>
  @endif
  @if ($url = $project->get('url'))
    <div><dt>{{ $pf->t('common.link') }}</dt><dd><x-pf::arrow-link :href="$url" external>{{ $pf->t('common.visit') }}</x-pf::arrow-link></dd></div>
  @endif
</dl>

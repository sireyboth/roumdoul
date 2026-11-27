{{-- The parents' names: the groom's side and the bride's side next to each other.
     - cols: 2 (side by side) or 3 (with an ornament in the middle, drawn here as "&") --}}
@props(['cols' => 2])
@aware(['preview'])

@if ($preview->hasAny(['groom_father', 'groom_mother', 'bride_father', 'bride_mother']))
  <x-preview.columns :cols="$cols">
    <div>
      <x-preview.text key="groom_father" size="sm" />
      <x-preview.text key="groom_mother" size="sm" />
    </div>
    @if ((int) $cols === 3)
      <x-preview.fixed>&amp;</x-preview.fixed>
    @endif
    <div>
      <x-preview.text key="bride_father" size="sm" />
      <x-preview.text key="bride_mother" size="sm" />
    </div>
  </x-preview.columns>
@endif

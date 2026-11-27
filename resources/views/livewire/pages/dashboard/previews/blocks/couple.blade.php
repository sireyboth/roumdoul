{{-- THE COUPLE — the groom's and bride's photos side by side, each with its name under it.
     Options:
       photo       => circle | oval | arch | portrait | square   (default 'oval')
       bride_first => true when the template puts the bride first (default false)
       full_photo  => true: one big photo (cover_image) with both names first, like the Khmer templates
       parents     => true: "child of …" under each name (default false) --}}
@php
  $shape = $block['photo'] ?? 'oval';
  $people = ($block['bride_first'] ?? false) ? ['bride', 'groom'] : ['groom', 'bride'];
@endphp

<x-preview.section :label="__('The couple')" :show="$preview->hasAny(['groom_photo', 'bride_photo', 'groom_name', 'bride_name'])">
  @if ($block['full_photo'] ?? false)
    <x-preview.photo key="cover_image" shape="wide" />
    <x-preview.couple-names style="columns" size="lg" :bride-first="$block['bride_first'] ?? false" />
  @endif

  <x-preview.columns cols="2">
    @foreach ($people as $person)
      <div class="pv-person">
        <x-preview.photo :key="$person.'_photo'" :shape="$shape" size="sm" />
        <x-preview.name :key="$person.'_name'" size="lg" />
        @if ($block['parents'] ?? false)
          <x-preview.text :key="$person.'_father'" size="xs" />
          <x-preview.text :key="$person.'_mother'" size="xs" />
        @endif
      </div>
    @endforeach
  </x-preview.columns>
</x-preview.section>

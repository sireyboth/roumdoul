{{-- A placeholder box for a part of the template the preview doesn't draw in detail
     (a game, a cake, scratch cards…).
     Options:
       label => what it is (English, translated here)
       key   => optional field: when the template doesn't use it, the box isn't drawn --}}
<x-preview.section :show="! isset($block['key']) || $preview->has($block['key'])">
  <x-preview.box :label="__($block['label'])" />
</x-preview.section>

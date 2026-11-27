{{-- GALLERY — where the photo gallery sits and how it is arranged.
     Options:
       label => section name (default 'Gallery')
       cols  => 1 | 2 | 3   photos per row (default 2)
       count => how many tiles to draw (default 4)
       strip => true for a row that scrolls sideways (default false) --}}
<x-preview.section :label="__($block['label'] ?? 'Gallery')" :show="$preview->has('photo_gallery')">
  <x-preview.gallery :cols="$block['cols'] ?? 2" :count="$block['count'] ?? 4" :strip="$block['strip'] ?? false" />
</x-preview.section>

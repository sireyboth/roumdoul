{{-- A person's name, big, with its Latin-letter spelling under it when the template has that field.
     - key:  'groom_name' or 'bride_name' (the Latin field is the same key + '_latin')
     - size: the size of the main name (see <x-preview.text>) --}}
@props(['key', 'size' => 'xl'])

<div class="pv-name">
  <x-preview.text :key="$key" :size="$size" />
  <x-preview.text :key="$key.'_latin'" size="xs" />
</div>

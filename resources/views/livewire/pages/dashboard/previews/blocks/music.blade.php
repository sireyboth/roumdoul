{{-- NOW PLAYING — the music card with the song's name. --}}
<x-preview.section :label="__('Now playing')" :show="$preview->has('song_title')">
  <div class="pv-card pv-card--row">
    <span class="pv-tile pv-tile--sm"></span>
    <x-preview.text key="song_title" size="md" />
  </div>
</x-preview.section>

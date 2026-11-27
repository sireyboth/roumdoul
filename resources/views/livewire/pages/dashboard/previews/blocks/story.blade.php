{{-- OUR STORY — the story chapters, drawn as one box saying how the template shows them.
     Options:
       label => section name (default 'Our story')
       style => 'Timeline' | 'Cards' | 'Map with pins' | 'Photo strip' … (default 'Timeline') --}}
<x-preview.section :label="__($block['label'] ?? 'Our story')" :show="$preview->has('story_chapters')">
  <x-preview.box :label="$preview->label('story_chapters').' · '.__($block['style'] ?? 'Timeline')" :tall="true" />
</x-preview.section>

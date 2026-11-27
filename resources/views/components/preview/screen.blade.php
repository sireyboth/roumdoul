{{-- LIVE PREVIEW: the page inside the phone. Wraps every block so the small preview components below
     can read the preview data with @aware(['preview']) instead of it being passed to each one.
     $preview is an App\Support\Invitations\LivePreviewData. --}}
@props(['preview'])

<div class="pv-page">
  {{ $slot }}
</div>

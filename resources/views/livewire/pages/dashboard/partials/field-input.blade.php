{{-- One template field's input, picked by its FIELD_CATALOG type. Expects $key and $catalogField. --}}
@php $placeholder = ! empty($catalogField['example']) ? 'ឧ. '.$catalogField['example'] : null; @endphp
@switch($catalogField['type'])
  @case('textarea')
    <textarea id="input-{{ $key }}" wire:model="fieldValues.{{ $key }}" rows="4" @if ($placeholder) placeholder="{{ $placeholder }}" @endif
      class="w-full rounded-lg border border-plum-200 bg-transparent px-4 py-3 text-base focus:border-brand-500 focus:outline-none dark:border-plum-700"></textarea>
    @break

  @case('image')
    @php
      $tempImage = $imageUploads[$key] ?? null;
      $page = \App\Livewire\Pages\Dashboard\InvitationManagePage::class;
    @endphp
    <div class="max-w-lg" x-data="photoUploader({ name: 'imageUploads.{{ $key }}', maxMb: {{ $page::MAX_UPLOAD_KB / 1024 }}, gifMb: {{ $page::MAX_GIF_KB / 1024 }} })">
    <label
      for="upload-{{ $key }}"
      :class="busy && 'pointer-events-none'"
      class="group relative flex aspect-video w-full cursor-pointer flex-col items-center justify-center gap-1.5 overflow-hidden rounded-xl border-2 border-dashed border-plum-300 bg-plum-50 text-center transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-plum-700 dark:bg-plum-800/40 dark:hover:border-brand-500 dark:hover:bg-brand-500/5"
    >
      @if ($tempImage && $tempImage->isPreviewable())
        <img src="{{ $tempImage->temporaryUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover" />
      @elseif (! empty($fieldValues[$key]))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($fieldValues[$key]) }}" alt="" class="absolute inset-0 h-full w-full object-cover" />
      @endif

      @if ($tempImage || ! empty($fieldValues[$key]))
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/0 opacity-0 transition-all group-hover:bg-black/50 group-hover:opacity-100">
          <x-app-icon name="upload" class="h-5 w-5 text-white" />
          <span class="text-xs font-semibold text-white">Change photo</span>
        </div>
      @else
        <x-app-icon name="upload" class="h-6 w-6 text-plum-400 group-hover:text-brand-500" />
        <span class="text-xs font-semibold text-plum-600 dark:text-plum-300">Click to upload photo</span>
        <span class="text-[11px] text-plum-400">JPG, PNG or WebP &middot; up to 20 MB</span>
      @endif

      <div x-show="busy" style="display: none" class="pointer-events-auto absolute inset-0 flex flex-col items-center justify-center gap-2 bg-white/85 dark:bg-plum-900/85">
        <span class="text-xs font-semibold text-plum-600 dark:text-plum-300" x-text="preparing ? 'Preparing photo…' : `Uploading… ${progress}%`"></span>
        <div class="h-1.5 w-2/3 overflow-hidden rounded-full bg-plum-200 dark:bg-plum-700">
          <div class="h-full bg-brand-500 transition-all" :style="`width: ${progress}%`"></div>
        </div>
        <button type="button" x-on:click.prevent="cancel()" class="text-[11px] font-semibold text-plum-500 underline hover:text-red-600">Cancel</button>
      </div>

      <input id="upload-{{ $key }}" type="file" x-on:change="pick($event)" :disabled="busy" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" />
    </label>
    <p x-show="error" x-text="error" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400"></p>
    @error('imageUploads.'.$key) <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
    @break

  @case('gallery')
    @php
      $page = \App\Livewire\Pages\Dashboard\InvitationManagePage::class;
      $galleryRoom = max(0, $page::MAX_GALLERY_PHOTOS - count($fieldValues[$key] ?? []) - count($galleryUploads[$key] ?? []));
    @endphp
    <div data-room="{{ $galleryRoom }}"
      x-data="photoUploader({ name: 'galleryUploads.{{ $key }}', multiple: true, maxMb: {{ $page::MAX_UPLOAD_KB / 1024 }}, gifMb: {{ $page::MAX_GIF_KB / 1024 }} })">
    <div class="flex flex-wrap gap-2">
      @foreach (($fieldValues[$key] ?? []) as $i => $path)
        <div wire:key="gallery-{{ $key }}-{{ $i }}" class="relative">
          <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($path) }}" alt="" class="h-28 w-28 rounded-lg object-cover" />
          <button type="button" wire:click="removeGalleryImage('{{ $key }}', {{ $i }})"
            class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white shadow">&times;</button>
        </div>
      @endforeach

      @foreach ((($galleryUploads[$key] ?? [])) as $i => $tempFile)
        @continue(! $tempFile->isPreviewable())
        <div wire:key="gallery-pending-{{ $key }}-{{ $i }}" class="relative">
          <img src="{{ $tempFile->temporaryUrl() }}" alt="" class="h-28 w-28 rounded-lg object-cover opacity-60" />
        </div>
      @endforeach

      @if ($galleryRoom > 0)
        <label
          for="upload-{{ $key }}"
          :class="busy && 'pointer-events-none opacity-50'"
          class="group flex h-28 w-28 shrink-0 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-plum-300 bg-plum-50 text-center transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-plum-700 dark:bg-plum-800/40 dark:hover:border-brand-500 dark:hover:bg-brand-500/5"
        >
          <x-app-icon name="upload" class="h-5 w-5 text-plum-400 group-hover:text-brand-500" />
          <span class="text-xs font-semibold text-plum-600 dark:text-plum-300">Add photos</span>
          <input id="upload-{{ $key }}" type="file" x-on:change="pick($event)" :disabled="busy" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" />
        </label>
      @endif
    </div>
    <div x-show="busy" style="display: none" class="mt-2 flex items-center gap-3">
      <div class="h-1.5 w-40 overflow-hidden rounded-full bg-plum-200 dark:bg-plum-700">
        <div class="h-full bg-brand-500 transition-all" :style="`width: ${progress}%`"></div>
      </div>
      <span class="text-xs font-semibold text-plum-500 dark:text-plum-400" x-text="preparing ? 'Preparing photos…' : `Uploading… ${progress}%`"></span>
      <button type="button" x-on:click="cancel()" class="text-[11px] font-semibold text-plum-500 underline hover:text-red-600">Cancel</button>
    </div>
    <p x-show="error" x-text="error" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400"></p>
    <p class="mt-1.5 text-[11px] text-plum-400">{{ $galleryRoom }} of {{ $page::MAX_GALLERY_PHOTOS }} spots left &middot; JPG, PNG or WebP &middot; big photos are shrunk automatically</p>
    {{-- per-file errors land on galleryUploads.{key}.{n}; show each distinct message once --}}
    @foreach (collect([...$errors->get('galleryUploads.'.$key), ...\Illuminate\Support\Arr::flatten($errors->get('galleryUploads.'.$key.'.*'))])->unique() as $message)
      <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @endforeach
    </div>
    @break

  @case('schedule')
    <div class="flex flex-col gap-2">
      @foreach (($fieldValues[$key] ?? []) as $i => $row)
        <div wire:key="schedule-{{ $key }}-{{ $i }}" class="flex gap-2">
          <input type="text" wire:model="fieldValues.{{ $key }}.{{ $i }}.time" placeholder="5:00 PM"
            class="w-28 shrink-0 rounded-lg border border-plum-200 bg-transparent px-3 py-2 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
          <input type="text" wire:model="fieldValues.{{ $key }}.{{ $i }}.label" placeholder="Ceremony begins"
            class="flex-1 rounded-lg border border-plum-200 bg-transparent px-3 py-2 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
          <button type="button" wire:click="removeScheduleItem('{{ $key }}', {{ $i }})" class="shrink-0 text-plum-400 hover:text-red-600">
            <x-app-icon name="trash" class="h-4 w-4" />
          </button>
        </div>
      @endforeach
      <button type="button" wire:click="addScheduleItem('{{ $key }}')"
        class="w-fit rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
        + Add item
      </button>
    </div>
    @break

  @case('story')
    <div class="flex flex-col gap-3">
      @foreach (($fieldValues[$key] ?? []) as $i => $row)
        <div wire:key="story-{{ $key }}-{{ $i }}" class="flex gap-2">
          <div class="flex flex-1 flex-col gap-2">
            <input type="text" wire:model="fieldValues.{{ $key }}.{{ $i }}.title" placeholder="Chapter title"
              class="w-full rounded-lg border border-plum-200 bg-transparent px-3 py-2 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
            <textarea wire:model="fieldValues.{{ $key }}.{{ $i }}.text" rows="2" placeholder="What happened in this chapter?"
              class="w-full rounded-lg border border-plum-200 bg-transparent px-3 py-2 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700"></textarea>
          </div>
          <button type="button" wire:click="removeStoryItem('{{ $key }}', {{ $i }})" class="shrink-0 self-start pt-2 text-plum-400 hover:text-red-600">
            <x-app-icon name="trash" class="h-4 w-4" />
          </button>
        </div>
      @endforeach
      <button type="button" wire:click="addStoryItem('{{ $key }}')"
        class="w-fit rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
        + Add chapter
      </button>
    </div>
    @break

  @case('boolean')
    <span class="relative inline-flex shrink-0 cursor-pointer items-center">
      <input id="input-{{ $key }}" type="checkbox" wire:model="fieldValues.{{ $key }}" class="peer sr-only" />
      <span class="h-7 w-12 rounded-full bg-plum-300 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 dark:bg-plum-700"></span>
      <span class="pointer-events-none absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
    </span>
    @break

  @case('datetime')
    <input id="input-{{ $key }}" type="datetime-local" wire:model="fieldValues.{{ $key }}"
      class="w-full rounded-lg border border-plum-200 bg-transparent px-4 py-3 text-base focus:border-brand-500 focus:outline-none dark:border-plum-700" />
    @break

  @case('color')
    <input id="input-{{ $key }}" type="color" wire:model="fieldValues.{{ $key }}" class="h-12 w-full cursor-pointer rounded-lg border border-plum-200 bg-transparent p-1 dark:border-plum-700" />
    @break

  @default
    <input id="input-{{ $key }}" type="text" wire:model="fieldValues.{{ $key }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif
      class="w-full rounded-lg border border-plum-200 bg-transparent px-4 py-3 text-base focus:border-brand-500 focus:outline-none dark:border-plum-700" />
@endswitch

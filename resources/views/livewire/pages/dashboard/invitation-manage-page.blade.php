<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
  <a href="/dashboard" wire:navigate class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-plum-500 hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">
    <x-app-icon name="chevron-left" class="h-4 w-4" /> Back to dashboard
  </a>

  <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white">{{ $invitation->template->name }}</h1>
  <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">
    {{ $invitation->recipients->count() }} / {{ $invitation->max_recipients }} recipients
    @if ($invitation->expires_at)
      &middot; expires {{ $invitation->expires_at->format('d M Y') }}
    @endif
  </p>

  @if (! $invitation->isPaid())
    <div class="mt-8 flex flex-col items-center gap-3 rounded-lg border border-dashed border-gold-400 bg-gold-500/10 py-16 text-center">
      <x-app-icon name="clock" class="h-10 w-10 text-gold-600" />
      <p class="text-base font-semibold text-plum-800 dark:text-plum-100">Waiting for payment confirmation</p>
      <p class="max-w-sm text-sm text-plum-500 dark:text-plum-400">
        You'll be able to fill in details and add recipients as soon as your order is confirmed as paid.
      </p>
    </div>
  @else
  {{-- Details form --}}
  <div class="mt-8 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <h2 class="mb-4 text-base font-bold text-plum-900 dark:text-white">Details</h2>

    <form wire:submit="save" class="flex flex-col gap-4">
      @foreach ($invitation->template->fields ?? [] as $key)
        @php $catalogField = $fieldCatalog[$key] ?? null; @endphp
        @continue(! $catalogField)

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $catalogField['label'] }}</label>

          @switch($catalogField['type'])
            @case('textarea')
              <textarea wire:model="fieldValues.{{ $key }}" rows="3"
                class="w-full rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700"></textarea>
              @break

            @case('image')
              @php $tempImage = $imageUploads[$key] ?? null; @endphp
              <label
                for="upload-{{ $key }}"
                class="group relative flex aspect-video w-full cursor-pointer flex-col items-center justify-center gap-1.5 overflow-hidden rounded-xl border-2 border-dashed border-plum-300 bg-plum-50 text-center transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-plum-700 dark:bg-plum-800/40 dark:hover:border-brand-500 dark:hover:bg-brand-500/5"
              >
                @if ($tempImage)
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
                  <span class="text-[11px] text-plum-400">PNG or JPG</span>
                @endif

                <div wire:loading wire:target="imageUploads.{{ $key }}" class="absolute inset-0 flex items-center justify-center bg-white/85 dark:bg-plum-900/85">
                  <span class="text-xs font-semibold text-plum-600 dark:text-plum-300">Uploading&hellip;</span>
                </div>

                <input id="upload-{{ $key }}" type="file" wire:model="imageUploads.{{ $key }}" accept="image/*" class="sr-only" />
              </label>
              @error('imageUploads.'.$key) <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
              @break

            @case('gallery')
              <div class="flex flex-wrap gap-2">
                @foreach (($fieldValues[$key] ?? []) as $i => $path)
                  <div wire:key="gallery-{{ $key }}-{{ $i }}" class="relative">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($path) }}" alt="" class="h-20 w-20 rounded-lg object-cover" />
                    <button type="button" wire:click="removeGalleryImage('{{ $key }}', {{ $i }})"
                      class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white shadow">&times;</button>
                  </div>
                @endforeach

                @foreach ((($galleryUploads[$key] ?? [])) as $i => $tempFile)
                  <div wire:key="gallery-pending-{{ $key }}-{{ $i }}" class="relative">
                    <img src="{{ $tempFile->temporaryUrl() }}" alt="" class="h-20 w-20 rounded-lg object-cover opacity-60" />
                  </div>
                @endforeach

                <label
                  for="upload-{{ $key }}"
                  class="group flex h-20 w-20 shrink-0 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-plum-300 bg-plum-50 text-center transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-plum-700 dark:bg-plum-800/40 dark:hover:border-brand-500 dark:hover:bg-brand-500/5"
                >
                  <x-app-icon name="upload" class="h-5 w-5 text-plum-400 group-hover:text-brand-500" />
                  <span class="text-[10px] font-semibold text-plum-600 dark:text-plum-300">Add photos</span>
                  <input id="upload-{{ $key }}" type="file" wire:model="galleryUploads.{{ $key }}" multiple accept="image/*" class="sr-only" />
                </label>
              </div>
              <div wire:loading wire:target="galleryUploads.{{ $key }}" class="mt-1.5 text-xs font-semibold text-plum-500 dark:text-plum-400">Uploading&hellip;</div>
              @error('galleryUploads.'.$key) <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
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

            @case('boolean')
              <input type="checkbox" wire:model="fieldValues.{{ $key }}" class="rounded border-plum-300" />
              @break

            @case('datetime')
              <input type="datetime-local" wire:model="fieldValues.{{ $key }}"
                class="w-full rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
              @break

            @case('color')
              <input type="color" wire:model="fieldValues.{{ $key }}" class="h-10 w-16 rounded border border-plum-200 dark:border-plum-700" />
              @break

            @default
              <input type="text" wire:model="fieldValues.{{ $key }}"
                class="w-full rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
          @endswitch
        </div>
      @endforeach

      <button type="submit"
        class="mt-2 flex w-fit items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-700">
        <span wire:loading.remove wire:target="save">Save</span>
        <span wire:loading wire:target="save">Saving...</span>
      </button>
      @if ($saved)
        <p class="text-sm font-semibold text-green-600 dark:text-green-400">Saved!</p>
      @endif
    </form>
  </div>

  {{-- Recipients --}}
  <div class="mt-6 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <h2 class="mb-4 text-base font-bold text-plum-900 dark:text-white">Recipients</h2>

    <div class="flex flex-col gap-2">
      @forelse ($invitation->recipients as $recipient)
        <div wire:key="recipient-{{ $recipient->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-plum-200 p-3 dark:border-plum-700">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $recipient->recipient_name }}</p>
            <p class="truncate text-xs text-plum-400">{{ route('invitation.show', [$invitation, $recipient]) }}</p>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <button type="button" x-data @click="navigator.clipboard.writeText('{{ route('invitation.show', [$invitation, $recipient]) }}')"
              class="rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
              Copy link
            </button>
            <button type="button" wire:click="removeRecipient({{ $recipient->id }})"
              class="text-plum-400 hover:text-red-600">
              <x-app-icon name="trash" class="h-4 w-4" />
            </button>
          </div>
        </div>
      @empty
        <p class="text-sm text-plum-500 dark:text-plum-400">No recipients yet — add one below.</p>
      @endforelse
    </div>

    @if ($invitation->recipients->count() < $invitation->max_recipients)
      <form wire:submit="addRecipient" class="mt-4 flex gap-2">
        <input type="text" wire:model="newRecipientName" placeholder="Recipient's name"
          wire:loading.attr="disabled" wire:target="addRecipient"
          class="flex-1 rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none disabled:opacity-50 dark:border-plum-700" />
        <button type="submit" wire:loading.attr="disabled" wire:target="addRecipient"
          class="shrink-0 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
          <span wire:loading.remove wire:target="addRecipient">Add</span>
          <span wire:loading wire:target="addRecipient">Adding...</span>
        </button>
      </form>
      @error('newRecipientName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    @else
      <p class="mt-4 text-sm text-plum-500 dark:text-plum-400">You've used all {{ $invitation->max_recipients }} recipient links on this plan.</p>
    @endif
  </div>
  @endif
</div>

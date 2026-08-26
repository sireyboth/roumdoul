    <section id="pricing" class="scroll-mt-20 bg-gray-50 py-16 transition-colors dark:bg-gray-900 sm:py-20">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-2xl font-bold text-green-800 dark:text-white sm:text-3xl">កញ្ចប់សេវាកម្ម</h2>
          <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 sm:text-base">
            ជ្រើសរើសកញ្ចប់ដែលសមស្របនឹងតម្រូវការសុវត្ថិភាពរបស់អ្នក។ រាល់កញ្ចប់អាចលៃតម្រូវបានតាមទំហំ និងលក្ខណៈជាក់លាក់នៃទីតាំង —
            ទាក់ទងមកយើងសម្រាប់សម្រង់តម្លៃត្រូវនឹងតម្រូវការជាក់ស្តែងរបស់អ្នក។
          </p>
        </div>

        <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
          @foreach ($this->packages() as $package)
            {{-- Outer wrapper stays un-clipped so the "Most Popular" badge can
                 float above the card edge without overflow-hidden (on the
                 card below) slicing it off. --}}
            <div wire:key="pricing-{{ $package['key'] }}" class="relative flex flex-col {{ $package['highlighted'] ? 'lg:-my-3' : '' }}">
              @if ($package['highlighted'])
                <span
                  class="absolute -top-3 left-1/2 z-10 -translate-x-1/2 rounded-full bg-amber-400 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-green-950 shadow-sm">
                  ពេញនិយមបំផុត
                </span>
              @endif

              <div
                class="flex flex-1 flex-col overflow-hidden rounded-xl border bg-white transition-colors dark:bg-gray-950 {{ $package['highlighted']
                    ? 'border-green-800 shadow-lg shadow-green-900/10 dark:border-amber-400'
                    : 'border-gray-200 dark:border-gray-800' }}">

              {{-- Header pill: tier name, kept distinct from the body with a solid brand-colored band --}}
              <div class="px-6 py-6 text-center {{ $package['highlighted']
                  ? 'bg-green-800 dark:bg-green-900'
                  : 'bg-gray-100 dark:bg-gray-900' }}">
                <h3 class="text-lg font-bold {{ $package['highlighted'] ? 'text-white' : 'text-green-800 dark:text-white' }}">
                  {{ $package['name'] }}
                </h3>
                <p class="mt-1 text-xs {{ $package['highlighted'] ? 'text-green-100' : 'text-gray-500 dark:text-gray-400' }}">
                  {{ $package['subtitle'] }}
                </p>
                <p class="mt-4 text-sm font-semibold {{ $package['highlighted'] ? 'text-amber-300' : 'text-green-800 dark:text-amber-400' }}">
                  សម្រង់តម្លៃតាមតម្រូវការ
                </p>
              </div>

              <div class="flex flex-1 flex-col gap-4 p-6">
                <ul class="flex flex-col gap-2.5 text-sm text-gray-600 dark:text-gray-400">
                  @foreach ($package['included'] as $feature)
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0 text-green-800 dark:text-amber-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                      </svg>
                      <span>{{ $feature }}</span>
                    </li>
                  @endforeach

                  @foreach ($package['excluded'] as $feature)
                    <li class="flex items-start gap-2 opacity-60">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      <span>{{ $feature }}</span>
                    </li>
                  @endforeach
                </ul>

                {{-- Dotted separator between the feature checklist and the CTA --}}
                <div class="mt-auto border-t border-dashed border-gray-200 pt-4 dark:border-gray-800">
                  <a href="{{ $package['cta_href'] }}" wire:navigate
                    class="flex w-full items-center justify-center rounded-full bg-green-800 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-green-700">
                    ស្នើសុំសម្រង់តម្លៃ
                  </a>
                </div>
              </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

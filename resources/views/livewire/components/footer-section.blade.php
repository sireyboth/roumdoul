  <footer id="contact" class="border-t border-plum-200 bg-white transition-colors dark:border-plum-800 dark:bg-plum-900">
    {{-- Trust strip --}}
    <div class="border-b border-plum-100 bg-brand-50/60 dark:border-plum-800 dark:bg-plum-800/40">
      <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-6 text-center sm:px-6 lg:grid-cols-4 lg:px-8">
        <div class="flex flex-col items-center gap-2">
          <x-app-icon name="bolt" class="h-6 w-6 text-brand-600 dark:text-brand-300" />
          <span class="text-xs font-semibold text-plum-700 dark:text-plum-200">ដឹកជញ្ជូនភ្លាមៗ</span>
        </div>
        <div class="flex flex-col items-center gap-2">
          <x-app-icon name="shield-check" class="h-6 w-6 text-brand-600 dark:text-brand-300" />
          <span class="text-xs font-semibold text-plum-700 dark:text-plum-200">ការទូទាត់ដោយសុវត្ថិភាព</span>
        </div>
        <div class="flex flex-col items-center gap-2">
          <x-app-icon name="chat" class="h-6 w-6 text-brand-600 dark:text-brand-300" />
          <span class="text-xs font-semibold text-plum-700 dark:text-plum-200">គាំទ្រអតិថិជន ២៤/៧</span>
        </div>
        <div class="flex flex-col items-center gap-2">
          <x-app-icon name="heart" class="h-6 w-6 text-brand-600 dark:text-brand-300" />
          <span class="text-xs font-semibold text-plum-700 dark:text-plum-200">ទុកចិត្តដោយអតិថិជនរាប់ពាន់នាក់</span>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-4 sm:col-span-2 lg:col-span-1">
          <a href="/" wire:navigate class="flex items-center gap-2.5">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-br from-brand-500 to-brand-800 text-lg font-bold text-white">រ</span>
            <span class="text-lg font-extrabold tracking-tight text-brand-800 dark:text-white">ROUMDOUL</span>
          </a>
          <p class="text-sm leading-relaxed text-plum-600 dark:text-plum-400">
            រំដួល &mdash; ហាងសេវាកម្មឌីជីថលកម្រិតខ្ពស់ សម្រាប់ Gemini Pro, Envato, Netflix និងកម្មវិធីល្បីៗជាច្រើនទៀត ដោយសុវត្ថិភាព និងតម្លៃសមរម្យ។
          </p>
          <div class="flex items-center gap-3">
            <a data-social="facebook" href="#" target="_blank" rel="noopener noreferrer" aria-label="Facebook"
              class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-brand-700 transition-colors hover:bg-brand-700 hover:text-white dark:bg-plum-800 dark:text-brand-300 dark:hover:bg-brand-700 dark:hover:text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4.5 w-4.5">
                <path d="M22 12.06C22 6.505 17.523 2 12 2S2 6.505 2 12.06c0 5.02 3.657 9.184 8.438 9.94v-7.03H7.898v-2.91h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.631.772-1.631 1.563v1.877h2.773l-.443 2.91h-2.33V22c4.78-.756 8.438-4.92 8.438-9.94z" />
              </svg>
            </a>
            <a data-social="instagram" href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram"
              class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-brand-700 transition-colors hover:bg-brand-700 hover:text-white dark:bg-plum-800 dark:text-brand-300 dark:hover:bg-brand-700 dark:hover:text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4.5 w-4.5">
                <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M12 2c-2.716 0-3.056.012-4.123.06-1.064.05-1.79.218-2.427.465a4.9 4.9 0 00-1.771 1.153A4.9 4.9 0 002.525 5.45c-.247.637-.416 1.363-.465 2.427C2.012 8.944 2 9.284 2 12s.012 3.056.06 4.123c.05 1.064.218 1.79.465 2.427a4.9 4.9 0 001.153 1.771 4.9 4.9 0 001.771 1.153c.637.247 1.363.416 2.427.465C8.944 21.988 9.284 22 12 22s3.056-.012 4.123-.06c1.064-.05 1.79-.218 2.427-.465a4.9 4.9 0 001.771-1.153 4.9 4.9 0 001.153-1.771c.247-.637.416-1.363.465-2.427.048-1.067.06-1.407.06-4.123s-.012-3.056-.06-4.123c-.05-1.064-.218-1.79-.465-2.427a4.9 4.9 0 00-1.153-1.771A4.9 4.9 0 0018.55 2.525c-.637-.247-1.363-.416-2.427-.465C15.056 2.012 14.716 2 12 2zm0 1.802c2.67 0 2.986.01 4.04.059.976.045 1.505.207 1.858.344.467.182.8.399 1.15.748.35.35.566.683.748 1.15.137.353.3.882.344 1.858.048 1.054.059 1.37.059 4.04s-.01 2.986-.059 4.04c-.045.976-.207 1.505-.344 1.858a3.1 3.1 0 01-.748 1.15 3.1 3.1 0 01-1.15.748c-.353.137-.882.3-1.858.344-1.054.048-1.37.059-4.04.059s-2.987-.01-4.04-.059c-.976-.045-1.505-.207-1.858-.344a3.1 3.1 0 01-1.15-.748 3.1 3.1 0 01-.748-1.15c-.137-.353-.3-.882-.344-1.858-.048-1.054-.059-1.37-.059-4.04s.01-2.986.059-4.04c.045-.976.207-1.505.344-1.858.182-.467.399-.8.748-1.15a3.1 3.1 0 011.15-.748c.353-.137.882-.3 1.858-.344 1.054-.048 1.37-.059 4.04-.059zm0 3.064a5.135 5.135 0 100 10.269 5.135 5.135 0 000-10.27zm0 8.468a3.333 3.333 0 110-6.666 3.333 3.333 0 010 6.666zm6.538-8.671a1.2 1.2 0 11-2.4 0 1.2 1.2 0 012.4 0z" />
              </svg>
            </a>
            <a data-social="tiktok" href="#" target="_blank" rel="noopener noreferrer" aria-label="TikTok"
              class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-brand-700 transition-colors hover:bg-brand-700 hover:text-white dark:bg-plum-800 dark:text-brand-300 dark:hover:bg-brand-700 dark:hover:text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4.5 w-4.5">
                <path d="M16.5 2h-3.02v13.44a2.7 2.7 0 11-2.7-2.7c.157 0 .31.012.46.036V9.7a5.72 5.72 0 105.72 5.72V9.15a7.06 7.06 0 003.55 1.02V7.16a4.16 4.16 0 01-4.01-4.16V2z" />
              </svg>
            </a>
            <a data-social="telegram" href="#" target="_blank" rel="noopener noreferrer" aria-label="Telegram"
              class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-brand-700 transition-colors hover:bg-brand-700 hover:text-white dark:bg-plum-800 dark:text-brand-300 dark:hover:bg-brand-700 dark:hover:text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4.5 w-4.5">
                <path d="M21.05 2.927a1.5 1.5 0 00-1.523-.245L2.98 9.36a1.31 1.31 0 00.088 2.457l4.377 1.462 1.68 5.393a1 1 0 001.697.372l2.457-2.457 4.522 3.34a1.5 1.5 0 002.37-.94l2.334-14.09a1.5 1.5 0 00-.452-1.61zM9.66 13.996l-.33 3.386-1.36-4.36 9.09-6.99c.19-.146.44.08.28.25l-7.4 7.4a.87.87 0 00-.28.314z" />
              </svg>
            </a>
          </div>
        </div>

        <div class="flex flex-col gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-brand-800 dark:text-white">ប្រភេទផលិតផល</h3>
          <ul class="flex flex-col gap-2 text-sm">
            @foreach ($categories as $category)
              <li>
                <a href="/shop/{{ $category->slug }}" wire:navigate
                  class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">
                  {{ $category->name_km }}
                </a>
              </li>
            @endforeach
          </ul>
        </div>

        <div class="flex flex-col gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-brand-800 dark:text-white">ក្រុមហ៊ុន</h3>
          <ul class="flex flex-col gap-3 text-sm">
            <li><a href="/" wire:navigate class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">ទំព័រដើម</a></li>
            <li><a href="/shop" wire:navigate class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">ហាង</a></li>
            <li><a href="/about" wire:navigate class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">អំពីយើង</a></li>
            <li><a href="/contact" wire:navigate class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">ទំនាក់ទំនង</a></li>
            <li class="flex items-start gap-2 pt-2">
              <x-app-icon name="envelope" class="mt-0.5 h-4 w-4 shrink-0 text-plum-400" />
              <a id="footer-email-link" href="#" target="_blank" rel="noopener noreferrer"
                class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">
                <span id="footer-email-text"></span>
              </a>
            </li>
            <li class="flex items-start gap-2">
              <x-app-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0 text-plum-400" />
              <a id="footer-phone-link" href="#" target="_blank" rel="noopener noreferrer"
                class="text-plum-600 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">
                <span id="footer-phone-text"></span>
              </a>
            </li>
          </ul>
        </div>

        <div class="flex flex-col gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-brand-800 dark:text-white">ព័ត៌មានថ្មីៗ</h3>
          <p class="text-sm text-plum-600 dark:text-plum-400">ចុះឈ្មោះដើម្បីទទួលបានការបញ្ចុះតម្លៃពិសេស</p>
          <form class="flex flex-col gap-2" onsubmit="return false">
            <input type="email" placeholder="អ៊ីមែលរបស់អ្នក"
              class="w-full rounded-full border border-plum-200 bg-plum-50 px-4 py-2 text-sm outline-none transition-colors placeholder:text-plum-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:placeholder:text-plum-500" />
            <button type="submit"
              class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
              ចុះឈ្មោះ
            </button>
          </form>
        </div>
      </div>

      <div class="mt-12 flex flex-col items-center gap-8 border-t border-plum-200 pt-8 text-center dark:border-plum-800">
        <p class="text-xs text-plum-500 dark:text-plum-400">&copy; <span id="footer-year"></span> ROUMDOUL. រក្សាសិទ្ធិគ្រប់យ៉ាង។</p>

        <div class="flex flex-col items-center gap-6 border-t border-plum-200 pt-8 dark:border-plum-800">
          <div class="flex flex-col items-center gap-1.5">
            <img src="/images/Roumdoul_Logo.png" alt="Romdoul" loading="lazy" class="h-6 w-6 object-contain" />
            <span class="text-[10px] font-semibold uppercase tracking-widest text-brand-800 dark:text-brand-300">Digital Design By</span>
            <span class="text-sm font-bold text-brand-800 dark:text-brand-300">រំដួល</span>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-800 dark:text-brand-300">Roumdoul</span>
          </div>

          <div class="flex flex-col items-center gap-2 text-sm text-plum-600 dark:text-plum-400">
            <a data-footer-phone href="#" class="transition-colors hover:text-brand-700 dark:hover:text-gold-400"></a>
            <a data-footer-phone href="#" class="transition-colors hover:text-brand-700 dark:hover:text-gold-400"></a>
            <a data-footer-phone href="#" class="transition-colors hover:text-brand-700 dark:hover:text-gold-400"></a>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <footer id="contact" class="border-t border-gray-200 bg-white transition-colors dark:border-gray-800 dark:bg-gray-900">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-10 text-center sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col items-center gap-4 sm:col-span-2 lg:col-span-1">
          <a href="/" wire:navigate class="flex items-center gap-2">
            <img src="/images/SJL Logo-01.png" alt="JingLong Security" loading="lazy" class="h-10 w-10 object-contain" />
            <span class="text-lg font-bold uppercase text-green-800 dark:text-white">JingLong Security</span>
          </a>
          <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">សេវាកម្មសន្តិសុខសម្រាប់សហគមន៍</p>
        </div>

        <div class="flex flex-col items-center gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-green-800 dark:text-white">តំណភ្ជាប់រហ័ស</h3>
          <ul class="flex flex-col items-center gap-2 text-sm">
            <li><a href="/" wire:navigate class="text-gray-600 transition-colors hover:text-green-800 dark:text-gray-400 dark:hover:text-white">ទំព័រដើម</a></li>
            <li><a href="/services" wire:navigate class="text-gray-600 transition-colors hover:text-green-800 dark:text-gray-400 dark:hover:text-white">សេវាកម្ម</a></li>
            <li><a href="/about" wire:navigate class="text-gray-600 transition-colors hover:text-green-800 dark:text-gray-400 dark:hover:text-white">អំពីយើង</a></li>
            <li><a href="/contact" wire:navigate class="text-gray-600 transition-colors hover:text-green-800 dark:text-gray-400 dark:hover:text-white">ទំនាក់ទំនង</a></li>
          </ul>
        </div>

        <div class="flex flex-col items-center gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-green-800 dark:text-white">ទំនាក់ទំនង</h3>
          <ul class="flex flex-col items-center gap-3 text-sm">
            <li class="flex items-start gap-2.5">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" class="mt-0.5 h-4 w-4 -shrink-0 text-gray-400 dark:text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
              </svg>
              <a id="footer-email-link" href="#" target="_blank" rel="noopener noreferrer"
                class="text-gray-600 transition-colors hover:text-green-800 dark:text-gray-400 dark:hover:text-white">
                <span id="footer-email-text"></span>
              </a>
            </li>
            <li class="flex items-start gap-2.5">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" class="mt-0.5 h-4 w-4 -shrink-0 text-gray-400 dark:text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
              </svg>
              <a id="footer-phone-link" href="#" target="_blank" rel="noopener noreferrer"
                class="text-gray-600 transition-colors hover:text-green-800 dark:text-gray-400 dark:hover:text-white">
                <span id="footer-phone-text"></span>
              </a>
            </li>
            <li class="flex items-start gap-2.5">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" class="mt-0.5 h-4 w-4 -shrink-0 text-gray-400 dark:text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
              </svg>
              <span class="text-gray-600 dark:text-gray-400">ភ្នំពេញ, កម្ពុជា</span>
            </li>
          </ul>
        </div>

        <div class="flex flex-col items-center gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-green-800 dark:text-white">ផ្ញើសំណើ</h3>
          <div class="w-full">
            <livewire:components.contact-form />
          </div>
        </div>
      </div>

      <div class="mt-12 flex flex-col items-center gap-8 border-t border-gray-200 pt-8 text-center dark:border-gray-800">
        <p class="text-xs text-gray-500 dark:text-gray-400">&copy; <span id="footer-year"></span> JINGLONG SECURITY CO.,LTD រក្សាសិទ្ធិគ្រប់យ៉ាង។</p>

        <div class="flex flex-col items-center gap-6 border-t border-gray-200 pt-8 dark:border-gray-800">
        <div class="flex flex-col items-center gap-1.5">
          <img src="/images/Roumdoul_Logo.png" alt="Romdoul" loading="lazy" class="h-6 w-6 object-contain" />
          <span class="text-[10px] font-semibold uppercase tracking-widest text-pink-900 dark:text-pink-900">Digital Design By</span>
          <span class="text-sm font-bold text-pink-900 dark:text-pink-900">រំដួល</span>
          <span class="text-xs font-semibold uppercase tracking-wide text-pink-900 dark:text-pink-900">Roumdoul</span>
        </div>

        <div class="flex items-center gap-3">
          <a data-social="facebook" href="#" target="_blank" rel="noopener noreferrer" aria-label="Facebook"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-pink-900 transition-colors hover:bg-pink-900 hover:text-white dark:bg-gray-800 dark:text-pink-900 dark:hover:bg-pink-900 dark:hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
              <path
                d="M22 12.06C22 6.505 17.523 2 12 2S2 6.505 2 12.06c0 5.02 3.657 9.184 8.438 9.94v-7.03H7.898v-2.91h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.631.772-1.631 1.563v1.877h2.773l-.443 2.91h-2.33V22c4.78-.756 8.438-4.92 8.438-9.94z" />
            </svg>
          </a>

          <a data-social="instagram" href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-pink-900 transition-colors hover:bg-pink-900 hover:text-white dark:bg-gray-800 dark:text-pink-900 dark:hover:bg-pink-900 dark:hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
              <path fill-rule="evenodd" clip-rule="evenodd"
                d="M12 2c-2.716 0-3.056.012-4.123.06-1.064.05-1.79.218-2.427.465a4.9 4.9 0 00-1.771 1.153A4.9 4.9 0 002.525 5.45c-.247.637-.416 1.363-.465 2.427C2.012 8.944 2 9.284 2 12s.012 3.056.06 4.123c.05 1.064.218 1.79.465 2.427a4.9 4.9 0 001.153 1.771 4.9 4.9 0 001.771 1.153c.637.247 1.363.416 2.427.465C8.944 21.988 9.284 22 12 22s3.056-.012 4.123-.06c1.064-.05 1.79-.218 2.427-.465a4.9 4.9 0 001.771-1.153 4.9 4.9 0 001.153-1.771c.247-.637.416-1.363.465-2.427.048-1.067.06-1.407.06-4.123s-.012-3.056-.06-4.123c-.05-1.064-.218-1.79-.465-2.427a4.9 4.9 0 00-1.153-1.771A4.9 4.9 0 0018.55 2.525c-.637-.247-1.363-.416-2.427-.465C15.056 2.012 14.716 2 12 2zm0 1.802c2.67 0 2.986.01 4.04.059.976.045 1.505.207 1.858.344.467.182.8.399 1.15.748.35.35.566.683.748 1.15.137.353.3.882.344 1.858.048 1.054.059 1.37.059 4.04s-.01 2.986-.059 4.04c-.045.976-.207 1.505-.344 1.858a3.1 3.1 0 01-.748 1.15 3.1 3.1 0 01-1.15.748c-.353.137-.882.3-1.858.344-1.054.048-1.37.059-4.04.059s-2.987-.01-4.04-.059c-.976-.045-1.505-.207-1.858-.344a3.1 3.1 0 01-1.15-.748 3.1 3.1 0 01-.748-1.15c-.137-.353-.3-.882-.344-1.858-.048-1.054-.059-1.37-.059-4.04s.01-2.986.059-4.04c.045-.976.207-1.505.344-1.858.182-.467.399-.8.748-1.15a3.1 3.1 0 011.15-.748c.353-.137.882-.3 1.858-.344 1.054-.048 1.37-.059 4.04-.059zm0 3.064a5.135 5.135 0 100 10.269 5.135 5.135 0 000-10.27zm0 8.468a3.333 3.333 0 110-6.666 3.333 3.333 0 010 6.666zm6.538-8.671a1.2 1.2 0 11-2.4 0 1.2 1.2 0 012.4 0z" />
            </svg>
          </a>

          <a data-social="tiktok" href="#" target="_blank" rel="noopener noreferrer" aria-label="TikTok"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-pink-900 transition-colors hover:bg-pink-900 hover:text-white dark:bg-gray-800 dark:text-pink-900 dark:hover:bg-pink-900 dark:hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
              <path
                d="M16.5 2h-3.02v13.44a2.7 2.7 0 11-2.7-2.7c.157 0 .31.012.46.036V9.7a5.72 5.72 0 105.72 5.72V9.15a7.06 7.06 0 003.55 1.02V7.16a4.16 4.16 0 01-4.01-4.16V2z" />
            </svg>
          </a>

          <a data-social="telegram" href="#" target="_blank" rel="noopener noreferrer" aria-label="Telegram"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-pink-900 transition-colors hover:bg-pink-900 hover:text-pink-900 dark:bg-gray-800 dark:text-pink-900 dark:hover:bg-pink-900 dark:hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
              <path
                d="M21.05 2.927a1.5 1.5 0 00-1.523-.245L2.98 9.36a1.31 1.31 0 00.088 2.457l4.377 1.462 1.68 5.393a1 1 0 001.697.372l2.457-2.457 4.522 3.34a1.5 1.5 0 002.37-.94l2.334-14.09a1.5 1.5 0 00-.452-1.61zM9.66 13.996l-.33 3.386-1.36-4.36 9.09-6.99c.19-.146.44.08.28.25l-7.4 7.4a.87.87 0 00-.28.314z" />
            </svg>
          </a>
        </div>

        <div class="flex flex-col items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <a data-footer-phone href="#" class="transition-colors hover:text-green-800 dark:hover:text-amber-400"></a>
          <a data-footer-phone href="#" class="transition-colors hover:text-green-800 dark:hover:text-amber-400"></a>
          <a data-footer-phone href="#" class="transition-colors hover:text-green-800 dark:hover:text-amber-400"></a>
        </div>
        </div>
      </div>
    </div>
  </footer>

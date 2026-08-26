import { initTheme, getStoredTheme, setStoredTheme } from './modules/theme.js'
import { initNav } from './modules/nav.js'
import { initMobileMenu } from './modules/menu.js'
import { initLangToggle, restorePersistedLanguage, preloadTranslateWidget } from './modules/lang.js'
import { initHeroSlider } from './modules/heroSlider.js'
import { initFooter } from './modules/footer.js'

document.addEventListener('alpine:init', () => {
  window.Alpine.store('theme', {
    current: getStoredTheme(),
    set(mode) {
      this.current = mode
      setStoredTheme(mode)
    },
  })
})

function scrollToHash() {
  const hash = window.location.hash
  if (!hash) return
  // Deferred: something Livewire runs immediately after dispatching
  // livewire:navigated (Alpine re-init / autofocus / further layout work)
  // cancels an in-flight smooth-scroll animation started synchronously
  // inside this same event tick — waiting for the next one lets that
  // settle first.
  setTimeout(() => {
    const target = document.querySelector(hash)
    if (!target) return
    target.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 0)
}

function mountApp() {
  initTheme()
  initNav()
  initMobileMenu()
  initLangToggle()
  restorePersistedLanguage()
  initHeroSlider()
  initFooter()
  scrollToHash()
}

mountApp()

const warmOnIdle = window.requestIdleCallback || ((cb) => setTimeout(cb, 300))
warmOnIdle(() => preloadTranslateWidget())

document.addEventListener('livewire:navigated', mountApp)

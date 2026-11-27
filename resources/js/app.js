import { initTheme, getStoredTheme, setStoredTheme } from './modules/theme.js'
import { initNav } from './modules/nav.js'
import { initMobileMenu } from './modules/menu.js'
import { initLangToggle } from './modules/lang.js'
import { initHeroSlider } from './modules/heroSlider.js'
import { initFooter } from './modules/footer.js'
import { initScrollReveal } from './modules/scrollReveal.js'
import { initHeroParticles } from './modules/heroParticles.js'
import { initTilt } from './modules/tilt.js'
import './modules/photoUploader.js'
import './modules/toast.js'
import './modules/loader.js'
import './modules/formErrors.js'

document.addEventListener('livewire:init', () => {
  // Pagination, filters, etc. update a Livewire component in place without a
  // full page navigation, so `livewire:navigated` never fires for them —
  // newly-rendered [data-reveal] cards (e.g. after clicking "next page" in
  // the shop) would otherwise sit at opacity:0 forever since nothing
  // re-scans for them. `commit` fires after every such update.
  Livewire.hook('commit', ({ succeed }) => {
    succeed(() => {
      initScrollReveal()
      initTilt()
    })
  })
})

document.addEventListener('alpine:init', () => {
  window.Alpine.store('theme', {
    current: getStoredTheme(),
    set(mode) {
      this.current = mode
      setStoredTheme(mode)
    },
  })

  window.Alpine.store('cartDrawer', {
    open: false,
    show() {
      this.open = true
    },
    hide() {
      this.open = false
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
  initHeroSlider()
  initFooter()
  scrollToHash()
  initScrollReveal()
  initHeroParticles()
  initTilt()
}

mountApp()

// Page transition for wire:navigate. A full page load already animates through the browser's
// view transition (@view-transition in the layouts), so only SPA swaps get the class here —
// otherwise a normal load would animate twice.
let spaNavigation = false
document.addEventListener('livewire:navigate', () => { spaNavigation = true })
document.addEventListener('livewire:navigated', () => {
  if (!spaNavigation) return
  spaNavigation = false
  const main = document.querySelector('[data-page-transition]')
  if (!main) return
  main.classList.remove('page-enter')
  void main.offsetWidth // restart the animation
  main.classList.add('page-enter')
})

document.addEventListener('livewire:navigated', mountApp)

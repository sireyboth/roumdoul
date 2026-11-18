/*
 * ROUMDOUL loading overlay — resources/views/components/loading-overlay.blade.php.
 *
 * Every Livewire action that sends what the customer typed shows the overlay until the server answers:
 *   - any `wire:submit` form (login, sign-up, checkout, profile, saving an invitation, adding a guest…)
 *   - any element inside `[data-loader]` (opt in a single button)
 *   - the actions in LOADER_ACTIONS (buttons and `$wire.x()` calls that aren't forms)
 * Opt out with `data-no-loader` on the form or button.
 *
 * The page is blocked the moment the request starts (an invisible shield, so a second click or
 * Enter can't send it twice); the logo only fades in if it takes longer than SHOW_AFTER, and
 * once shown it stays at least MIN_VISIBLE so it never flashes. When the answer is a redirect
 * (wire:navigate), it stays up until the next page is in.
 */

const SHOW_AFTER = 140
const MIN_VISIBLE = 550
const SAFETY_TIMEOUT = 45000

const LOADER_ACTIONS = new Set([
  'placeOrder', 'submitReview', 'applyPromoCode', 'addToCart', 'buyAddon', 'importGuests',
  'rotateRecipient', 'removeRecipient', 'resetChristmasForm',
])

let active = 0
let showTimer = null
let hideTimer = null
let safetyTimer = null
let shownAt = 0
let navigating = false

const el = () => document.getElementById('rd-loader')

function setState(state) {
  const node = el()
  if (node) {
    node.dataset.state = state
    node.setAttribute('aria-hidden', state === 'visible' ? 'false' : 'true')
  }
  if (state === 'idle') document.documentElement.removeAttribute('data-loading')
  else document.documentElement.setAttribute('data-loading', '')
}

function show() {
  active++
  clearTimeout(hideTimer)
  if (active > 1) return

  // Stop the click/Enter that started this from reaching anything else, and lose focus so a held
  // Enter key can't resubmit.
  if (document.activeElement && document.activeElement !== document.body) document.activeElement.blur()

  if (el()?.dataset.state !== 'visible') {
    setState('blocking')
    clearTimeout(showTimer)
    showTimer = setTimeout(() => {
      if (active > 0 || navigating) {
        shownAt = performance.now()
        setState('visible')
      }
    }, SHOW_AFTER)
  }

  clearTimeout(safetyTimer)
  safetyTimer = setTimeout(reset, SAFETY_TIMEOUT)
}

function hide() {
  active = Math.max(0, active - 1)
  if (active > 0) return

  const wait = el()?.dataset.state === 'visible' ? Math.max(0, MIN_VISIBLE - (performance.now() - shownAt)) : 0
  clearTimeout(hideTimer)
  // a short grace period: a redirect after a successful submit starts right after the response
  hideTimer = setTimeout(() => {
    if (active === 0 && !navigating) reset()
  }, Math.max(wait, 60))
}

function reset() {
  active = 0
  navigating = false
  clearTimeout(showTimer)
  clearTimeout(hideTimer)
  clearTimeout(safetyTimer)
  setState('idle')
}

/** Keep the overlay up while a promise runs: `roumdoulLoader.track($wire.save())`. */
function track(promise) {
  show()
  return Promise.resolve(promise).finally(hide)
}

function wantsLoader(action) {
  const origin = action.origin || {}
  const node = origin.el instanceof Element ? origin.el : null
  if (node?.closest('[data-no-loader]')) return false
  if (origin.directive?.value === 'submit') return true
  if (node?.closest('[data-loader]')) return true
  return LOADER_ACTIONS.has(action.name)
}

window.roumdoulLoader = { show, hide, track, reset }

document.addEventListener('livewire:init', () => {
  window.Livewire.interceptAction(({ action, onFinish }) => {
    if (!wantsLoader(action)) return
    show()
    onFinish(() => hide())
  })
})

// A submit that answers with a redirect: stay up until the new page has arrived.
document.addEventListener('livewire:navigate', () => {
  if (active > 0 || el()?.dataset.state !== 'idle') {
    navigating = true
    clearTimeout(hideTimer)
  }
})
document.addEventListener('livewire:navigated', reset)
window.addEventListener('pageshow', reset)

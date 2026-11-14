// Language dropdown in the header. Switching is server-side (GET /lang/{locale} stores the
// choice in the session and redirects back), so this only opens/closes the menu.

let globalListenersAttached = false

function setMenuOpen(wrapper, isOpen) {
  const button = wrapper.querySelector('[data-lang-toggle]')
  const menu = wrapper.querySelector('[data-lang-menu]')
  menu.classList.toggle('opacity-0', !isOpen)
  menu.classList.toggle('scale-95', !isOpen)
  menu.classList.toggle('pointer-events-none', !isOpen)
  menu.classList.toggle('opacity-100', isOpen)
  menu.classList.toggle('scale-100', isOpen)
  button.setAttribute('aria-expanded', String(isOpen))
}

function closeAllLangDropdowns() {
  document.querySelectorAll('[data-lang-dropdown]').forEach((wrapper) => setMenuOpen(wrapper, false))
}

export function initLangToggle() {
  document.querySelectorAll('[data-lang-dropdown]').forEach((wrapper) => {
    const button = wrapper.querySelector('[data-lang-toggle]')
    if (button.dataset.langBound) return
    button.dataset.langBound = 'true'

    button.addEventListener('click', (e) => {
      e.stopPropagation()
      const isOpen = wrapper.querySelector('[data-lang-menu]').classList.contains('opacity-100')
      closeAllLangDropdowns()
      setMenuOpen(wrapper, !isOpen)
    })
  })

  if (!globalListenersAttached) {
    globalListenersAttached = true
    document.addEventListener('click', closeAllLangDropdowns)
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeAllLangDropdowns()
    })
  }
}

// ── Language-switch animation ────────────────────────────────────────────────
// Browsers with cross-page view transitions cross-fade the reload themselves (see
// @view-transition in app.css); we only make sure that animation runs for language switches
// and not for every other full-page navigation. Older browsers get a short fade-out here and
// a fade-in from the inline script in the layouts' <head>.
const LANG_SWITCH_FLAG = 'roumdoul:lang-switch'
const supportsPageTransitions = typeof window.CSSViewTransitionRule !== 'undefined'

function isLangSwitchPending() {
  try {
    return sessionStorage.getItem(LANG_SWITCH_FLAG) === '1'
  } catch {
    return false
  }
}

document.addEventListener('click', (e) => {
  const link = e.target.closest('a[data-lang-option], a[data-lang-switch]')
  if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return

  // Already in that language: nothing to reload.
  if (link.getAttribute('aria-current') === 'true') {
    e.preventDefault()
    return
  }

  try {
    sessionStorage.setItem(LANG_SWITCH_FLAG, '1')
  } catch {
    /* storage blocked — the switch still works, just without the animation */
  }

  if (supportsPageTransitions || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

  e.preventDefault()
  document.documentElement.classList.add('lang-leaving')
  setTimeout(() => {
    window.location.href = link.href
  }, 200)
})

// Leaving this page: keep the view transition only if it's a language switch.
window.addEventListener('pageswap', (e) => {
  if (e.viewTransition && !isLangSwitchPending()) e.viewTransition.skipTransition()
})

// Back/forward cache restores the old page as it was — undo the fade-out.
window.addEventListener('pageshow', (e) => {
  if (e.persisted) document.documentElement.classList.remove('lang-leaving')
})

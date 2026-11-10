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

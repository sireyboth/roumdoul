export function initMobileMenu() {
  const toggleButton = document.getElementById('menu-toggle')
  const wrapper = document.getElementById('mobile-menu-wrapper')
  if (!toggleButton || !wrapper || toggleButton.dataset.menuBound) return
  toggleButton.dataset.menuBound = 'true'

  const menu = document.getElementById('mobile-menu')
  const openIcon = document.getElementById('menu-icon-open')
  const closeIcon = document.getElementById('menu-icon-close')
  const desktopBreakpoint = window.matchMedia('(min-width: 768px)')

  let isOpen = false

  const setOpen = (nextOpen) => {
    isOpen = nextOpen
    wrapper.classList.toggle('grid-rows-[0fr]', !isOpen)
    wrapper.classList.toggle('grid-rows-[1fr]', isOpen)
    wrapper.classList.toggle('opacity-0', !isOpen)
    wrapper.classList.toggle('opacity-100', isOpen)
    openIcon.classList.toggle('hidden', isOpen)
    closeIcon.classList.toggle('hidden', !isOpen)
    toggleButton.setAttribute('aria-expanded', String(isOpen))
  }

  toggleButton.addEventListener('click', () => {
    setOpen(!isOpen)
  })

  menu.querySelectorAll('[data-nav-link]').forEach((link) => {
    link.addEventListener('click', () => setOpen(false))
  })

  desktopBreakpoint.addEventListener('change', (e) => {
    if (e.matches) setOpen(false)
  })
}

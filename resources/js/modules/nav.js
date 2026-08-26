export function initNav() {
  const links = document.querySelectorAll('[data-nav-link]')
  const activeClasses = ['text-green-800', 'dark:text-white']
  const inactiveClasses = ['text-gray-600', 'hover:text-green-800', 'dark:text-gray-300', 'dark:hover:text-white']

  links.forEach((link) => {
    const isExact = link.hasAttribute('data-nav-exact')
    const linkPath = new URL(link.href).pathname
    const isActive = isExact ? linkPath === window.location.pathname : window.location.pathname.startsWith(linkPath)

    // The header is persisted across wire:navigate (it's never re-rendered
    // server-side), so this re-runs on the same DOM nodes on every
    // navigation. Always clear both class sets first — otherwise a
    // previously-active link keeps its active classes sitting in the
    // classList alongside the newly-added inactive ones, and whichever rule
    // comes later in the compiled CSS wins the cascade, leaving the old page
    // looking "stuck" active.
    link.classList.remove(...activeClasses, ...inactiveClasses)
    link.classList.add(...(isActive ? activeClasses : inactiveClasses))
  })
}

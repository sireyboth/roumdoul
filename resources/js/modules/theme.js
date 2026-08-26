const STORAGE_KEY = 'theme'

export function getStoredTheme() {
  // Light is the default unless the user has explicitly chosen dark —
  // deliberately ignores prefers-color-scheme so a dark OS/browser setting
  // doesn't force the site into dark mode on a first visit.
  return localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'light'
}

export function applyTheme(theme) {
  document.documentElement.classList.toggle('dark', theme === 'dark')
}

export function setStoredTheme(theme) {
  localStorage.setItem(STORAGE_KEY, theme)
  applyTheme(theme)
}

export function initTheme() {
  // Livewire's wire:navigate swap syncs document.documentElement's
  // attributes to match the freshly-fetched page's <html> tag
  // (replaceHtmlAttributes in Livewire's navigate feature). The server never
  // renders class="dark", so that sync silently strips it on every
  // navigation. <html> sits outside any @persist'd Alpine tree, so the theme
  // dropdown's own state surviving navigation doesn't fix this by itself —
  // re-apply from localStorage on every mount (this runs again on
  // livewire:navigated, after the swap) to undo it.
  applyTheme(getStoredTheme())
}

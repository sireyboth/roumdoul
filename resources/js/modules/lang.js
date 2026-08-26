const TARGET_LANGUAGE = 'en'
const WIDGET_LOAD_TIMEOUT_MS = 8000
const LANG_STORAGE_KEY = 'lang'

let widgetReady = null
let globalListenersAttached = false
let isEnglish = false

function loadTranslateWidget() {
  if (widgetReady) return widgetReady

  widgetReady = new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('Google Translate widget timed out')), WIDGET_LOAD_TIMEOUT_MS)

    const container = document.createElement('div')
    container.id = 'google_translate_element'
    container.className = 'hidden'
    document.body.appendChild(container)

    window.initGoogleTranslate = () => {
      try {
        new google.translate.TranslateElement(
          { pageLanguage: 'km', includedLanguages: TARGET_LANGUAGE, autoDisplay: false },
          'google_translate_element',
        )
      } catch (err) {
        clearTimeout(timer)
        reject(err)
        return
      }

      const existing = container.querySelector('.goog-te-combo')
      if (existing) {
        clearTimeout(timer)
        resolve(existing)
      } else {
        const observer = new MutationObserver(() => {
          const select = container.querySelector('.goog-te-combo')
          if (select) {
            observer.disconnect()
            clearTimeout(timer)
            resolve(select)
          }
        })
        observer.observe(container, { childList: true, subtree: true })
      }
    }

    const script = document.createElement('script')
    script.src = 'https://translate.google.com/translate_a/element.js?cb=initGoogleTranslate'
    script.onerror = () => {
      clearTimeout(timer)
      reject(new Error('Google Translate script failed to load'))
    }
    document.body.appendChild(script)
  })

  widgetReady.catch(() => {
    widgetReady = null
  })

  return widgetReady
}

export function preloadTranslateWidget() {
  loadTranslateWidget().catch(() => {})
}

function updateUI(overrideState) {
  const state = overrideState ?? isEnglish
  document.querySelectorAll('[data-lang-label]').forEach((label) => {
    label.textContent = state ? 'EN' : 'ខ្មែរ'
  })
  document.querySelectorAll('[data-lang-check-en]').forEach((check) => check.classList.toggle('hidden', !state))
  document.querySelectorAll('[data-lang-check-km]').forEach((check) => check.classList.toggle('hidden', state))
}

async function activateEnglish() {
  const select = await loadTranslateWidget()
  select.value = TARGET_LANGUAGE
  select.dispatchEvent(new Event('change'))
  isEnglish = true
  updateUI()
}

function interceptNavigationWhileTranslated(e) {
  if (!isEnglish) return
  const destination = e.detail && e.detail.url
  if (!destination) return
  e.preventDefault()
  window.location.href = destination.href
}

function clearGoogleTranslateCookie() {
  const expired = 'expires=Thu, 01 Jan 1970 00:00:00 UTC'
  document.cookie = `googtrans=; ${expired}; path=/`

  const labels = window.location.hostname.split('.')
  for (let i = 0; i < labels.length; i++) {
    const domain = labels.slice(i).join('.')
    document.cookie = `googtrans=; ${expired}; path=/; domain=${domain}`
    document.cookie = `googtrans=; ${expired}; path=/; domain=.${domain}`
  }
}

function restoreOriginal() {
  localStorage.setItem(LANG_STORAGE_KEY, 'km')
  clearGoogleTranslateCookie()
  window.location.reload()
}

export function restorePersistedLanguage() {
  if (isEnglish) return
  if (localStorage.getItem(LANG_STORAGE_KEY) !== 'en') return

  activateEnglish().catch((err) => {
    // eslint-disable-next-line no-console
    console.error('Could not restore English after navigation, staying in Khmer.', err)
    localStorage.setItem(LANG_STORAGE_KEY, 'km')
  })
}

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
  const wrappers = document.querySelectorAll('[data-lang-dropdown]')
  if (wrappers.length === 0) return

  const unboundWrappers = Array.from(wrappers).filter(
    (wrapper) => !wrapper.querySelector('[data-lang-toggle]').dataset.langBound,
  )
  if (unboundWrappers.length === 0) return

  unboundWrappers.forEach((wrapper) => {
    const button = wrapper.querySelector('[data-lang-toggle]')
    button.dataset.langBound = 'true'

    button.addEventListener('click', (e) => {
      e.stopPropagation()
      const menu = wrapper.querySelector('[data-lang-menu]')
      const isOpen = menu.classList.contains('opacity-100')
      closeAllLangDropdowns()
      setMenuOpen(wrapper, !isOpen)
    })

    button.addEventListener('pointerdown', () => preloadTranslateWidget(), { passive: true })

    wrapper.querySelectorAll('[data-lang-option]').forEach((option) => {
      option.addEventListener('click', async () => {
        const wantsEnglish = option.getAttribute('data-lang-option') === 'en'
        closeAllLangDropdowns()

        if (wantsEnglish === isEnglish) return

        if (!wantsEnglish) {
          restoreOriginal()
          return
        }

        updateUI(true)
        button.disabled = true
        try {
          await activateEnglish()
          localStorage.setItem(LANG_STORAGE_KEY, 'en')
        } catch (err) {
          // eslint-disable-next-line no-console
          console.error('Language switch failed, staying in Khmer.', err)
          isEnglish = false
          updateUI()
        } finally {
          button.disabled = false
        }
      })
    })
  })

  if (!globalListenersAttached) {
    globalListenersAttached = true
    document.addEventListener('click', closeAllLangDropdowns)
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeAllLangDropdowns()
    })
    document.addEventListener('alpine:navigate', interceptNavigationWhileTranslated)
  }

  updateUI()
}

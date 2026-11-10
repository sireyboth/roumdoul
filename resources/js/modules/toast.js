// Toast notifications (Alpine store `toasts`), shown by <x-toasts /> wherever a layout includes it.
//
// Raise one from anywhere:
//   - PHP (Livewire):  $this->dispatch('toast', type: 'success', message: '…')
//   - JS:              window.toast('error', '…')  or  window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }))
// Types: success, error, warning, info. Errors stay longer; `action` adds a button ({ label, run }).
//
// Also turns failed Livewire requests (no connection, expired session, forbidden, server error)
// and going offline/online into toasts — but only on pages that render the toast root, so other
// pages keep Livewire's own error handling.

import { t } from './i18n.js'

const DURATION = { success: 3500, info: 4500, warning: 6000, error: 7000 }
let nextId = 1

function hasToastRoot() {
  return document.getElementById('toast-root') !== null
}

function push({ type = 'info', message = '', action = null, duration = null } = {}) {
  const store = window.Alpine?.store('toasts')
  if (!store || !message) return

  // the same message twice in a row (double click, repeated failure) just restarts its timer
  const same = store.items.find((t) => t.message === message && t.type === type)
  if (same) {
    store.restart(same)
    return
  }

  const ms = duration ?? DURATION[type] ?? 4500
  const toast = { id: nextId++, type, message, action, duration: ms, remaining: ms, round: 0, startedAt: 0, timer: null, paused: false }
  store.items.push(toast)
  if (store.items.length > 4) store.items.shift()
  // arm the reactive copy Alpine keeps in the store, so paused/round changes re-render
  store.arm(store.items.find((t) => t.id === toast.id))
}

window.toast = (type, message, options = {}) => push({ type, message, ...options })

document.addEventListener('alpine:init', () => {
  window.Alpine.store('toasts', {
    items: [],
    // (re)starts the countdown with whatever time is left; hovering pauses it
    arm(toast) {
      clearTimeout(toast.timer)
      toast.paused = false
      toast.startedAt = Date.now()
      toast.timer = setTimeout(() => this.dismiss(toast.id), toast.remaining)
    },
    pause(toast) {
      clearTimeout(toast.timer)
      toast.paused = true
      toast.remaining = Math.max(500, toast.remaining - (Date.now() - toast.startedAt))
    },
    restart(toast) {
      toast.remaining = toast.duration
      toast.round++ // re-keys the time bar so its animation starts over
      this.arm(toast)
    },
    dismiss(id) {
      this.items = this.items.filter((t) => t.id !== id)
    },
  })
})

window.addEventListener('toast', (e) => push(e.detail ?? {}))

document.addEventListener('livewire:init', () => {
  window.Livewire.hook('request', ({ fail }) => {
    fail(({ status, preventDefault }) => {
      if (!hasToastRoot()) return
      preventDefault()

      if (status === 419) {
        push({
          type: 'warning',
          message: t('Your session has expired. Please reload the page (unsaved changes may be lost).'),
          action: { label: t('Reload'), run: () => window.location.reload() },
          duration: 20000,
        })
      } else if (status === 403) {
        push({ type: 'error', message: t("You can't do this — the invitation may have expired or not been paid yet.") })
      } else if (status === 404) {
        push({ type: 'error', message: t('Not found — it may already have been deleted. Please reload the page.') })
      } else if (status === 413) {
        push({ type: 'error', message: t('The file is too large. Please use a smaller photo.') })
      } else if (!status || !navigator.onLine) {
        push({ type: 'error', message: t("Can't connect to the internet. Please check your connection and try again.") })
      } else {
        push({
          type: 'error',
          message: t('Something went wrong. Please try again in a moment.'),
          action: { label: t('Reload'), run: () => window.location.reload() },
        })
      }
    })
  })
})

window.addEventListener('offline', () => {
  if (hasToastRoot()) push({ type: 'warning', message: t("You're offline — changes can't be saved until you reconnect."), duration: 10000 })
})

window.addEventListener('online', () => {
  if (hasToastRoot()) push({ type: 'success', message: t('Back online.') })
})

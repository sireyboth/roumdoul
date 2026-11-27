// LIVE PREVIEW for the invitation editor (Details / Images / Program tabs).
//
// Markup: resources/views/livewire/pages/dashboard/partials/live-preview.blade.php (a wireframe of the
// invitation's real template, built from its recipe in config/live-preview.php)
// Styles: «Live preview» in resources/css/app.css
//
// How a field finds its place in the preview — the ONLY mapping is the field key:
//   the form's input:      <input name="groom_name" …>
//   its slot(s) in preview: <p data-preview-slot="groom_name" …>
// A field can have more than one slot (the names show on the cover, at the top and at the end, like
// in the real template): every slot with that key is updated.
//
// What this file does:
//   1. typing          → copies the value into the slot(s) (textContent, never innerHTML)
//   2. focusing a field → pink dashed outline on its slot(s) + scrolls the PHONE SCREEN to the first one
//   3. picking a photo  → shows it in its slot(s) with URL.createObjectURL (nothing is uploaded here)
//   4. event date       → «X days left» and the countdown's day box, worked out once (no timers)
//   5. on/off switches  → sections tied to a switch (countdown, RSVP, Latin names) show or hide
//   6. phones           → «Live preview» opens it full screen, «Close» goes back to the same place
// It never saves anything and never talks to the server.
//
// Livewire swaps parts of the page (switching tabs), so nothing keeps a reference to an element:
// each function looks the panel / form up again when it needs it.

const WIDE_SCREEN = window.matchMedia('(min-width: 80rem)') // Tailwind's `xl`: preview beside the form
const HIGHLIGHT_CLASS = 'is-focused' // the pink dashed outline (app.css)
const EMPTY_CLASS = 'is-empty' // grey placeholder text (app.css)

let lastFocusedKey = null // the field the customer edited last, e.g. 'groom_name'
let scrollBeforeOpen = 0 // where the page was when the phone preview opened
const photoUrls = {} // the temporary photo link per field, so the old one can be freed

// ---------------------------------------------------------------------------------------------
// Finding things
// ---------------------------------------------------------------------------------------------

function getPanel() {
  return document.getElementById('live-preview')
}

function getForm() {
  return document.querySelector('[data-live-preview-form]')
}

/** Every slot of a field in the preview (none when this template doesn't show that field). */
function findSlots(key) {
  const panel = getPanel()
  if (panel === null) return []
  return panel.querySelectorAll('[data-preview-slot="' + key + '"]')
}

/** The form's input for a field (inputs are linked to slots by their name), or null. */
function findInput(key) {
  const form = getForm()
  if (form === null) return null
  return form.querySelector('[name="' + key + '"]')
}

// ---------------------------------------------------------------------------------------------
// Turning a value into the text shown in a slot
// ---------------------------------------------------------------------------------------------

/**
 * Reads "2026-12-15", "2026-12-15T17:00" or "2026-12-15 17:00:00" into its parts.
 * Returns null when the text isn't a date.
 */
function readDate(text) {
  const match = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/.exec(text)
  if (match === null) return null

  return {
    year: Number(match[1]),
    month: Number(match[2]),
    day: Number(match[3]),
    hasTime: match[4] !== undefined,
    hour: match[4] !== undefined ? Number(match[4]) : 0,
    minute: match[5] !== undefined ? Number(match[5]) : 0,
  }
}

/** "2026-12-15T17:00" → "15 December 2026, 5:00 PM" (in the site's language). */
function formatDate(text, locale) {
  const parts = readDate(text)
  if (parts === null) return text // not a date: show it as typed

  const date = new Date(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute)
  const options = { day: 'numeric', month: 'long', year: 'numeric' }
  if (parts.hasTime) {
    options.hour = 'numeric'
    options.minute = '2-digit'
  }
  return new Intl.DateTimeFormat(locale, options).format(date)
}

/** "17:00" → "5:00 PM" (in the site's language). */
function formatTime(text, locale) {
  const match = /^(\d{2}):(\d{2})/.exec(text)
  if (match === null) return text

  const date = new Date(2000, 0, 1, Number(match[1]), Number(match[2]))
  return new Intl.DateTimeFormat(locale, { hour: 'numeric', minute: '2-digit' }).format(date)
}

/** The text a slot shows for a value: dates and times are formatted, the rest stays as typed. */
function textForSlot(slot, value) {
  const kind = slot.getAttribute('data-preview-kind')
  const locale = getPanel().getAttribute('data-locale')

  if (kind === 'date') return formatDate(value, locale)
  if (kind === 'time') return formatTime(value, locale)
  return value
}

// ---------------------------------------------------------------------------------------------
// Updating the preview
// ---------------------------------------------------------------------------------------------

/** Puts a field's value in all its slots; an empty value shows the field's label in grey instead. */
function fillSlots(key, rawValue) {
  const value = String(rawValue).trim()
  const slots = findSlots(key)

  for (let i = 0; i < slots.length; i++) {
    const slot = slots[i]
    if (slot.getAttribute('data-preview-kind') === 'photo') continue // photos: see showPhoto()

    if (value === '') {
      slot.textContent = slot.getAttribute('data-preview-label')
      slot.classList.add(EMPTY_CLASS)
    } else {
      slot.textContent = textForSlot(slot, value)
      slot.classList.remove(EMPTY_CLASS)
    }
  }

  if (key === 'event_date') {
    updateDaysLeft(value)
  }
}

/** Whole days from today until the date (negative once it has passed), or null when it isn't a date. */
function daysUntil(dateText) {
  const parts = readDate(dateText)
  if (parts === null) return null

  // Count whole days between today and the event day (times of day don't matter).
  const today = new Date()
  const todayAtMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const eventDay = new Date(parts.year, parts.month - 1, parts.day)
  const millisecondsPerDay = 24 * 60 * 60 * 1000
  return Math.round((eventDay - todayAtMidnight) / millisecondsPerDay)
}

/** «X days left» lines and the countdown's day box: worked out once from the date (they don't tick). */
function updateDaysLeft(dateText) {
  const panel = getPanel()
  const daysLeft = daysUntil(dateText)

  // the «X days left» lines
  const lines = panel.querySelectorAll('[data-preview-days-left]')
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i]
    if (daysLeft === null) {
      line.textContent = ''
      line.hidden = true
      continue
    }

    let text
    if (daysLeft > 1) {
      text = panel.getAttribute('data-text-days-left').replace(':count', String(daysLeft))
    } else if (daysLeft === 1) {
      text = panel.getAttribute('data-text-one-day-left')
    } else if (daysLeft === 0) {
      text = panel.getAttribute('data-text-today')
    } else {
      text = panel.getAttribute('data-text-passed')
    }
    line.textContent = text
    line.hidden = false
  }

  // the first box of each countdown: the number of days (or – when there is no date / it has passed)
  const dayBoxes = panel.querySelectorAll('[data-preview-days-count]')
  for (let i = 0; i < dayBoxes.length; i++) {
    if (daysLeft === null || daysLeft < 0) {
      dayBoxes[i].textContent = '–'
    } else {
      dayBoxes[i].textContent = String(daysLeft)
    }
  }
}

/** Shows a just-picked photo in its slot(s). The file stays on the phone: nothing is uploaded here. */
function showPhoto(key, file) {
  // Free the previous temporary link of this field, then make one for the new file.
  if (photoUrls[key]) {
    URL.revokeObjectURL(photoUrls[key])
  }
  photoUrls[key] = URL.createObjectURL(file)

  const slots = findSlots(key)
  for (let i = 0; i < slots.length; i++) {
    const image = slots[i].querySelector('img')
    const label = slots[i].querySelector('.pv-photo-label')
    image.src = photoUrls[key]
    image.hidden = false
    label.hidden = true
  }
}

/** The photo was removed in the form: back to the pink placeholder with the label. */
function clearPhoto(key) {
  if (photoUrls[key]) {
    URL.revokeObjectURL(photoUrls[key])
    delete photoUrls[key]
  }

  const slots = findSlots(key)
  for (let i = 0; i < slots.length; i++) {
    const image = slots[i].querySelector('img')
    const label = slots[i].querySelector('.pv-photo-label')
    image.removeAttribute('src')
    image.hidden = true
    label.hidden = false
  }
}

/** Everything tied to a switch (Latin names, countdown, RSVP): shown while the switch is on. */
function applySwitch(switchName, isOn) {
  const panel = getPanel()
  if (panel === null) return

  const parts = panel.querySelectorAll('[data-preview-switch="' + switchName + '"]')
  for (let i = 0; i < parts.length; i++) {
    parts[i].hidden = !isOn
  }
}

/** On page load (and each time Livewire puts a new preview in the page): fill every slot. */
function start() {
  const panel = getPanel()
  if (panel === null) return

  const slots = panel.querySelectorAll('[data-preview-slot]')
  for (let i = 0; i < slots.length; i++) {
    const slot = slots[i]
    const key = slot.getAttribute('data-preview-slot')
    if (slot.getAttribute('data-preview-kind') === 'photo') continue // the server already drew the saved photo

    // The value the page was drawn with (data-preview-value) is the form's current value, unsaved
    // edits included. We can't simply read the inputs here: Livewire may not have put the values
    // into them yet. An input that already has text (e.g. the browser restored it) wins.
    const input = findInput(key)
    if (input !== null && input.value !== '') {
      fillSlots(key, input.value)
    } else {
      fillSlots(key, slot.getAttribute('data-preview-value') || '')
    }
  }

  // Switches (Latin names, countdown, RSVP): the server already drew those parts shown or hidden.
}

// ---------------------------------------------------------------------------------------------
// Focus: outline the field's slot(s) and scroll the phone screen to the first one
// ---------------------------------------------------------------------------------------------

function highlight(key) {
  const panel = getPanel()
  if (panel === null) return

  // remove the previous outline
  const outlined = panel.querySelectorAll('.' + HIGHLIGHT_CLASS)
  for (let i = 0; i < outlined.length; i++) {
    outlined[i].classList.remove(HIGHLIGHT_CLASS)
  }

  // outline every slot of the field that is on screen, and remember the first one
  const slots = findSlots(key)
  let firstVisible = null
  for (let i = 0; i < slots.length; i++) {
    const isVisible = slots[i].getClientRects().length > 0 // false when it (or its section) is hidden
    if (!isVisible) continue
    slots[i].classList.add(HIGHLIGHT_CLASS)
    if (firstVisible === null) firstVisible = slots[i]
  }

  if (firstVisible !== null) {
    scrollScreenTo(firstVisible)
  }
}

/** Scrolls ONLY the preview's own scroll area so the slot sits in its middle (the page stays put). */
function scrollScreenTo(slot) {
  const scrollArea = getPanel().querySelector('[data-preview-scroll]')

  // Where the slot is inside the scroll area right now, in pixels.
  const areaBox = scrollArea.getBoundingClientRect()
  const slotBox = slot.getBoundingClientRect()
  const slotTop = slotBox.top - areaBox.top + scrollArea.scrollTop

  // Scroll so the middle of the slot lines up with the middle of the visible area.
  const target = slotTop - scrollArea.clientHeight / 2 + slotBox.height / 2
  scrollArea.scrollTop = Math.max(0, target)
}

// ---------------------------------------------------------------------------------------------
// Phones: open / close full screen
// ---------------------------------------------------------------------------------------------

function openFullScreen() {
  const panel = getPanel()
  if (panel === null) return

  scrollBeforeOpen = window.scrollY // to come back to the same place on Close
  panel.classList.add('is-open')
  document.documentElement.classList.add('live-preview-open') // the page behind stops scrolling

  if (lastFocusedKey !== null) {
    highlight(lastFocusedKey) // opens at the last edited field
  }

  const closeButton = panel.querySelector('[data-live-preview-close]')
  if (closeButton !== null) closeButton.focus()
}

function closeFullScreen() {
  const panel = getPanel()
  if (panel === null || !panel.classList.contains('is-open')) return

  panel.classList.remove('is-open')
  document.documentElement.classList.remove('live-preview-open')
  // 'instant': the page has smooth scrolling on, but here we want to be back right away
  window.scrollTo({ top: scrollBeforeOpen, behavior: 'instant' })

  const openButton = document.querySelector('[data-live-preview-open]')
  if (openButton !== null) openButton.focus({ preventScroll: true })
}

// ---------------------------------------------------------------------------------------------
// Listening (set up once, for whatever form / preview is on the page at that moment)
// ---------------------------------------------------------------------------------------------

/** The field key of a form element: its name (e.g. 'groom_name'), or '' when it isn't a field. */
function fieldKeyOf(element) {
  const form = getForm()
  if (form === null || !form.contains(element)) return ''
  return element.getAttribute('name') || ''
}

// 1. Typing → copy the value into the slot(s).
document.addEventListener('input', function (event) {
  const key = fieldKeyOf(event.target)
  if (key === '' || event.target.type === 'file' || event.target.type === 'checkbox') return
  fillSlots(key, event.target.value)
})

// 2. A switch (checkbox) changed → show/hide the parts tied to it.
document.addEventListener('change', function (event) {
  const key = fieldKeyOf(event.target)
  if (key === '' || event.target.type !== 'checkbox') return
  applySwitch(key, event.target.checked)
})

// 3. A photo was picked → show it. Listening in the "capture" phase runs this before the photo
//    uploader's own handler, so the chosen file is still there to read.
document.addEventListener('change', function (event) {
  const key = fieldKeyOf(event.target)
  if (key === '' || event.target.type !== 'file') return
  const files = event.target.files
  if (files && files.length > 0) {
    showPhoto(key, files[0])
  }
}, true)

// 4. Focusing a field → remember it and highlight its slot(s).
document.addEventListener('focusin', function (event) {
  const key = fieldKeyOf(event.target)
  if (key === '') return
  lastFocusedKey = key
  highlight(key)
})

// 5. Buttons: «Live preview», «Close», and «remove photo» in the form.
document.addEventListener('click', function (event) {
  if (event.target.closest('[data-live-preview-open]')) {
    openFullScreen()
  }
  if (event.target.closest('[data-live-preview-close]')) {
    closeFullScreen()
  }

  const removeButton = event.target.closest('[data-preview-remove]')
  if (removeButton !== null) {
    clearPhoto(removeButton.getAttribute('data-preview-remove'))
  }
})

// 6. Escape closes the full-screen preview.
document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    closeFullScreen()
  }
})

// 7. Growing a window / turning a tablet past `xl`: the preview moves beside the form, so close full screen.
WIDE_SCREEN.addEventListener('change', function () {
  if (WIDE_SCREEN.matches) {
    closeFullScreen()
  }
})

// The preview's x-init calls this each time Livewire puts a (new) preview in the page (switching tabs).
window.livePreview = { start: start }

// Also fill the slots once each page has loaded, in case that x-init ran before this file did.
document.addEventListener('livewire:navigated', start)

// Photo picker for the invitation dashboard (Alpine component `photoUploader`).
//
// Instead of letting `wire:model` push the raw file, it:
//  - rejects wrong types / oversized files / too many gallery photos before anything is sent,
//  - shrinks every photo in the browser to the server's 1600px / q0.82 (an 18 MB phone photo
//    becomes ~300-600 KB), since the server scales everything down to that anyway — sending
//    more pixels only makes the upload slower on a phone connection,
//  - decodes photos two at a time, so picking 20 phone photos doesn't decode ~1 GB of bitmaps
//    at once and stall (or crash) the phone,
//  - locks the picker (and the form's Save button) while an upload runs, so it can't be spammed,
//  - shows progress, can be cancelled, and gives up with a message if the upload stalls.

import { t } from './i18n.js'

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
const MAX_EDGE = 1600 // keep in step with App\Services\ImageOptimizer::MAX_EDGE
const QUALITY = 0.82 // ImageOptimizer::QUALITY
const SHRINK_ABOVE_BYTES = 300 * 1024
const SHRINK_CONCURRENCY = 2
const STALL_MS = 45000

async function shrink(file) {
  if (file.type === 'image/gif' || file.size <= SHRINK_ABOVE_BYTES || !window.createImageBitmap) return file

  try {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' })
    const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height))
    const canvas = document.createElement('canvas')
    canvas.width = Math.round(bitmap.width * scale)
    canvas.height = Math.round(bitmap.height * scale)
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height)
    bitmap.close?.()

    // PNG/WebP may have transparency, so keep them as WebP; everything else becomes JPEG.
    const type = file.type === 'image/jpeg' ? 'image/jpeg' : 'image/webp'
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, type, QUALITY))
    if (!blob || blob.size >= file.size) return file

    const name = file.name.replace(/\.[^.]+$/, '') + (type === 'image/jpeg' ? '.jpg' : '.webp')
    return new File([blob], name, { type, lastModified: Date.now() })
  } catch {
    return file // the browser couldn't decode it (e.g. HEIC); let the server judge the original
  }
}

// Runs `fn` over `items` with at most `limit` in flight, keeping the input order.
async function mapLimit(items, limit, fn) {
  const out = new Array(items.length)
  let next = 0
  const worker = async () => {
    while (next < items.length) {
      const i = next++
      out[i] = await fn(items[i])
    }
  }
  await Promise.all(Array.from({ length: Math.min(limit, items.length) }, worker))
  return out
}

// The form's Save button is locked while any uploader has announced `photo-upload-start` and not yet
// `photo-upload-end` (each event carries the uploader's id; see invitation-manage-page.blade.php).
// Both go out on `window`, never from an element: inside a method Alpine resolves `this.$dispatch`,
// `this.$root` and `this.$wire` against the element whose directive called it — for `pick()` that's
// the file input, and the gallery's input is removed by Livewire as soon as the gallery is full, so an
// event dispatched from it after the last upload never reached the form and Save stayed stuck on
// «Uploading photos…». The root and $wire are captured once in init() for the same reason.
let nextUploaderId = 0

const announce = (type, id) => window.dispatchEvent(new CustomEvent(`photo-upload-${type}`, { detail: { id } }))

// `data-room` on the component root holds how many more gallery photos fit; it's read at pick
// time because Livewire re-renders it after every upload/removal.
export function photoUploader({ name, multiple = false, maxMb = 3, gifMb = 3 }) {
  const id = `${name}#${++nextUploaderId}`
  let root = null
  let wire = null

  return {
    busy: false,
    preparing: false,
    progress: 0,
    error: '',
    stallTimer: null,
    cancelled: false,
    // true from start() until the matching end event has gone out — not tied to `busy`
    announced: false,

    init() {
      root = this.$el
      wire = this.$wire
    },

    // the Livewire component, captured while the root is in the page (falls back to the live lookup)
    server() {
      return wire ?? this.$wire
    },

    async pick(event) {
      const files = [...event.target.files]
      event.target.value = '' // so picking the same file again still fires `change`
      if (this.busy || files.length === 0) return

      this.error = ''

      const room = Number(root?.dataset.room ?? Infinity)
      if (multiple && files.length > room) {
        this.reject(room > 0
          ? t('You picked :count photos, but only :room more fit in the gallery.', { count: files.length, room })
          : t('The gallery is full — remove a photo first.'))
        return
      }

      for (const file of files) {
        if (!ACCEPTED.includes(file.type)) {
          this.reject(t('":name" is not a JPG, PNG, WebP or GIF photo.', { name: file.name }))
          return
        }
        if (file.type === 'image/gif' && file.size > gifMb * 1024 * 1024) {
          this.reject(t('":name" is too big. GIFs can be at most :max MB.', { name: file.name, max: gifMb }))
          return
        }
      }

      this.start()
      this.preparing = true
      const ready = await mapLimit(files, SHRINK_CONCURRENCY, shrink)
      this.preparing = false

      const tooBig = ready.find((file) => file.size > maxMb * 1024 * 1024)
      if (tooBig) {
        this.finish(`"${tooBig.name}" is too big. Photos can be at most ${maxMb} MB.`)
        return
      }

      if (multiple) {
        await this.uploadEach(ready)
        return
      }

      const done = () => this.finish()
      const failed = () => this.finish() // the server's _uploadErrored puts a readable message in the error bag
      const progress = (e) => {
        this.progress = e.detail.progress
        this.armStallTimer()
      }

      this.armStallTimer()
      try {
        this.server().$upload(name, ready[0], done, failed, progress, done)
      } catch (err) {
        this.finish(t('The upload failed. Please try again.'))
      }
    },

    // Gallery: send the photos ONE AT A TIME, each appended to the gallery's pending list. One big
    // multi-file request breaks when many photos are picked together (the server's request-size /
    // file-count limits apply to the whole batch, and one bad file failed them all). Each photo has
    // its own try/catch: a failed one is skipped and counted, the rest still go up.
    async uploadEach(files) {
      this.cancelled = false
      let failed = 0
      for (let i = 0; i < files.length; i++) {
        if (this.cancelled) break
        try {
          const ok = await this.uploadOne(files[i], (p) => {
            this.progress = Math.round(((i + p / 100) / files.length) * 100)
          })
          if (!ok) failed++
        } catch {
          failed++
        }
      }
      if (this.cancelled) return
      this.progress = 100
      this.finish(failed
        ? t(':failed of :count photos couldn\'t be uploaded — please try those again.', { failed, count: files.length })
        : '')
    },

    // One photo → resolves true when it's uploaded, false when it failed or stalled (never rejects).
    uploadOne(file, onProgress) {
      return new Promise((resolve) => {
        let settled = false
        const settle = (ok) => {
          if (settled) return
          settled = true
          clearTimeout(this.stallTimer)
          resolve(ok)
        }
        const arm = () => {
          clearTimeout(this.stallTimer)
          this.stallTimer = setTimeout(() => {
            try { this.server().$cancelUpload(name) } catch {}
            settle(false)
          }, STALL_MS)
        }
        arm()
        try {
          // append = true (Livewire's default): adds to the files already waiting in this gallery
          this.server().$uploadMultiple(name, [file], () => settle(true), () => settle(false), (e) => {
            onProgress(e.detail.progress)
            arm()
          }, () => settle(false))
        } catch {
          settle(false)
        }
      })
    },

    // shows the problem under the field and as a toast
    reject(message) {
      this.error = message
      window.toast?.('error', message)
    },

    cancel() {
      this.cancelled = true
      try { this.server().$cancelUpload(name) } catch {}
      this.finish()
    },

    start() {
      this.busy = true
      this.progress = 0
      this.announced = true
      announce('start', id)
    },

    // Always closes what start() opened, even if `busy` was already cleared some other way.
    finish(error = '') {
      clearTimeout(this.stallTimer)
      const wasBusy = this.busy
      this.busy = false
      this.preparing = false
      if (wasBusy) {
        if (error) this.reject(error)
        else this.error = ''
      }
      if (this.announced) {
        this.announced = false
        announce('end', id)
      }
    },

    // Restarted on every progress tick, so it only fires when nothing has moved for STALL_MS
    // (including a server that never answers after the bytes are sent).
    armStallTimer() {
      clearTimeout(this.stallTimer)
      this.stallTimer = setTimeout(() => {
        try { this.server().$cancelUpload(name) } catch {}
        this.finish(t('The upload is taking too long. Please try again on a better connection, or use a smaller photo.'))
      }, STALL_MS)
    },

    // removed from the page (a re-render, leaving the tab) while uploading: let the form go
    destroy() {
      clearTimeout(this.stallTimer)
      if (this.announced) {
        this.announced = false
        announce('end', id)
      }
    },
  }
}

// Song picker (Alpine component `audioUploader`): same busy/progress/cancel/stall handling as the
// photo picker, but nothing to shrink — a file over the cap or of the wrong type is refused here,
// before a single byte is sent (the server checks both again).
const AUDIO_TYPES = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'audio/aac', 'audio/x-aac', 'audio/aacp']
const AUDIO_EXTENSIONS = /\.(mp3|m4a|aac)$/i

export function audioUploader({ name, maxMb = 10 }) {
  return {
    ...photoUploader({ name, maxMb }),

    pick(event) {
      const file = event.target.files[0]
      event.target.value = ''
      if (this.busy || !file) return

      this.error = ''
      // some phones report m4a with an empty type, so the extension counts too
      if (!AUDIO_TYPES.includes(file.type) && !AUDIO_EXTENSIONS.test(file.name)) {
        this.reject(t('":name" is not an MP3 or M4A audio file.', { name: file.name }))
        return
      }
      if (file.size > maxMb * 1024 * 1024) {
        const mb = (file.size / 1024 / 1024).toFixed(file.size > 100 * 1024 * 1024 ? 0 : 1)
        this.reject(t('":name" is :mb MB — audio files can be at most :max MB (≈ a 10-minute song). Please use a shorter song or a 128 kbps MP3.', { name: file.name, mb, max: maxMb }))
        return
      }

      this.start()
      this.armStallTimer()
      const done = () => this.finish()
      try {
        this.server().$upload(name, file, done, done, (e) => {
          this.progress = e.detail.progress
          this.armStallTimer()
        }, done)
      } catch {
        this.finish(t('The upload failed. Please try again.'))
      }
    },
  }
}

// Register whether this script runs before Alpine starts (the normal case) or after it.
const register = () => {
  window.Alpine.data('photoUploader', photoUploader)
  window.Alpine.data('audioUploader', audioUploader)
}
if (window.Alpine) {
  register()
} else {
  document.addEventListener('alpine:init', register)
}

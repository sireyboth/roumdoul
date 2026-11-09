// Photo picker for the invitation dashboard (Alpine component `photoUploader`).
//
// Instead of letting `wire:model` push the raw file, it:
//  - rejects wrong types / oversized files / too many gallery photos before anything is sent,
//  - shrinks big photos in the browser (an 18 MB phone photo becomes ~1-2 MB), since the
//    server scales everything down to 1600px anyway,
//  - locks the picker (and the form's Save button) while an upload runs, so it can't be spammed,
//  - shows progress, can be cancelled, and gives up with a message if the upload stalls.

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
const MAX_EDGE = 2560
const SHRINK_ABOVE_BYTES = 1.5 * 1024 * 1024
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
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, type, 0.85))
    if (!blob || blob.size >= file.size) return file

    const name = file.name.replace(/\.[^.]+$/, '') + (type === 'image/jpeg' ? '.jpg' : '.webp')
    return new File([blob], name, { type, lastModified: Date.now() })
  } catch {
    return file // the browser couldn't decode it (e.g. HEIC); let the server judge the original
  }
}

// `data-room` on the component root holds how many more gallery photos fit; it's read at pick
// time because Livewire re-renders it after every upload/removal.
export function photoUploader({ name, multiple = false, maxMb = 20, gifMb = 5 }) {
  return {
    busy: false,
    preparing: false,
    progress: 0,
    error: '',
    stallTimer: null,

    async pick(event) {
      const files = [...event.target.files]
      event.target.value = '' // so picking the same file again still fires `change`
      if (this.busy || files.length === 0) return

      this.error = ''

      const room = Number(this.$root.dataset.room ?? Infinity)
      if (multiple && files.length > room) {
        this.reject(room > 0
          ? `You picked ${files.length} photos, but only ${room} more fit in the gallery.`
          : 'The gallery is full — remove a photo first.')
        return
      }

      for (const file of files) {
        if (!ACCEPTED.includes(file.type)) {
          this.reject(`"${file.name}" is not a JPG, PNG, WebP or GIF photo.`)
          return
        }
        if (file.type === 'image/gif' && file.size > gifMb * 1024 * 1024) {
          this.reject(`"${file.name}" is too big. GIFs can be at most ${gifMb} MB.`)
          return
        }
      }

      this.start()
      this.preparing = true
      const ready = await Promise.all(files.map(shrink))
      this.preparing = false

      const tooBig = ready.find((file) => file.size > maxMb * 1024 * 1024)
      if (tooBig) {
        this.finish(`"${tooBig.name}" is too big. Photos can be at most ${maxMb} MB.`)
        return
      }

      const done = () => this.finish()
      const failed = () => this.finish() // the server's _uploadErrored puts a readable message in the error bag
      const progress = (e) => {
        this.progress = e.detail.progress
        this.armStallTimer()
      }

      this.armStallTimer()
      if (multiple) {
        this.$wire.$uploadMultiple(name, ready, done, failed, progress, done)
      } else {
        this.$wire.$upload(name, ready[0], done, failed, progress, done)
      }
    },

    // shows the problem under the field and as a toast
    reject(message) {
      this.error = message
      window.toast?.('error', message)
    },

    cancel() {
      this.$wire.$cancelUpload(name)
      this.finish()
    },

    start() {
      this.busy = true
      this.progress = 0
      this.$dispatch('photo-upload-start')
    },

    finish(error = '') {
      if (!this.busy) return
      clearTimeout(this.stallTimer)
      this.busy = false
      this.preparing = false
      if (error) this.reject(error)
      else this.error = ''
      this.$dispatch('photo-upload-end')
    },

    // Restarted on every progress tick, so it only fires when nothing has moved for STALL_MS
    // (including a server that never answers after the bytes are sent).
    armStallTimer() {
      clearTimeout(this.stallTimer)
      this.stallTimer = setTimeout(() => {
        this.$wire.$cancelUpload(name)
        this.finish('The upload is taking too long. Please try again on a better connection, or use a smaller photo.')
      }, STALL_MS)
    },

    destroy() {
      clearTimeout(this.stallTimer)
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
        this.reject(`"${file.name}" មិនមែនជាឯកសារភ្លេង MP3 ឬ M4A ទេ។`)
        return
      }
      if (file.size > maxMb * 1024 * 1024) {
        const mb = (file.size / 1024 / 1024).toFixed(file.size > 100 * 1024 * 1024 ? 0 : 1)
        this.reject(`"${file.name}" ធំ ${mb} MB — ឯកសារភ្លេងធំបំផុត ${maxMb} MB (≈ បទ ១០ នាទី)។ សូមប្រើបទខ្លីជាងនេះ ឬ MP3 គុណភាព 128 kbps។`)
        return
      }

      this.start()
      this.armStallTimer()
      const done = () => this.finish()
      this.$wire.$upload(name, file, done, done, (e) => {
        this.progress = e.detail.progress
        this.armStallTimer()
      }, done)
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

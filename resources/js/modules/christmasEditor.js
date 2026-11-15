// Alpine component `xmEditor` — the Christmas template's dashboard editor
// (resources/views/livewire/pages/dashboard/partials/christmas-editor.blade.php).
//
// - keeps the stored `event_date` («2026-12-19T18:30») in step with its separate Date and
//   Start time inputs, and `dress_code` with the dress-code select (+ "Other" text box)
// - sends the form's current values to the live preview iframe (public/js/invitations/christmas.js)
//   with postMessage whenever something changes — nothing is saved until Save
// - "Download as PNG": asks the preview to draw cover.png and details.png and downloads them

const toast = (type, message) => window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }))

function splitStart(value) {
  const raw = String(value ?? '').trim()
  const d = /^(\d{4}-\d{2}-\d{2})/.exec(raw)
  const t = /(?:^|[T ])(\d{2}:\d{2})/.exec(raw)
  return [d ? d[1] : '', t ? t[1] : '']
}

function saveFile(name, blob) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = name
  document.body.appendChild(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 10000)
}

function xmEditor(config) {
  return {
    date: '',
    time: '',
    dressChoice: '',
    dressOther: '',
    guestName: '',
    msgLen: 0,
    moreOpen: !!config.moreOpen,
    extrasOpen: !!config.extrasOpen,
    ready: false,
    downloading: false,
    frameTimer: null,
    captureTimer: null,

    init() {
      this.load()
      this.$watch('date', () => this.syncStart())
      this.$watch('time', () => this.syncStart())
      this.$watch('dressChoice', () => this.syncDress())
      this.$watch('dressOther', () => this.syncDress())
      this.$watch('guestName', () => this.push())

      this.onMessage = (e) => this.receive(e)
      window.addEventListener('message', this.onMessage)

      // The preview announces itself with 'xm:ready', but it may have finished loading before this
      // component started listening — so also treat an iframe that has (re)loaded as ready.
      const frame = this.$refs.frame
      const markReady = () => {
        this.ready = true
        this.push()
      }
      frame.addEventListener('load', markReady)
      try {
        if (frame.contentDocument?.readyState === 'complete' && frame.contentWindow.location.href !== 'about:blank') markReady()
      } catch {
        // not loaded yet (or not same-origin): wait for 'load' / 'xm:ready'
      }
    },

    destroy() {
      window.removeEventListener('message', this.onMessage)
    },

    /** (Re)reads the local inputs from the Livewire form — on start and after Reset. */
    load() {
      const values = this.$wire.fieldValues ?? {}
      ;[this.date, this.time] = splitStart(values.event_date)
      const dress = String(values.dress_code ?? '').trim()
      if (dress === '' || config.dressCodes.includes(dress)) {
        this.dressChoice = dress
        this.dressOther = ''
      } else {
        this.dressChoice = 'Other'
        this.dressOther = dress
      }
      this.msgLen = String(values.message ?? '').length
    },

    afterReset() {
      this.$nextTick(() => {
        this.load()
        this.guestName = ''
        this.push()
      })
    },

    syncStart() {
      const value = this.date || this.time ? `${this.date}T${this.time}` : ''
      if ((this.$wire.fieldValues ?? {}).event_date !== value) this.$wire.$set('fieldValues.event_date', value, false)
      this.push()
    },

    syncDress() {
      const value = this.dressChoice === 'Other' ? this.dressOther.trim() : this.dressChoice
      if ((this.$wire.fieldValues ?? {}).dress_code !== value) this.$wire.$set('fieldValues.dress_code', value, false)
      this.push()
    },

    /** Sends the current form values to the preview (batched to one message per frame). */
    push() {
      cancelAnimationFrame(this.frameTimer)
      this.frameTimer = requestAnimationFrame(() => {
        const frame = this.$refs.frame
        if (!this.ready || !frame?.contentWindow) return
        const fields = JSON.parse(JSON.stringify(this.$wire.fieldValues ?? {}))
        frame.contentWindow.postMessage({ type: 'xm:update', fields, guest: this.guestName }, window.location.origin)
      })
    },

    receive(e) {
      if (e.origin !== window.location.origin || !this.$refs.frame || e.source !== this.$refs.frame.contentWindow || !e.data) return

      if (e.data.type === 'xm:ready') {
        this.ready = true
        this.push()
      } else if (e.data.type === 'xm:captured') {
        clearTimeout(this.captureTimer)
        this.downloading = false
        e.data.files.forEach((file, i) => setTimeout(() => saveFile(file.name, file.blob), i * 400))
        toast('success', config.strings.downloaded)
      } else if (e.data.type === 'xm:capture-failed') {
        this.captureFailed()
      }
    },

    download() {
      const frame = this.$refs.frame
      if (this.downloading) return
      if (!this.ready || !frame?.contentWindow) {
        toast('error', config.strings.downloadFailed)
        return
      }
      this.downloading = true
      this.push()
      frame.contentWindow.postMessage({ type: 'xm:capture' }, window.location.origin)
      this.captureTimer = setTimeout(() => this.captureFailed(), 45000)
    },

    captureFailed() {
      clearTimeout(this.captureTimer)
      this.downloading = false
      toast('error', config.strings.downloadFailed)
    },

    /** A failed save names its first field: open the card it's in and bring it into view. */
    showError(key) {
      if (config.moreFields.includes(key)) this.moreOpen = true
      if (config.extraFields.includes(key)) this.extrasOpen = true
      const id = key === 'event_date' ? 'xm-field-event_date' : `xm-field-${key}`
      setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250)
    },
  }
}

function register() {
  window.Alpine.data('xmEditor', xmEditor)
}

if (window.Alpine) register()
else document.addEventListener('alpine:init', register)

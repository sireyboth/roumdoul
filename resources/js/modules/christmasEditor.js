// Alpine component `xmEditor` — the Christmas template's dashboard editor
// (resources/views/livewire/pages/dashboard/partials/christmas-editor.blade.php).
//
// - keeps the stored `event_date` («2026-12-19T18:30») in step with its separate Date and
//   Start time inputs, and `dress_code` with the dress-code select (+ "Other" text box)
// - sends the form's current values to the live preview iframe (public/js/invitations/christmas.js)
//   with postMessage whenever something changes — nothing is saved until Save
// - the invitation is two pages (two routes): the Cover / Details tabs load either one into the
//   iframe; the iframe also moves by itself when the gift box or "Back to cover" is tapped, and
//   says which page it is on with 'xm:ready'
// - "Download as PNG": loads each page in turn, asks it to draw itself (cover.png, details.png),
//   then returns to the page that was showing

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
    page: 'cover',
    downloading: false,
    pageWaiters: [],
    frameTimer: null,
    captureDone: null,

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
        this.page = this.framePage() ?? this.page
        this.push()
      }
      frame.addEventListener('load', markReady)
      try {
        if (frame.contentDocument?.readyState === 'complete' && frame.contentWindow.location.href !== 'about:blank') markReady()
      } catch {
        // not loaded yet (or not same-origin): wait for 'load' / 'xm:ready'
      }
    },

    /** Which page the iframe shows, from its URL (null while it isn't readable yet). */
    framePage() {
      try {
        const path = new URL(this.$refs.frame.contentWindow.location.href).pathname
        return /\/details\/?$/.test(path) ? 'details' : 'cover'
      } catch {
        return null
      }
    },

    /** Loads a page into the preview; resolves once that page has said it is ready. */
    showPage(page) {
      const frame = this.$refs.frame
      if (!frame?.contentWindow) return Promise.reject(new Error('no preview'))
      if (this.page === page && this.ready) return Promise.resolve()
      return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('preview timeout')), 30000)
        this.pageWaiters.push({ page, done: () => { clearTimeout(timer); resolve() } })
        this.ready = false
        this.page = page
        frame.contentWindow.location.replace(config.pages[page])
      })
    },

    /** Sends the form to the preview right away (not batched): a capture must see it first. */
    pushNow() {
      const frame = this.$refs.frame
      if (!frame?.contentWindow) return
      const fields = JSON.parse(JSON.stringify(this.$wire.fieldValues ?? {}))
      frame.contentWindow.postMessage({ type: 'xm:update', fields, guest: this.guestName }, window.location.origin)
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
        if (e.data.page) this.page = e.data.page
        this.pushNow()
        this.pageWaiters = this.pageWaiters.filter((w) => (w.page === this.page ? (w.done(), false) : true))
      } else if (e.data.type === 'xm:captured') {
        this.captureDone?.(e.data.files)
      } else if (e.data.type === 'xm:capture-failed') {
        this.captureDone?.(null)
      }
    },

    /** Asks the page in the preview for its PNG. */
    captureOne() {
      return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('capture timeout')), 45000)
        this.captureDone = (files) => {
          clearTimeout(timer)
          this.captureDone = null
          if (files) resolve(files)
          else reject(new Error('capture failed'))
        }
        this.pushNow()
        this.$refs.frame.contentWindow.postMessage({ type: 'xm:capture' }, window.location.origin)
      })
    },

    async download() {
      const frame = this.$refs.frame
      if (this.downloading) return
      if (!this.ready || !frame?.contentWindow) {
        toast('error', config.strings.downloadFailed)
        return
      }
      this.downloading = true
      const startPage = this.page
      try {
        const files = []
        for (const page of ['cover', 'details']) {
          await this.showPage(page)
          files.push(...(await this.captureOne()))
        }
        files.forEach((file, i) => setTimeout(() => saveFile(file.name, file.blob), i * 400))
        toast('success', config.strings.downloaded)
      } catch {
        toast('error', config.strings.downloadFailed)
      } finally {
        this.downloading = false
        if (this.page !== startPage) this.showPage(startPage).catch(() => {})
      }
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

/* Shared scratch-off card — resources/views/components/scratch-card.blade.php (styles: css/invitations/shared/scratch-card.css).
   Paints a foil over every [data-inv-scratch] card; the guest rubs it away and, past data-threshold of the foil,
   the rest melts away (.is-revealed + a bubbling `inv-scratch:reveal` event). Enter/Space reveals from the keyboard.

   window.InvScratch
     init(root)      paint any cards added later (runs by itself on load and after wire:navigate)
     reveal(card)    open a card from code
     setMuted(bool)  silence the scratch / reveal sounds (hook it to a template's own mute button)

   Theme from CSS (read when the foil is painted): --inv-scratch-foil (comma list of colours), --inv-scratch-ink,
   --inv-scratch-accent, --inv-scratch-font. A resize keeps what was already scratched (the foil is rescaled). */
(function () {
  'use strict';
  if (window.InvScratch) { window.InvScratch.init(document); return; }

  var DPR = Math.min(window.devicePixelRatio || 1, 2);
  var DEFAULT_FOIL = ['#d9c7c0', '#fbf3ef', '#e8cfd2', '#ffffff', '#d4c2b8', '#f6e7e4'];

  /* ---------- sound: one AudioContext, made on the first scratch (a user gesture), buffers fetched early ---------- */
  var muted = false, ac = null, out = null;
  var raw = {}, decoded = {};
  var AC = window.AudioContext || window.webkitAudioContext;
  var prefetch = function (url) {
    if (!url || !AC || raw[url]) return;
    raw[url] = fetch(url).then(function (r) { return r.arrayBuffer(); }).catch(function () { return null; });
  };
  var wake = function () {
    if (!AC) return null;
    if (!ac) { ac = new AC(); out = ac.createGain(); out.gain.value = muted ? 0 : 1; out.connect(ac.destination); }
    if (ac.state === 'suspended') ac.resume();
    return ac;
  };
  var buffer = function (url) {
    if (!url || !wake()) return Promise.resolve(null);
    if (!decoded[url]) {
      prefetch(url);
      decoded[url] = raw[url].then(function (a) {
        if (!a) return null;
        return new Promise(function (ok) { ac.decodeAudioData(a.slice(0), ok, function () { ok(null); }); });
      });
    }
    return decoded[url];
  };
  var playOnce = function (url, rate, vol) {
    if (!url || muted) return;
    buffer(url).then(function (b) {
      if (!b || muted) return;
      var s = ac.createBufferSource(), g = ac.createGain();
      s.buffer = b; s.playbackRate.value = rate || 1; g.gain.value = vol;
      s.connect(g); g.connect(out); s.start();
    });
  };
  /* the scratch loop: one looping source, only audible while the finger is moving */
  var scratcher = function (url) {
    var n = null, quiet = 0, want = false;
    return {
      start: function () {
        want = true;
        if (n || !url || muted) return;
        buffer(url).then(function (b) {
          if (!b || n || !want || muted) return;
          n = { s: ac.createBufferSource(), g: ac.createGain() };
          n.s.buffer = b; n.s.loop = true; n.g.gain.value = 0;
          n.s.connect(n.g); n.g.connect(out); n.s.start(0, Math.random() * b.duration);
        });
      },
      move: function () {
        if (!n) return;
        n.g.gain.setTargetAtTime(.28, ac.currentTime, .02);
        clearTimeout(quiet);
        quiet = setTimeout(function () { if (n) n.g.gain.setTargetAtTime(0, ac.currentTime, .04); }, 90);
      },
      stop: function () {
        want = false;
        if (!n) return;
        clearTimeout(quiet);
        var m = n; n = null;
        m.g.gain.setTargetAtTime(0, ac.currentTime, .03);
        setTimeout(function () { try { m.s.stop(); } catch (e) {} }, 200);
      },
    };
  };

  /* ---------- foil painting ---------- */
  var noise = null;
  var noiseTile = function () {
    if (noise) return noise;
    noise = document.createElement('canvas'); noise.width = noise.height = 96;
    var x = noise.getContext('2d'), d = x.createImageData(96, 96);
    for (var i = 0; i < d.data.length; i += 4) { var v = 180 + Math.random() * 75; d.data[i] = d.data[i + 1] = d.data[i + 2] = v; d.data[i + 3] = 40; }
    x.putImageData(d, 0, 0);
    return noise;
  };
  var css = function (el, name, fallback) { var v = getComputedStyle(el).getPropertyValue(name).trim(); return v || fallback; };
  var withAlpha = function (color, a) {
    var m = /^#([0-9a-f]{6})$/i.exec(color);
    if (!m) return color;
    var n = parseInt(m[1], 16);
    return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
  };
  var paint = function (card, x, W, H) {
    var foil = css(card, '--inv-scratch-foil', '').split(',').map(function (c) { return c.trim(); }).filter(Boolean);
    if (foil.length < 2) foil = DEFAULT_FOIL;
    var g = x.createLinearGradient(0, 0, W, H);
    foil.forEach(function (c, i) { g.addColorStop(i / (foil.length - 1), c); });
    x.globalCompositeOperation = 'source-over';
    x.fillStyle = g; x.fillRect(0, 0, W, H);
    x.globalAlpha = .6; x.fillStyle = x.createPattern(noiseTile(), 'repeat'); x.fillRect(0, 0, W, H); x.globalAlpha = 1;
    for (var i = 0; i < 160; i++) { // glitter
      x.fillStyle = 'rgba(255,255,255,' + Math.random() * .9 + ')';
      x.beginPath(); x.arc(Math.random() * W, Math.random() * H, Math.random() * 1.7 * DPR, 0, 7); x.fill();
    }
    var text = card.getAttribute('data-cover') || '';
    var font = css(card, '--inv-scratch-font', '"Pinyon Script", "Kantumruy Pro", cursive');
    x.textAlign = 'center'; x.textBaseline = 'middle';
    if (text) {
      x.fillStyle = css(card, '--inv-scratch-ink', 'rgba(31,30,27,.62)');
      var size = Math.round(W * .16);
      x.font = size + 'px ' + font;
      while (size > 8 && x.measureText(text).width > W * .86) { size -= 2; x.font = size + 'px ' + font; }
      x.fillText(text, W / 2, H / 2);
    }
    if (card.getAttribute('data-heart') !== '0') {
      x.font = Math.round(W * .12) + 'px serif';
      x.fillStyle = withAlpha(css(card, '--inv-scratch-accent', '#d65f7f'), .75);
      x.fillText('♥', W / 2, H / 2 + (text ? W * .2 : 0));
    }
  };

  /* ---------- one card ---------- */
  var setup = function (card) {
    if (card._invScratch) return;
    var cv = card.querySelector('.inv-scratch__foil');
    if (!cv || !cv.getContext) return;
    card._invScratch = true;

    var x = cv.getContext('2d', { willReadFrequently: true });
    var W = 0, H = 0, hidden = [], painted = false, done = false, down = false, last = null, moves = 0;
    var threshold = parseFloat(card.getAttribute('data-threshold')) || .45;
    var soundUrl = card.getAttribute('data-sound'), revealUrl = card.getAttribute('data-reveal-sound');
    prefetch(soundUrl); prefetch(revealUrl);
    var snd = scratcher(soundUrl);

    // layout size (offsetWidth), not getBoundingClientRect(): a tilted or tweened card would measure wrong
    var size = function () {
      var w = Math.round(cv.offsetWidth * DPR), h = Math.round(cv.offsetHeight * DPR);
      if (!w || !h || done) return;
      if (!painted) {
        cv.width = W = w; cv.height = H = h;
        paint(card, x, W, H);
        painted = true;
        card.classList.add('is-ready');
        // links/buttons under the foil (e.g. a lightbox button) can't be reached until it's revealed
        hidden = [].slice.call(card.querySelectorAll('.inv-scratch__face a[href], .inv-scratch__face button, .inv-scratch__face [tabindex]'));
        hidden.forEach(function (el) { el.setAttribute('data-inv-tabindex', el.getAttribute('tabindex') || ''); el.setAttribute('tabindex', '-1'); });
      } else if (w !== W || h !== H) { // keep what's been scratched: rescale the current foil
        var keep = document.createElement('canvas'); keep.width = W; keep.height = H;
        keep.getContext('2d').drawImage(cv, 0, 0);
        cv.width = W = w; cv.height = H = h;
        x.drawImage(keep, 0, 0, W, H);
      }
      x.lineCap = 'round'; x.lineJoin = 'round'; x.lineWidth = W * .17; x.globalCompositeOperation = 'destination-out';
    };

    var font = css(card, '--inv-scratch-font', '"Pinyon Script", "Kantumruy Pro", cursive');
    var fontReady = document.fonts && document.fonts.load
      ? Promise.race([document.fonts.load('30px ' + font), new Promise(function (ok) { setTimeout(ok, 1500); })]).catch(function () {})
      : Promise.resolve();
    fontReady.then(function () {
      size();
      if ('ResizeObserver' in window) new ResizeObserver(size).observe(cv);
      else window.addEventListener('resize', size);
    });

    // offsetX/Y are in the canvas's own (untilted) space, so a rotated card still scratches under the finger
    var pos = function (e) { return [e.offsetX * W / cv.offsetWidth, e.offsetY * H / cv.offsetHeight]; };
    var line = function (a, c) { x.beginPath(); x.moveTo(a[0], a[1]); x.lineTo(c[0] + .01, c[1]); x.stroke(); };
    var reveal = function () {
      if (done) return;
      done = true; down = false; snd.stop();
      card.classList.add('is-revealed');
      card.removeAttribute('role'); card.removeAttribute('aria-label');
      if (document.activeElement === card) card.blur();
      card.removeAttribute('tabindex');
      hidden.forEach(function (el) {
        var t = el.getAttribute('data-inv-tabindex');
        if (t) el.setAttribute('tabindex', t); else el.removeAttribute('tabindex');
        el.removeAttribute('data-inv-tabindex');
      });
      playOnce(revealUrl, 1.3, .3);
      card.dispatchEvent(new CustomEvent('inv-scratch:reveal', { bubbles: true }));
    };
    card._invScratchReveal = reveal;
    var check = function () {
      if (done || !W) return;
      var d = x.getImageData(0, 0, W, H).data, clear = 0, total = 0;
      for (var i = 3; i < d.length; i += 4 * 29) { total++; if (d[i] < 40) clear++; }
      if (clear / total > threshold) reveal();
    };

    cv.addEventListener('pointerdown', function (e) {
      if (done || !painted) return;
      down = true; cv.setPointerCapture(e.pointerId);
      last = pos(e); line(last, last); snd.start();
    });
    cv.addEventListener('pointermove', function (e) {
      if (!down) return;
      var p = pos(e); line(last, p); last = p;
      snd.move();
      if (++moves % 10 === 0) check();
    });
    var up = function () { if (down) { down = false; snd.stop(); check(); } };
    cv.addEventListener('pointerup', up);
    cv.addEventListener('pointercancel', up);
    card.addEventListener('keydown', function (e) {
      if (done || e.target !== card || (e.key !== 'Enter' && e.key !== ' ')) return;
      e.preventDefault(); wake(); reveal();
    });
  };

  var init = function (root) {
    (root || document).querySelectorAll('[data-inv-scratch]').forEach(setup);
  };

  window.InvScratch = {
    init: init,
    reveal: function (card) { if (card && card._invScratchReveal) card._invScratchReveal(); },
    setMuted: function (on) { muted = !!on; if (out) out.gain.setTargetAtTime(muted ? 0 : 1, ac.currentTime, .05); },
    get muted() { return muted; },
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(document); });
  else init(document);
  document.addEventListener('livewire:navigated', function () { init(document); });
})();

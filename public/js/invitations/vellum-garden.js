/* Vellum Garden — resources/views/invitations/templates/wedding/vellum-garden.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.vg');
  if (!root) return;
  root.classList.add('js');

  var config = {};
  try { config = JSON.parse(document.getElementById('vg-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };

  /* ─────────── sound: little effects + the music, one mute button ───────────
     Effects only play after the guest's first tap (browsers block sound before that anyway). */
  var muted = false, unlocked = false;
  var pool = {};
  var sfx = function (name, vol) {
    if (muted || !unlocked || !sfxUrls[name]) return;
    try {
      var base = pool[name] || (pool[name] = new Audio(sfxUrls[name]));
      var a = base.paused ? base : base.cloneNode();
      a.volume = vol == null ? .45 : vol;
      a.currentTime = 0;
      var p = a.play();
      if (p && p.catch) p.catch(function () {});
    } catch (e) { /* no sound is fine */ }
  };

  var audio = document.getElementById('vg-audio');
  var soundBtn = document.getElementById('vg-sound');
  var yt = null, playing = false, songWanted = true;
  var setPlaying = function (on) {
    playing = on;
    if (soundBtn) soundBtn.classList.toggle('is-playing', on);
  };
  var startSong = function (fromGesture) {
    if (muted || playing || !songWanted) return;
    if (audio) {
      audio.volume = .7;
      var p = audio.play();
      setPlaying(true);
      if (p && p.catch) p.catch(function () { setPlaying(false); });
    } else if (config.youtube && fromGesture) {
      yt = document.createElement('iframe');
      yt.src = 'https://www.youtube.com/embed/' + config.youtube + '?autoplay=1&loop=1&playlist=' + config.youtube + '&controls=0&playsinline=1';
      yt.allow = 'autoplay; encrypted-media';
      yt.title = 'Background music';
      yt.setAttribute('aria-hidden', 'true');
      yt.tabIndex = -1;
      yt.style.cssText = 'position:fixed;width:1px;height:1px;left:-9999px;bottom:0;border:0;';
      document.body.appendChild(yt);
      setPlaying(true);
    }
  };
  var stopSong = function () {
    if (audio) audio.pause();
    if (yt) { yt.remove(); yt = null; }
    setPlaying(false);
  };
  var setMuted = function (on) {
    muted = on;
    if (soundBtn) {
      soundBtn.classList.toggle('is-muted', on);
      soundBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
      soundBtn.setAttribute('aria-label', on ? 'បើកសំឡេង' : 'បិទសំឡេង');
    }
    if (on) { songWanted = false; stopSong(); } else { songWanted = true; startSong(true); }
  };
  if (soundBtn) soundBtn.addEventListener('click', function () { setMuted(!muted); if (!muted) sfx('tap', .35); });

  // try the music straight away (allowed on some browsers); otherwise the first tap starts it
  if (audio) startSong(false);
  var firstTouch = function (e) {
    ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.removeEventListener(t, firstTouch, true); });
    unlocked = true;
    if (e && e.target && e.target.closest && e.target.closest('#vg-sound')) return; // that button decides itself
    startSong(true);
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });

  /* ─────────── reveal on scroll (cards fade up, botanical line art draws itself) ─────────── */
  var revealAll = function () {
    root.querySelectorAll('.vg-rv, .vg-draw').forEach(function (el) { el.classList.add('is-in'); });
  };
  var observe = function () {
    if (reduce || !('IntersectionObserver' in window)) { revealAll(); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        e.target.classList.add('is-in');
        io.unobserve(e.target);
      });
    }, { threshold: .12, rootMargin: '0px 0px -6% 0px' });
    root.querySelectorAll('.vg-page .vg-rv, .vg-page .vg-draw').forEach(function (el) { io.observe(el); });
  };

  /* ─────────── the envelope ─────────── */
  var gate = document.getElementById('vg-gate');
  var opened = false;
  var finishOpen = function () {
    root.classList.add('is-open');
    document.documentElement.classList.remove('vg-locked');
    if (gate && gate.parentNode) gate.parentNode.removeChild(gate);
    gate = null;
    var first = document.getElementById('vg-top');
    if (first) { first.setAttribute('tabindex', '-1'); first.focus({ preventScroll: true }); }
  };

  if (gate) {
    document.documentElement.classList.add('vg-locked');
    window.scrollTo(0, 0);
    var gateSprig = gate.querySelector('.vg-draw');
    setTimeout(function () { if (gateSprig) gateSprig.classList.add('is-in'); }, reduce ? 0 : 300);

    var openBtn = document.getElementById('vg-open');
    var open = function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      startSong(true);
      if (reduce) {
        sfx('open');
        gate.classList.add('is-leaving');
        setTimeout(function () { finishOpen(); observe(); }, 320);
        return;
      }
      sfx('seal', .6);
      gate.classList.add('is-broken');
      var steps = [
        [450, function () { sfx('open', .5); gate.classList.add('is-flap'); }],
        [1250, function () { gate.classList.add('is-rising'); }],
        [2300, function () { sfx('chime', .4); gate.classList.add('is-leaving'); observe(); }],
        [3300, finishOpen],
      ];
      steps.forEach(function (s) { setTimeout(s[1], s[0]); });
    };
    openBtn.addEventListener('click', open);
    // the whole envelope is a big target on a phone
    var env = document.getElementById('vg-env');
    if (env) env.addEventListener('click', function (e) { if (!e.target.closest('#vg-open')) open(); });
  } else {
    root.classList.add('is-open');
    observe();
  }

  /* ─────────── countdown (Khmer digits) ─────────── */
  var count = document.getElementById('vg-count');
  if (count) {
    var end = new Date(count.getAttribute('data-target')).getTime();
    var cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var tick = function () {
      var left = Math.max(0, end - Date.now());
      cells.d.textContent = khDigits(Math.floor(left / 864e5));
      cells.h.textContent = khDigits(Math.floor(left / 36e5) % 24);
      cells.m.textContent = khDigits(Math.floor(left / 6e4) % 60);
      cells.s.textContent = khDigits(Math.floor(left / 1e3) % 60);
    };
    tick();
    setInterval(tick, 1000);
  }

  /* ─────────── smooth in-page links + gentle tap sounds ─────────── */
  root.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (a) {
      var target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); sfx('tap', .3); target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' }); }
      return;
    }
    if (e.target.closest('[data-sfx="tap"], .inv-footer__top, .inv-footer a')) sfx('tap', .3);
  });

  /* ─────────── photo preview ─────────── */
  var lb = document.getElementById('vg-lb');
  if (lb) {
    var btns = Array.prototype.slice.call(root.querySelectorAll('.vg-gal-btn'));
    var img = document.getElementById('vg-lb-img'), cap = document.getElementById('vg-lb-cap');
    var idx = 0, last = null;
    var show = function (i) {
      idx = (i + btns.length) % btns.length;
      var b = btns[idx];
      img.style.animation = 'none'; void img.offsetWidth; img.style.animation = '';
      img.src = b.getAttribute('data-full');
      img.alt = b.querySelector('img') ? b.querySelector('img').alt : '';
      cap.textContent = b.getAttribute('data-cap') || '';
    };
    var close = function () {
      lb.hidden = true; img.removeAttribute('src');
      document.documentElement.classList.remove('vg-locked');
      if (last) last.focus();
    };
    btns.forEach(function (b, i) {
      b.addEventListener('click', function () {
        last = b; show(i); lb.hidden = false; sfx('card', .45);
        document.documentElement.classList.add('vg-locked');
        document.getElementById('vg-lb-x').focus();
      });
    });
    document.getElementById('vg-lb-prev').addEventListener('click', function () { show(idx - 1); sfx('card', .3); });
    document.getElementById('vg-lb-next').addEventListener('click', function () { show(idx + 1); sfx('card', .3); });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.closest('.vg-lb-x')) close(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') show(idx - 1);
      else if (e.key === 'ArrowRight') show(idx + 1);
    });
    var sx = null;
    lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var dx = e.changedTouches[0].clientX - sx; sx = null;
      if (Math.abs(dx) > 45) { show(idx + (dx < 0 ? 1 : -1)); sfx('card', .3); }
    });
  }

  /* ─────────── RSVP: status yes|no + optional note → the invitation RSVP endpoint ─────────── */
  var opts = document.getElementById('vg-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var form = document.getElementById('vg-rsvp-form'), note = document.getElementById('vg-rsvp-note'), thanks = document.getElementById('vg-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.vg-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.vg-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); });
      form.classList.add('show'); thanks.classList.remove('show');
      sfx('tap', .35);
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var done = function (ok) {
        btn.disabled = false;
        thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ។');
        thanks.classList.add('show');
        if (ok) { form.classList.remove('show'); sfx('reveal', .4); }
      };
      if (!rsvpUrl) { done(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }
})();

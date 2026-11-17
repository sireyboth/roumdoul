/* Front Page — resources/views/invitations/templates/wedding/front-page.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.fp');
  if (!root) return;
  root.classList.add('fp-js');

  var config = {};
  try { config = JSON.parse(document.getElementById('fp-config').textContent) || {}; } catch (e) { config = {}; }
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var io = 'IntersectionObserver' in window;

  /* ─────────── masthead names: each fills the column width (capped by screen height on desktop) ─────────── */
  var fits = root.querySelectorAll('[data-fit]');
  var fit = function () {
    var cap = window.innerWidth >= 980 ? window.innerHeight * 0.22 : window.innerWidth * 0.34;
    fits.forEach(function (el) {
      el.style.fontSize = '100px';
      var box = el.closest('.fp-mast') || el.parentNode;
      var w = box.clientWidth, sw = el.scrollWidth;
      if (!w || !sw) return;
      el.style.fontSize = Math.max(28, Math.min(cap, 100 * w / sw * 0.98)) + 'px';
    });
  };
  fit();
  window.addEventListener('resize', fit);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);

  /* ─────────── reveal on scroll (text masks, fades, strips printing) ─────────── */
  var reveals = root.querySelectorAll('.fp-rv, .fp-strips');
  if (!reduce && io) {
    var rio = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-in'); rio.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    reveals.forEach(function (el) { rio.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ─────────── running header: shows after the cover, with the page number you're on ─────────── */
  var run = document.getElementById('fpRun'), runNo = document.getElementById('fpRunNo');
  var cover = document.getElementById('fpCover');
  if (run && cover && io) {
    new IntersectionObserver(function (entries) {
      run.classList.toggle('is-on', !entries[0].isIntersecting);
    }, { threshold: 0, rootMargin: '-60px 0px 0px 0px' }).observe(cover);
    var pio = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting && runNo) runNo.textContent = e.target.getAttribute('data-page'); });
    }, { rootMargin: '-40% 0px -55% 0px' });
    root.querySelectorAll('[data-page]').forEach(function (s) { pio.observe(s); });
  }

  /* smooth in-page links */
  root.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a) return;
    var target = document.querySelector(a.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
  });

  /* ─────────── countdown ─────────── */
  var count = document.getElementById('fpCount');
  if (count && config.countdown) {
    var end = new Date(config.countdown).getTime(), cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var pad = function (n) { return n < 10 ? '0' + n : String(n); };
    var tick = function () {
      var left = Math.max(0, end - Date.now());
      cells.d.textContent = pad(Math.floor(left / 864e5));
      cells.h.textContent = pad(Math.floor(left / 36e5) % 24);
      cells.m.textContent = pad(Math.floor(left / 6e4) % 60);
      cells.s.textContent = pad(Math.floor(left / 1e3) % 60);
    };
    tick();
    setInterval(tick, 1000);
  }

  /* ─────────── sound effects: nothing plays until the guest's first tap ─────────── */
  var sfxUrls = config.sfx || {};
  var unlocked = false, muted = false;
  var VOL = { tap: 0.28, open: 0.45, photo: 0.4, paper: 0.35, chime: 0.45, tape: 0.4 };
  var sfx = function (name) {
    if (!unlocked || muted || !sfxUrls[name]) return;
    try {
      var a = new Audio(sfxUrls[name]);
      a.volume = VOL[name] || 0.35;
      var p = a.play();
      if (p && p.catch) p.catch(function () {});
    } catch (e) { /* no audio */ }
  };

  /* ─────────── music: autoplays when the browser allows, otherwise on the first tap ─────────── */
  var musicBtn = document.getElementById('fpMusic');
  var audio = document.getElementById('fpAudio');
  var music = config.music || {};
  var playing = false, songWanted = true, yt = null;
  var setPlaying = function (on) {
    playing = on;
    if (musicBtn) musicBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
  };
  var startSong = function () {
    if (playing || muted) return;
    if (audio) {
      setPlaying(true);
      var p = audio.play();
      if (p && p.catch) p.catch(function () { setPlaying(false); });
    } else if (music.youtube && unlocked) {
      yt = document.createElement('iframe');
      yt.src = 'https://www.youtube.com/embed/' + music.youtube + '?autoplay=1&loop=1&playlist=' + music.youtube + '&controls=0&playsinline=1';
      yt.allow = 'autoplay; encrypted-media';
      yt.title = 'Music';
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
  if (audio) {
    // try straight away; a browser that blocks sound leaves it to the first tap
    var first = audio.play();
    if (first && first.then) first.then(function () { setPlaying(true); }).catch(function () { setPlaying(false); });
  }
  if (musicBtn) {
    musicBtn.addEventListener('click', function () {
      if (muted) setMuted(false);
      sfx('tape');
      if (playing) { songWanted = false; stopSong(); } else { songWanted = true; startSong(); }
    });
  }
  document.addEventListener('visibilitychange', function () {
    if (!audio || !playing) return;
    if (document.hidden) audio.pause(); else { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });

  var firstTouch = function (e) {
    ['pointerdown', 'keydown'].forEach(function (t) { document.removeEventListener(t, firstTouch, true); });
    unlocked = true;
    var onCtrl = e && e.target && e.target.closest && e.target.closest('#fpMusic, #fpSound');
    if (songWanted && !onCtrl && !muted) startSong();
  };
  ['pointerdown', 'keydown'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });

  /* ─────────── mute everything ─────────── */
  var soundBtn = document.getElementById('fpSound');
  function setMuted(on) {
    muted = on;
    if (soundBtn) {
      soundBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
      soundBtn.setAttribute('aria-label', on ? 'បើកសំឡេង' : 'បិទសំឡេង');
    }
    if (on) stopSong(); else if (songWanted) startSong();
    if (window.InvScratch) window.InvScratch.setMuted(on);
  }
  if (soundBtn) soundBtn.addEventListener('click', function () { setMuted(!muted); });

  /* a soft sound on every tap of a button or link (the photo, scratch and controls have their own) */
  root.addEventListener('click', function (e) {
    var el = e.target.closest('a, button');
    if (!el || el.closest('.inv-scratch') || el.matches('#fpMusic, #fpSound, .fp-ph, .fp-opt, [type=submit]')) return;
    sfx(el.getAttribute('data-sfx') || 'tap');
  });

  /* ─────────── photo preview ─────────── */
  var lb = document.getElementById('fpLb');
  if (lb) {
    var img = document.getElementById('fpLbImg'), cap = document.getElementById('fpLbCap'), last = null;
    var close = function () { lb.hidden = true; img.removeAttribute('src'); sfx('paper'); if (last) last.focus(); };
    root.addEventListener('click', function (e) {
      var b = e.target.closest('.fp-ph');
      if (!b || !b.getAttribute('data-full')) return;
      last = b;
      img.src = b.getAttribute('data-full');
      cap.textContent = b.getAttribute('data-cap') || '';
      lb.hidden = false;
      sfx('photo');
      document.getElementById('fpLbX').focus();
    });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.closest('.fp-lb-x')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lb.hidden) close(); });
  }

  /* ─────────── RSVP: posts to the invitation RSVP endpoint (status yes|no + optional note) ─────────── */
  var opts = document.getElementById('fpRsvpOpts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var form = document.getElementById('fpRsvpForm'), note = document.getElementById('fpRsvpNote'), thanks = document.getElementById('fpRsvpThanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.fp-opt');
      if (!b) return;
      chosen = b.getAttribute('data-status');
      sfx('tap');
      opts.querySelectorAll('.fp-opt').forEach(function (o) { o.classList.toggle('chosen', o === b); });
      form.classList.add('show');
      thanks.classList.remove('show');
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!chosen) return;
      var done = function (ok) {
        thanks.textContent = !ok ? 'សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក គឺមានន័យណាស់សម្រាប់យើង។');
        thanks.classList.add('show');
        if (ok) { form.classList.remove('show'); sfx('chime'); } else sfx('tap');
      };
      if (!rsvpUrl) { done(true); return; } // demo preview: no recipient, nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }
})();

/* Khmer Traditional Wedding II (resources/views/invitations/templates/khmer-traditional-2.blade.php) */
(function () {
  var root = document.documentElement, body = document.body;
  root.classList.add('k2-js');
  body.classList.add('k2-body', 'k2-locked');

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var gate = document.getElementById('k2-gate');
  var cover = document.getElementById('k2-cover-video'), bg = document.getElementById('k2-bg-video');

  // cover.mp4 timeline (both renders): the card draws in over 0–2.6s, holds until ~15s, then fades to black.
  // The live text appears once the card is drawn, and the hold is looped until the guest opens it.
  var COVER_READY = 2.6, HOLD_FROM = 4, HOLD_TO = 14.5;
  // background.mp4: the lamps light up over 0–3.5s and dim again from ~8s; the lit stretch loops.
  var BG_FROM = 3.8, BG_TO = 7.4;

  /* --- layout picker: wide landscape screens get the 16:9 renders, phones the 9:16 ones. Keep the query
         in step with the CSS. The cover's poster is only used when the video can't play (it shows the
         finished card, while the video starts on black and draws it in). --- */
  var mq = window.matchMedia('(min-width: 900px) and (min-aspect-ratio: 5/4)');
  var coverFailed = false;
  function pick() {
    var land = mq.matches;
    root.classList.toggle('k2-land', land);
    [cover, bg].forEach(function (v) {
      var src = land ? v.dataset.land : v.dataset.src, poster = land ? v.dataset.landPoster : v.dataset.poster;
      if (v === bg || coverFailed) v.poster = poster;
      if (v.getAttribute('src') === src) return;
      var t = v.currentTime, wasPlaying = !!v.getAttribute('src') && !v.paused;
      v.src = src;
      // the two renders share one timeline, so a turned screen carries on where it was
      if (t) v.addEventListener('loadedmetadata', function () { v.currentTime = t; }, { once: true });
      if (wasPlaying) v.play().catch(function () {});
    });
    fitAll();
  }

  /* --- one-line text (initials, guest name, parents, names) shrinks until it fits its box --- */
  function fitAll() {
    document.querySelectorAll('[data-fit]').forEach(function (el) {
      el.style.fontSize = '';
      var w = el.clientWidth;
      if (!w) return;
      var size = parseFloat(getComputedStyle(el).fontSize);
      if (el.scrollWidth > w) el.style.fontSize = Math.max(size * w / el.scrollWidth - 0.5, 8) + 'px';
    });
  }

  /* --- cover --- */
  var ready = false, opened = false;
  function showCoverText() {
    if (ready) return;
    ready = true;
    gate.classList.add('ready');
    fitAll();
  }
  function coverFallback() {
    coverFailed = true;
    cover.poster = mq.matches ? cover.dataset.landPoster : cover.dataset.poster;
    showCoverText();
  }
  cover.addEventListener('timeupdate', function () {
    var t = cover.currentTime;
    if (t >= COVER_READY) showCoverText();
    if (!opened && t >= HOLD_TO) cover.currentTime = HOLD_FROM;
  });
  cover.addEventListener('ended', function () { if (!opened) { cover.currentTime = HOLD_FROM; cover.play().catch(function () {}); } });
  cover.addEventListener('error', coverFallback);

  /* --- background: the lit stretch loops --- */
  bg.addEventListener('timeupdate', function () { if (bg.currentTime >= BG_TO) bg.currentTime = BG_FROM; });
  bg.addEventListener('ended', function () { bg.currentTime = BG_FROM; bg.play().catch(function () {}); });

  pick();
  if (mq.addEventListener) mq.addEventListener('change', pick); else mq.addListener(pick);
  addEventListener('resize', fitAll);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitAll);

  if (reduced) coverFallback();
  else {
    var p = cover.play();
    if (p) p.catch(coverFallback);   // e.g. iOS low-power mode blocks autoplay: show the finished card
    setTimeout(showCoverText, 5000); // a slow download never hides the open button
  }

  /* --- music (an uploaded song, or a YouTube iframe primed muted; the open tap is the user gesture) --- */
  var yt = document.getElementById('k2-yt'), audio = document.getElementById('k2-audio'), musicBtn = document.getElementById('k2-music'), playing = false;
  function ytCmd(fn) {
    if (audio) {
      if (fn === 'playVideo') { var p = audio.play(); if (p) p.catch(function () { setPlaying(false); }); }
      else if (fn === 'pauseVideo') audio.pause();
      return;
    }
    if (yt && yt.contentWindow) yt.contentWindow.postMessage(JSON.stringify({ event: 'command', func: fn, args: [] }), '*');
  }
  function setPlaying(on) {
    playing = on;
    if (!musicBtn) return;
    musicBtn.classList.toggle('playing', on);
    musicBtn.classList.toggle('paused', !on);
  }
  if (musicBtn) {
    musicBtn.addEventListener('click', function () {
      if (playing) { ytCmd('pauseVideo'); setPlaying(false); }
      else { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    });
  }

  /* --- section reveal on scroll --- */
  function startReveal() {
    var revs = document.querySelectorAll('.k2-rev');
    if ('IntersectionObserver' in window && !reduced) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
      }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
      revs.forEach(function (el) { io.observe(el); });
    } else {
      revs.forEach(function (el) { el.classList.add('in'); });
    }
  }

  /* --- open: the card fades away over the lamp-lit background, which starts lighting up underneath --- */
  document.getElementById('k2-open').addEventListener('click', function () {
    if (opened) return;
    opened = true;
    if (yt || audio) { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    if (musicBtn) musicBtn.classList.add('show');
    bg.currentTime = 0;
    if (!reduced) bg.play().catch(function () {});
    body.classList.add('k2-opened');
    body.classList.remove('k2-locked');
    gate.classList.add('open');
    window.scrollTo(0, 0);
    fitAll();
    setTimeout(startReveal, reduced ? 0 : 450);
    setTimeout(function () { gate.style.display = 'none'; cover.pause(); cover.removeAttribute('src'); cover.load(); }, reduced ? 0 : 1300);
  });

  // nothing plays while the tab is in the background
  document.addEventListener('visibilitychange', function () {
    if (reduced) return;
    var v = opened ? bg : cover;
    if (document.hidden) v.pause(); else v.play().catch(function () {});
  });

  var toTop = document.getElementById('k2-totop');
  if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); });

  /* --- countdown (the invitation's real event date) --- */
  var cd = document.querySelector('.k2-count');
  if (cd) {
    var target = new Date(cd.getAttribute('data-target')).getTime(), els = {};
    cd.querySelectorAll('[data-u]').forEach(function (b) { els[b.getAttribute('data-u')] = b; });
    var pad = function (n) { return String(n).padStart(2, '0'); };
    var tick = function () {
      var diff = Math.max(0, target - Date.now());
      els.d.textContent = pad(Math.floor(diff / 86400000));
      els.h.textContent = pad(Math.floor(diff / 3600000) % 24);
      els.m.textContent = pad(Math.floor(diff / 60000) % 60);
      els.s.textContent = pad(Math.floor(diff / 1000) % 60);
    };
    tick(); setInterval(tick, 1000);
  }

  // a little burst of gold hearts when a guest says yes
  function heartBurst(from) {
    if (reduced || !from || !from.animate) return;
    var r = from.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    for (var i = 0; i < 14; i++) {
      var h = document.createElement('span');
      h.className = 'k2-burst';
      h.setAttribute('aria-hidden', 'true');
      h.textContent = i % 3 ? '♥' : '✿';
      h.style.cssText = 'left:' + cx + 'px;top:' + cy + 'px;color:' + (i % 2 ? '#F3D27A' : '#E7A5A0');
      document.body.appendChild(h);
      var dx = (Math.random() - .5) * 300, dy = -70 - Math.random() * 190;
      h.animate([{ transform: 'translate(0,0) scale(.4)', opacity: 1 }, { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(1.4)', opacity: 0 }],
        { duration: 1400 + Math.random() * 900, easing: 'cubic-bezier(.2,.7,.3,1)' }).onfinish = h.remove.bind(h);
    }
  }

  /* --- RSVP: posts to the invitation RSVP endpoint (status yes|no + optional note) --- */
  var opts = document.getElementById('k2-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var form = document.getElementById('k2-rsvp-form'), note = document.getElementById('k2-rsvp-note'), thanks = document.getElementById('k2-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.k2-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.k2-opt').forEach(function (o) { o.classList.toggle('chosen', o === b); });
      form.classList.add('show'); thanks.classList.remove('show');
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var done = function (ok) {
        thanks.textContent = !ok ? 'សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។ ក្តីនឹករលឹក និងពរជ័យរបស់លោកអ្នក គឺមានន័យណាស់សម្រាប់យើង។');
        if (ok && chosen === 'yes') heartBurst(form.querySelector('button'));
        thanks.classList.add('show'); if (ok) form.classList.remove('show');
      };
      if (!rsvpUrl) { done(true); return; }   // demo preview: no recipient, nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }
})();

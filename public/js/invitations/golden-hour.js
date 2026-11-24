/* Golden Hour — resources/views/invitations/templates/wedding/golden-hour.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.gh');
  if (!root) return;
  root.classList.add('js');

  var config = {};
  try { config = JSON.parse(document.getElementById('gh-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
  var accent = getComputedStyle(root).getPropertyValue('--gh-accent').trim() || '#d4a76a';

  /* ─────────── sound: little effects + the music, one mute button ───────────
     Effects only play after the guest's first tap, and stay soft under the song. */
  var muted = false, unlocked = false, pool = {};
  var sfx = function (name, vol) {
    if (muted || !unlocked || !sfxUrls[name]) return;
    try {
      var base = pool[name] || (pool[name] = new Audio(sfxUrls[name]));
      var a = base.paused ? base : base.cloneNode();
      a.volume = vol == null ? .4 : vol;
      a.currentTime = 0;
      var p = a.play();
      if (p && p.catch) p.catch(function () {});
    } catch (e) { /* no sound is fine */ }
  };

  var audio = document.getElementById('gh-audio');
  var soundBtn = document.getElementById('gh-sound');
  var nowPlaying = document.getElementById('gh-now');
  var yt = null, playing = false, songWanted = true, nowTimer = null;
  var setPlaying = function (on) {
    playing = on;
    if (soundBtn) soundBtn.classList.toggle('is-playing', on);
    if (nowPlaying) {
      clearTimeout(nowTimer);
      nowPlaying.classList.toggle('show', on);
      // the song name slides out for a few seconds, then tucks away again
      if (on) nowTimer = setTimeout(function () { nowPlaying.classList.remove('show'); }, 6000);
    }
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
  if (soundBtn) soundBtn.addEventListener('click', function () { setMuted(!muted); if (!muted) sfx('tap', .3); });

  var firstTouch = function (e) {
    ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.removeEventListener(t, firstTouch, true); });
    unlocked = true;
    if (e && e.target && e.target.closest && e.target.closest('#gh-sound')) return; // that button decides itself
    // the song waits for the cover to open (the Open button starts it)
    if (!gate) startSong(true);
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });

  // little taps (map, phone, links, scroll arrow) get a soft pop
  root.addEventListener('click', function (e) {
    if (e.target.closest('[data-sfx="tap"]')) sfx('tap', .3);
  });

  /* ─────────── gold dust: motes drifting in the light; a tap scatters sparks ─────────── */
  var canvas = document.getElementById('gh-dust');
  var dust = { burst: function () {} };
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var motes = [], sparks = [];
    var hex = accent.replace('#', '');
    var rgb = hex.length === 6 ? [parseInt(hex.substr(0, 2), 16), parseInt(hex.substr(2, 2), 16), parseInt(hex.substr(4, 2), 16)] : [212, 167, 106];
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);
    var count = Math.round(Math.min(60, W * H / 16000));
    for (var i = 0; i < count; i++) {
      motes.push({ x: Math.random() * W, y: Math.random() * H, r: Math.random() * 1.8 + .4, vx: (Math.random() - .5) * .15, vy: -Math.random() * .25 - .05, a: Math.random() * Math.PI * 2, tw: Math.random() * .02 + .005 });
    }
    var lastScroll = window.scrollY, wind = 0;
    window.addEventListener('scroll', function () { wind += (window.scrollY - lastScroll) * .02; lastScroll = window.scrollY; }, { passive: true });
    // tap / burst particles are little hearts: gold, cream and a soft rose
    var heartTones = [accent, '#fff1d6', '#f3a6a0'];
    var heart = function (r) {
      ctx.beginPath();
      ctx.moveTo(0, r * .35);
      ctx.bezierCurveTo(-r * 1.1, -r * .35, -r * .5, -r * 1.05, 0, -r * .45);
      ctx.bezierCurveTo(r * .5, -r * 1.05, r * 1.1, -r * .35, 0, r * .35);
      ctx.closePath();
      ctx.fill();
    };
    dust.burst = function (x, y, n, colors) {
      for (var k = 0; k < (n || 14); k++) {
        var ang = Math.random() * Math.PI * 2, sp = Math.random() * 3 + 1;
        sparks.push({ x: x, y: y, vx: Math.cos(ang) * sp, vy: Math.sin(ang) * sp - 1.4, life: 1, r: Math.random() * 5 + 7, spin: (Math.random() - .5) * .5, c: colors ? colors[k % colors.length] : heartTones[k % heartTones.length] });
      }
    };
    var frame = function () {
      ctx.clearRect(0, 0, W, H);
      wind *= .92;
      for (var i = 0; i < motes.length; i++) {
        var m = motes[i];
        m.a += m.tw;
        m.x += m.vx + Math.sin(m.a) * .2;
        m.y += m.vy - wind * m.r * .3;
        if (m.y < -10) { m.y = H + 10; m.x = Math.random() * W; }
        if (m.y > H + 10) { m.y = -10; m.x = Math.random() * W; }
        if (m.x < -10) m.x = W + 10; else if (m.x > W + 10) m.x = -10;
        var al = .25 + Math.sin(m.a * 3) * .2 + .2;
        var g = ctx.createRadialGradient(m.x, m.y, 0, m.x, m.y, m.r * 4);
        g.addColorStop(0, 'rgba(255,240,210,' + al + ')');
        g.addColorStop(.4, 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + (al * .5) + ')');
        g.addColorStop(1, 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',0)');
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.arc(m.x, m.y, m.r * 4, 0, Math.PI * 2); ctx.fill();
      }
      for (var j = sparks.length - 1; j >= 0; j--) {
        var s = sparks[j];
        s.x += s.vx; s.y += s.vy; s.vy += .045; s.vx *= .97; s.life -= .016;
        if (s.life <= 0) { sparks.splice(j, 1); continue; }
        ctx.fillStyle = s.c;
        ctx.globalAlpha = Math.min(1, s.life * 1.4);
        ctx.save(); ctx.translate(s.x, s.y); ctx.rotate(Math.sin(s.life * 8) * .35 + s.spin);
        heart(s.r * (.6 + s.life * .4));
        ctx.restore();
        ctx.globalAlpha = 1;
      }
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
    // a tap on an empty spot scatters a few sparks
    document.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button, a, input, textarea, .gh-lb')) return;
      dust.burst(e.clientX, e.clientY, 10);
    }, { passive: true });
  }

  /* ─────────── words: split text into words for the word-by-word reveal ─────────── */
  root.querySelectorAll('[data-a="words"]').forEach(function (el) {
    var parts = el.textContent.split(/(\s+)/), w = 0;
    el.textContent = '';
    parts.forEach(function (p) {
      if (/^\s+$/.test(p)) { el.appendChild(document.createTextNode(p)); return; }
      if (!p) return;
      var s = document.createElement('span');
      s.className = 'gh-w'; s.textContent = p; s.style.setProperty('--w', Math.min(w++, 60));
      el.appendChild(s);
    });
  });

  /* ─────────── reveal on scroll ───────────
     Checked against the viewport on scroll (not IntersectionObserver: a «mask» element starts fully
     clipped, and a clipped element never counts as visible to the observer). */
  var pending = [];
  var observe = function () {
    pending = Array.prototype.slice.call(root.querySelectorAll('.gh-page .gh-a, .gh-page .gh-head'));
    if (reduce) { pending.forEach(function (el) { el.classList.add('is-in'); }); pending = []; }
  };
  var revealTick = function (vh) {
    if (!pending.length) return;
    pending = pending.filter(function (el) {
      var r = el.getBoundingClientRect();
      if (r.top < vh * .9 && r.bottom > 0) { el.classList.add('is-in'); return false; }
      // scrolled past without stopping (fast fling / jump link): show it anyway
      if (r.bottom <= 0) { el.classList.add('is-in'); return false; }
      return true;
    });
  };

  /* ─────────── parallax photos + the story line drawing as you scroll ─────────── */
  var speedEls = Array.prototype.slice.call(root.querySelectorAll('[data-speed]'));
  var storyList = document.getElementById('gh-story-list');
  var storyFill = document.getElementById('gh-story-fill');
  var storyItems = storyList ? Array.prototype.slice.call(storyList.querySelectorAll('.gh-story-item')) : [];
  var queued = false;
  var tick = function () {
    queued = false;
    var vh = window.innerHeight;
    revealTick(vh);
    if (!reduce) {
      speedEls.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -100 || r.top > vh + 100) return;
        var off = (r.top + r.height / 2 - vh / 2) * -parseFloat(el.getAttribute('data-speed'));
        el.style.transform = 'translate3d(0,' + off.toFixed(1) + 'px,0)';
      });
    }
    if (storyList) {
      var r = storyList.getBoundingClientRect();
      var p = Math.max(0, Math.min(1, (vh * .6 - r.top) / r.height));
      storyFill.style.setProperty('--p', p.toFixed(3));
      storyItems.forEach(function (it) {
        it.classList.toggle('is-reached', it.getBoundingClientRect().top < vh * .6 + 20);
      });
    }
  };
  var queue = function () { if (!queued) { queued = true; requestAnimationFrame(tick); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue);
  window.addEventListener('load', queue);

  /* ─────────── 3D tilt on photos (follows the pointer) ─────────── */
  if (!reduce && window.matchMedia('(hover: hover)').matches) {
    root.querySelectorAll('.gh-tilt').forEach(function (el) {
      el.addEventListener('pointermove', function (e) {
        var r = el.getBoundingClientRect();
        el.style.setProperty('--ry', ((e.clientX - r.left) / r.width - .5) * 14 + 'deg');
        el.style.setProperty('--rx', ((e.clientY - r.top) / r.height - .5) * -14 + 'deg');
      });
      el.addEventListener('pointerleave', function () { el.style.setProperty('--rx', '0deg'); el.style.setProperty('--ry', '0deg'); });
    });
  }

  /* ─────────── cover: tap Open → seam of light → doors swing open → flash ─────────── */
  var gate = document.getElementById('gh-gate');
  var start = function () {
    root.classList.add('is-open');
    document.documentElement.classList.remove('gh-locked');
    observe();
    tick();
  };
  if (gate) {
    document.documentElement.classList.add('gh-locked');
    var openBtn = document.getElementById('gh-open');
    var opened = false;
    var open = function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      sfx('tap', .45);
      startSong(true);
      gate.classList.add('is-pressed');
      if (reduce) {
        gate.classList.add('is-gone');
        start();
        setTimeout(function () { gate.remove(); gate = null; }, 400);
        return;
      }
      var r = openBtn.getBoundingClientRect();
      dust.burst(r.left + r.width / 2, r.top + r.height / 2, 26);
      var steps = [
        [250, function () { gate.classList.add('is-opening'); }],
        [700, function () { gate.classList.add('is-seam'); sfx('open', .45); }],
        [1000, function () { gate.classList.add('is-doors'); start(); }],
        [1500, function () { sfx('chime', .35); dust.burst(window.innerWidth / 2, window.innerHeight / 2, 40); }],
        [2300, function () { gate.classList.add('is-gone'); }],
        [3000, function () { gate.remove(); gate = null; }],
      ];
      steps.forEach(function (s) { setTimeout(s[1], s[0]); });
    };
    openBtn.addEventListener('click', open);
    openBtn.focus({ preventScroll: true });
  } else {
    start();
  }

  /* ─────────── countdown (flip on every change) ─────────── */
  var count = document.getElementById('gh-count');
  if (count) {
    var target = new Date(count.getAttribute('data-target')).getTime();
    var cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var set = function (u, v) {
      var txt = khDigits(String(v).padStart(2, '0'));
      if (cells[u].textContent === txt) return;
      cells[u].textContent = txt;
      cells[u].classList.remove('flip'); void cells[u].offsetWidth; cells[u].classList.add('flip');
    };
    var countTick = function () {
      var diff = Math.max(0, target - Date.now());
      if (diff === 0) {
        count.hidden = true;
        var done = document.getElementById('gh-count-done');
        if (done) done.hidden = false;
        return;
      }
      var s = Math.floor(diff / 1000);
      set('d', Math.floor(s / 86400)); set('h', Math.floor(s % 86400 / 3600));
      set('m', Math.floor(s % 3600 / 60)); set('s', s % 60);
      setTimeout(countTick, 1000);
    };
    countTick();
  }

  /* ─────────── photo preview (swipe, arrows, keyboard) ─────────── */
  var lb = document.getElementById('gh-lb');
  var phs = Array.prototype.slice.call(root.querySelectorAll('.gh-ph'));
  if (lb && phs.length) {
    var lbImg = document.getElementById('gh-lb-img'), lbCap = document.getElementById('gh-lb-cap');
    var idx = 0, lastFocus = null;
    var show = function (i, dir) {
      idx = (i + phs.length) % phs.length;
      lbImg.src = phs[idx].getAttribute('data-full');
      lbImg.alt = phs[idx].getAttribute('data-cap') || '';
      lbCap.textContent = phs[idx].getAttribute('data-cap') || '';
      lbImg.style.setProperty('--from', (dir || 0) * 30 + 'px');
      lbImg.classList.remove('swap'); void lbImg.offsetWidth; lbImg.classList.add('swap');
    };
    var close = function () {
      lb.classList.remove('show');
      setTimeout(function () { lb.hidden = true; }, 300);
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    phs.forEach(function (b, i) {
      b.addEventListener('click', function () {
        lastFocus = b;
        show(i, 0);
        lb.hidden = false;
        requestAnimationFrame(function () { lb.classList.add('show'); });
        document.getElementById('gh-lb-x').focus({ preventScroll: true });
        sfx('card', .35);
      });
    });
    var nav = lb.querySelectorAll('.gh-lb-nav');
    if (phs.length < 2) nav.forEach(function (n) { n.hidden = true; });
    document.getElementById('gh-lb-prev').addEventListener('click', function () { show(idx - 1, -1); sfx('card', .3); });
    document.getElementById('gh-lb-next').addEventListener('click', function () { show(idx + 1, 1); sfx('card', .3); });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.closest('.gh-lb-x')) { close(); sfx('tap', .25); } });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') { show(idx - 1, -1); sfx('card', .3); }
      else if (e.key === 'ArrowRight') { show(idx + 1, 1); sfx('card', .3); }
    });
    var sx = null;
    lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 40 && phs.length > 1) { show(idx + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1); sfx('card', .3); }
      sx = null;
    });
  }

  /* ─────────── hearts flying out of an element ─────────── */
  var hearts = function (el, n, chars) {
    if (reduce) return;
    var r = el.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    chars = chars || ['♥'];
    for (var i = 0; i < n; i++) {
      var s = document.createElement('span');
      s.className = 'gh-burst';
      s.textContent = chars[i % chars.length];
      var ang = Math.random() * Math.PI * 2, dist = 60 + Math.random() * 110;
      s.style.left = cx + 'px'; s.style.top = cy + 'px';
      s.style.setProperty('--x', Math.cos(ang) * dist + 'px');
      s.style.setProperty('--y', Math.sin(ang) * dist - 40 + 'px');
      s.style.setProperty('--r', (Math.random() * 80 - 40) + 'deg');
      s.style.setProperty('--s', 12 + Math.random() * 14 + 'px');
      s.style.setProperty('--c', i % 3 ? accent : '#fff1d6');
      document.body.appendChild(s);
      setTimeout(function (x) { return function () { x.remove(); }; }(s), 1300);
    }
  };

  /* ─────────── RSVP: status yes|no + optional note → the invitation RSVP endpoint ─────────── */
  var opts = document.getElementById('gh-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var form = document.getElementById('gh-rsvp-form'), note = document.getElementById('gh-rsvp-note'), thanks = document.getElementById('gh-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.gh-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.gh-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); });
      form.classList.add('show'); thanks.classList.remove('show');
      sfx('tap', .35);
      if (chosen === 'yes') hearts(b, 12);
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var done = function (ok) {
        btn.disabled = false;
        thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ។');
        thanks.classList.add('show');
        if (ok) {
          form.classList.remove('show');
          sfx('reveal', .4);
          var r = thanks.getBoundingClientRect();
          dust.burst(r.left + r.width / 2, r.top, 30, [accent, '#fff1d6', '#f3c9a8']);
          hearts(thanks, 16);
        }
      };
      if (!rsvpUrl) { done(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }

  /* ─────────── copy the gift account number ─────────── */
  var toast = document.getElementById('gh-toast'), toastTimer = null;
  var say = function (msg) {
    toast.textContent = msg; toast.classList.add('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2200);
  };
  var copyBtn = document.getElementById('gh-copy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var val = copyBtn.getAttribute('data-copy');
      var ok = function () { say('បានចម្លងលេខគណនី ✓'); sfx('tap', .35); hearts(copyBtn, 6); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(val).then(ok, function () { say(val); });
      else say(val);
    });
  }

  /* ─────────── dress code swatches: a tap wobbles the fabric ─────────── */
  root.querySelectorAll('.gh-swatch').forEach(function (sw) {
    sw.addEventListener('click', function () {
      sw.classList.remove('wobble'); void sw.offsetWidth; sw.classList.add('wobble');
      sfx('tap', .3);
      var r = sw.getBoundingClientRect();
      dust.burst(r.left + r.width / 2, r.top + r.height / 2, 8, [getComputedStyle(sw.parentNode).getPropertyValue('--c').trim() || accent]);
    });
  });

  /* ─────────── finale: send love (hearts + chime) ─────────── */
  var love = document.getElementById('gh-love');
  if (love) {
    love.addEventListener('click', function () {
      hearts(love, 14);
      var r = love.getBoundingClientRect();
      dust.burst(r.left + r.width / 2, r.top + r.height / 2, 18);
      sfx('chime', .3);
    });
  }
})();

/* Kbach Chhouk — resources/views/invitations/templates/wedding/kbach-chhouk.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.kc');
  if (!root) return;
  root.classList.add('js');

  var config = {};
  try { config = JSON.parse(document.getElementById('kc-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
  var isKh = function (s) { return /[ក-៿]/.test(s || ''); };
  var accent = getComputedStyle(root).getPropertyValue('--kc-accent').trim() || '#2f7a5a';
  var gate = document.getElementById('kc-gate');
  var wait = function (ms) { return new Promise(function (r) { setTimeout(r, ms); }); };

  /* ─────────── sound: effects + music, one mute button (effects only after the first tap) ─────────── */
  var muted = false, unlocked = false, pool = {};
  var sfx = function (name, vol) {
    if (muted || !unlocked || !sfxUrls[name]) return;
    try {
      var base = pool[name] || (pool[name] = new Audio(sfxUrls[name]));
      var a = base.paused ? base : base.cloneNode();
      a.volume = vol == null ? .35 : vol;
      a.currentTime = 0;
      var p = a.play();
      if (p && p.catch) p.catch(function () {});
    } catch (e) { /* no sound is fine */ }
  };
  var audio = document.getElementById('kc-audio');
  var soundBtn = document.getElementById('kc-sound');
  var nowPlaying = document.getElementById('kc-now');
  var yt = null, playing = false, songWanted = true, nowTimer = null;
  var setPlaying = function (on) {
    playing = on;
    if (soundBtn) soundBtn.classList.toggle('is-playing', on);
    if (nowPlaying) {
      clearTimeout(nowTimer);
      nowPlaying.classList.toggle('show', on);
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
    if (e && e.target && e.target.closest && e.target.closest('#kc-sound')) return;
    if (!gate) startSong(true); // with the cover, «បើកសំបុត្រ» starts the song
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });
  root.addEventListener('click', function (e) { if (e.target.closest('[data-sfx="tap"]')) sfx('tap', .28); });

  /* ─────────── fx canvas: lotus petals that turn as they drift, and floating lotus lanterns ─────────── */
  var fx = { burst: function () {}, rain: function () {}, lanterns: function () {}, start: function () {} };
  var canvas = document.getElementById('kc-fx');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var bits = [];
    var tones = ['#f4c6cb', '#fbe4e2', '#ffffff', '#e8a2ab', '#f7d9c4'];
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);
    var make = function (x, y, vx, vy, kind) {
      return { x: x, y: y, vx: vx, vy: vy, kind: kind, c: tones[(Math.random() * tones.length) | 0], s: kind === 'lantern' ? 9 + Math.random() * 6 : 5 + Math.random() * 6,
        rot: Math.random() * 6.28, vr: (Math.random() - .5) * .08, flip: Math.random() * 6.28, vf: .04 + Math.random() * .07, sway: Math.random() * 6.28, life: 1, ambient: false };
    };
    var petal = function (r, fill) {
      ctx.beginPath();
      ctx.moveTo(0, -r * 1.3);
      ctx.bezierCurveTo(r * .9, -r * .5, r * .75, r * .8, 0, r);
      ctx.bezierCurveTo(-r * .75, r * .8, -r * .9, -r * .5, 0, -r * 1.3);
      ctx.fillStyle = fill; ctx.fill();
      ctx.strokeStyle = 'rgba(184,149,90,.35)'; ctx.lineWidth = .6; ctx.stroke();
    };
    var lantern = function (b) {
      var r = b.s;
      var g = ctx.createRadialGradient(0, 0, 0, 0, 0, r * 3.2);
      g.addColorStop(0, 'rgba(255,224,160,.75)'); g.addColorStop(1, 'rgba(255,224,160,0)');
      ctx.fillStyle = g; ctx.beginPath(); ctx.arc(0, 0, r * 3.2, 0, 6.29); ctx.fill();
      [-1.05, 1.05, -.55, .55, 0].forEach(function (a) {
        ctx.save(); ctx.rotate(a * .7);
        ctx.beginPath(); ctx.moveTo(0, r * .5);
        ctx.bezierCurveTo(-r * .55, 0, -r * .45, -r * .9, 0, -r * 1.25);
        ctx.bezierCurveTo(r * .45, -r * .9, r * .55, 0, 0, r * .5);
        ctx.fillStyle = a === 0 ? '#fff4ec' : '#f6c9cd'; ctx.fill();
        ctx.strokeStyle = 'rgba(232,201,138,.9)'; ctx.lineWidth = .7; ctx.stroke();
        ctx.restore();
      });
      ctx.beginPath(); ctx.arc(0, -r * .1, r * .22, 0, 6.29); ctx.fillStyle = '#fff1c4'; ctx.fill();
    };
    fx.burst = function (x, y, n) {
      for (var i = 0; i < n; i++) {
        var a = -Math.PI / 2 + (Math.random() - .5) * 2.8, sp = 2.5 + Math.random() * 4.5;
        bits.push(make(x, y, Math.cos(a) * sp, Math.sin(a) * sp, 'petal'));
      }
    };
    fx.rain = function (n) {
      for (var i = 0; i < n; i++) {
        var b = make(Math.random() * W, -20 - Math.random() * H * .4, (Math.random() - .5) * .8, 1 + Math.random() * 1.2, 'petal');
        b.ambient = true; b.once = true;
        bits.push(b);
      }
    };
    // lotus lanterns rising slowly from the bottom of the screen
    fx.lanterns = function (n) {
      for (var i = 0; i < n; i++) {
        var b = make(W * (.1 + Math.random() * .8), H + 10 + Math.random() * 70, (Math.random() - .5) * .3, -(1.1 + Math.random() * .9), 'lantern');
        bits.push(b);
      }
    };
    var ambientOn = false;
    var frame = function () {
      ctx.clearRect(0, 0, W, H);
      if (ambientOn && Math.random() < .015 && bits.filter(function (b) { return b.ambient && !b.once; }).length < 8) {
        var a = make(Math.random() * W, -20, (Math.random() - .5) * .4, .45 + Math.random() * .5, 'petal');
        a.ambient = true; bits.push(a);
      }
      for (var i = bits.length - 1; i >= 0; i--) {
        var b = bits[i];
        b.sway += .03;
        if (b.kind === 'lantern') {
          b.x += b.vx + Math.sin(b.sway) * .35; b.y += b.vy;
          if (b.y < H * .25) b.life -= .006;
        } else if (b.ambient) {
          b.x += b.vx + Math.sin(b.sway) * .7; b.y += b.vy;
        } else {
          b.vy += .1; b.vx *= .98; b.vy *= .98;
          if (b.vy > 1.8) b.vy = 1.8;
          b.x += b.vx + (b.vy > .8 ? Math.sin(b.sway) * 1.1 : 0); b.y += b.vy;
          if (b.vy > 1.2) b.life -= .0045;
        }
        b.rot += b.vr; b.flip += b.vf;
        if (b.y > H + 40 || b.y < -80 || b.life <= 0) { bits.splice(i, 1); continue; }
        ctx.save();
        ctx.translate(b.x, b.y);
        if (b.kind === 'lantern') {
          ctx.globalAlpha = Math.min(1, b.life * 1.5);
          ctx.rotate(Math.sin(b.sway) * .08);
          lantern(b);
        } else {
          var f = Math.cos(b.flip);
          ctx.rotate(b.rot); ctx.scale(Math.max(.2, Math.abs(f)), 1);
          ctx.globalAlpha = b.ambient ? .8 : Math.min(1, b.life * 1.6);
          petal(b.s, b.c);
        }
        ctx.restore();
      }
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
    fx.start = function () { ambientOn = true; };
    document.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button, a, input, textarea, .kc-lb, .kc-gate')) return;
      fx.burst(e.clientX, e.clientY, 7);
    }, { passive: true });
  }

  /* ─────────── words: split for the word-by-word reveal ─────────── */
  root.querySelectorAll('[data-r="words"]').forEach(function (el) {
    var parts = el.textContent.split(/(\s+)/), w = 0;
    el.textContent = '';
    parts.forEach(function (p) {
      if (!p) return;
      if (/^\s+$/.test(p)) { el.appendChild(document.createTextNode(p)); return; }
      var s = document.createElement('span');
      s.className = 'kc-w'; s.textContent = p; s.style.setProperty('--w', Math.min(w++, 60));
      el.appendChild(s);
    });
  });

  /* ─────────── reveal on scroll (viewport position, checked every frame something moves) ─────────── */
  var pending = [];
  var collect = function () {
    pending = Array.prototype.slice.call(root.querySelectorAll('.kc-page .kc-r'));
    if (reduce) { pending.forEach(function (el) { el.classList.add('is-in'); }); pending = []; }
  };
  var revealTick = function (vh) {
    if (!pending.length) return;
    pending = pending.filter(function (el) {
      var r = el.getBoundingClientRect();
      if ((r.top < vh * .9 && r.bottom > 0) || r.bottom <= 0) {
        el.classList.add('is-in');
        if (el.classList.contains('kc-cal')) setTimeout(function () { sfx('chime', .18); }, 700);
        if (el.classList.contains('kc-invite') || el.classList.contains('kc-venue') || el.classList.contains('kc-letter')) sfx('paper', .16);
        return false;
      }
      return true;
    });
  };

  /* ─────────── lotus stem (programme) + river (story): paths built to the real size, drawn with scroll ─────────── */
  var stem = document.getElementById('kc-stem');
  var river = document.getElementById('kc-river');
  var steps = stem ? Array.prototype.slice.call(stem.querySelectorAll('.kc-step')) : [];
  var buildStem = function () {
    if (!stem) return;
    var svg = stem.querySelector('.kc-stem-line'), h = stem.offsetHeight, w = 56, d = 'M28 0';
    svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
    for (var y = 0; y <= h; y += 60) {
      d += 'Q' + (28 + (y / 60 % 2 ? -9 : 9)) + ' ' + (y + 30) + ' 28 ' + Math.min(h, y + 60);
    }
    svg.querySelectorAll('path').forEach(function (p) { p.setAttribute('d', d); });
  };
  var buildRiver = function () {
    if (!river) return;
    var svg = river.querySelector('.kc-river-svg');
    var w = river.offsetWidth, h = river.offsetHeight, rr = river.getBoundingClientRect();
    svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
    var pts = [];
    river.querySelectorAll('.kc-lantern').forEach(function (l) {
      var r = l.getBoundingClientRect();
      pts.push([r.left - rr.left + r.width / 2, r.top - rr.top + r.height * .75]);
    });
    if (!pts.length) return;
    pts.unshift([pts[0][0], 0]);
    pts.push([w / 2, h]);
    var d = 'M' + pts[0][0].toFixed(1) + ' ' + pts[0][1].toFixed(1);
    for (var i = 1; i < pts.length; i++) {
      var a = pts[i - 1], b = pts[i], my = (b[1] - a[1]) * .55;
      d += 'C' + a[0].toFixed(1) + ' ' + (a[1] + my).toFixed(1) + ' ' + b[0].toFixed(1) + ' ' + (b[1] - my).toFixed(1) + ' ' + b[0].toFixed(1) + ' ' + b[1].toFixed(1);
    }
    svg.querySelectorAll('path').forEach(function (p) { p.setAttribute('d', d); });
  };
  var progress = function (el, vh) {
    var r = el.getBoundingClientRect();
    return Math.max(0, Math.min(1, (vh * .72 - r.top) / r.height));
  };

  /* ─────────── scroll: reveals, parallax, stem/river progress, sway with scroll speed ─────────── */
  var speedEls = Array.prototype.slice.call(root.querySelectorAll('[data-speed]'));
  var lastY = window.scrollY, velocity = 0, queued = false, settle = null;
  var tick = function () {
    queued = false;
    var vh = window.innerHeight, y = window.scrollY;
    revealTick(vh);
    velocity = velocity * .6 + (y - lastY) * .4;
    lastY = y;
    var sway = Math.max(-10, Math.min(10, velocity * .35));
    if (!reduce) {
      speedEls.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        var shift = (r.top + r.height / 2 - vh / 2) * -parseFloat(el.getAttribute('data-speed'));
        el.style.transform = 'translate3d(0,' + shift.toFixed(1) + 'px,0) rotate(' + (el.closest('.kc-deco') ? sway.toFixed(2) : 0) + 'deg)';
      });
      root.style.setProperty('--kc-vel', sway.toFixed(2));
    }
    if (stem) {
      var p = reduce ? 1 : progress(stem, vh);
      stem.style.setProperty('--p', p.toFixed(3));
      var sr = stem.getBoundingClientRect();
      steps.forEach(function (s) {
        var at = (s.offsetTop + s.offsetHeight / 2) / sr.height;
        if (p >= at - .04 && !s.classList.contains('is-open')) { s.classList.add('is-open'); sfx('tap', .18); }
      });
    }
    if (river) river.style.setProperty('--p', (reduce ? 1 : progress(river, vh)).toFixed(3));
    clearTimeout(settle);
    settle = setTimeout(function () { if (Math.abs(velocity) > .1) { velocity = 0; queue(); } }, 140);
  };
  var queue = function () { if (!queued) { queued = true; requestAnimationFrame(tick); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', function () { buildStem(); buildRiver(); queue(); });
  window.addEventListener('load', function () { buildStem(); buildRiver(); queue(); });

  /* ─────────── start: the page shows, things settle ─────────── */
  var heroLines = document.getElementById('kc-hero-lines');
  var heroPhoto = document.getElementById('kc-hero-photo');
  var start = function () {
    root.classList.add('is-open');
    document.documentElement.classList.remove('kc-locked');
    collect();
    buildStem(); buildRiver();
    tick();
    fx.start();
  };

  /* ─────────── cover: ripples → a giant lotus closes round the photo → it blooms open over the page,
     while the photo flows into the hero frame ─────────── */
  if (gate) {
    document.documentElement.classList.add('kc-locked');
    window.scrollTo(0, 0);
    var openBtn = document.getElementById('kc-open');
    var lotus = document.getElementById('kc-lotus');
    var ripples = document.getElementById('kc-ripples');
    var coverPhoto = document.getElementById('kc-cover-photo');
    var opened = false;
    var ripple = function (x, y, n, gap) {
      for (var k = 0; k < n; k++) {
        (function (k) {
          setTimeout(function () {
            var i = document.createElement('i');
            i.style.left = x + 'px'; i.style.top = y + 'px';
            ripples.appendChild(i);
            setTimeout(function () { i.remove(); }, 1900);
          }, k * gap);
        })(k);
      }
    };
    openBtn.addEventListener('click', function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      sfx('chime', .35);
      startSong(true);
      if (reduce) {
        start();
        heroLines.classList.add('is-drawn');
        gate.style.transition = 'opacity .5s ease';
        gate.style.opacity = '0';
        setTimeout(function () { gate.remove(); gate = null; if (lotus) lotus.remove(); }, 520);
        return;
      }
      var pr = coverPhoto.getBoundingClientRect();
      gate.classList.add('is-opening');
      ripple(pr.left + pr.width / 2, pr.bottom + 6, 3, 260);

      // the photo, lifted out of the cover so it can fly over everything
      var fly = coverPhoto.cloneNode(true);
      fly.removeAttribute('id');
      fly.className = 'kc-fly';
      fly.style.left = pr.left + 'px'; fly.style.top = pr.top + 'px';
      fly.style.width = pr.width + 'px'; fly.style.height = pr.height + 'px';
      document.body.appendChild(fly);
      coverPhoto.style.visibility = 'hidden';
      fly.animate([{ transform: 'none' }, { transform: 'translate(' + (pr.width * -.03) + 'px,' + (-14) + 'px) scale(1.06)' }], { duration: 900, easing: 'cubic-bezier(.3,.7,.2,1)', fill: 'forwards' });

      wait(320).then(function () {
        sfx('open', .4);
        lotus.classList.add('is-closing');
        return wait(1050);
      }).then(function () {
        // under the closed lotus: swap the cover for the page
        heroPhoto.classList.add('is-waiting');
        start();
        gate.remove(); gate = null;
        window.scrollTo(0, 0);
        var hr = heroPhoto.getBoundingClientRect();
        var cur = fly.getBoundingClientRect();
        var s = hr.width / pr.width;
        var from = getComputedStyle(fly).transform;
        var midX = (hr.left - pr.left) * .5 + (cur.left - pr.left) * .5, midY = Math.min(hr.top, cur.top) - pr.top - 40;
        var anim = fly.animate([
          { transform: from === 'none' ? 'none' : from },
          { transform: 'translate(' + midX + 'px,' + midY + 'px) scale(' + ((1 + s) / 2 * 1.05) + ')', offset: .55 },
          { transform: 'translate(' + (hr.left - pr.left) + 'px,' + (hr.top - pr.top) + 'px) scale(' + s + ')' }
        ], { duration: 1500, easing: 'cubic-bezier(.65,0,.25,1)', fill: 'forwards' });
        lotus.classList.remove('is-closing');
        lotus.classList.add('is-blooming');
        setTimeout(function () { sfx('chime', .3); fx.burst(window.innerWidth / 2, window.innerHeight * .45, 34); }, 450);
        anim.onfinish = function () {
          heroPhoto.classList.remove('is-waiting');
          heroLines.classList.add('is-drawn');
          var r = heroPhoto.getBoundingClientRect();
          fly.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 300, fill: 'forwards' }).onfinish = function () { fly.remove(); };
          fx.burst(r.left + r.width / 2, r.top + r.height * .9, 14);
        };
        setTimeout(function () { lotus.remove(); }, 3200);
      });
    });
    openBtn.focus({ preventScroll: true });
  } else {
    start();
    if (heroLines) setTimeout(function () { heroLines.classList.add('is-drawn'); }, 200);
  }

  /* ─────────── countdown in lotus medallions ─────────── */
  var count = document.getElementById('kc-count');
  if (count) {
    var target = new Date(count.getAttribute('data-target')).getTime();
    var cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var set = function (u, v) {
      var txt = khDigits(String(v).padStart(2, '0'));
      if (cells[u].textContent === txt) return;
      cells[u].textContent = txt;
      cells[u].classList.remove('tick'); void cells[u].offsetWidth; cells[u].classList.add('tick');
    };
    var countTick = function () {
      var diff = Math.max(0, target - Date.now());
      if (!diff) {
        count.hidden = true;
        var done = document.getElementById('kc-count-done');
        if (done) done.hidden = false;
        return;
      }
      var s = Math.floor(diff / 1000);
      set('d', Math.floor(s / 86400)); set('h', Math.floor(s % 86400 / 3600));
      set('m', Math.floor(s % 3600 / 60)); set('s', s % 60);
      setTimeout(countTick, 1000);
    };
    countTick();
    count.querySelectorAll('.kc-medal').forEach(function (m) {
      m.addEventListener('click', function () { sfx('tap', .22); var r = m.getBoundingClientRect(); fx.burst(r.left + r.width / 2, r.top + r.height / 2, 6); });
    });
  }

  /* ─────────── photo preview: zooms out of the tapped photo (FLIP), prev/next, swipe, keys ─────────── */
  var lb = document.getElementById('kc-lb');
  var lbImg = document.getElementById('kc-lb-img'), lbCap = document.getElementById('kc-lb-cap'), lbCount = document.getElementById('kc-lb-count');
  var lbPrev = document.getElementById('kc-lb-prev'), lbNext = document.getElementById('kc-lb-next');
  var lbItems = [], lbIndex = 0, lastFocus = null, lbBusy = false;
  var thumbs = Array.prototype.slice.call(root.querySelectorAll('.kc-ph'));
  var galleryThumbs = thumbs.filter(function (b) { return b.hasAttribute('data-i'); });
  var thumbRect = function (btn) { var img = btn.querySelector('img') || btn; return img.getBoundingClientRect(); };
  var flipFrom = function (rect, duration, reverse) {
    lbImg.getAnimations().forEach(function (a) { a.cancel(); });
    var big = lbImg.getBoundingClientRect();
    if (!big.width || !rect.width) return null;
    var s = Math.max(rect.width / big.width, rect.height / big.height);
    var ix = Math.max(0, (big.width - rect.width / s) / 2), iy = Math.max(0, (big.height - rect.height / s) / 2);
    var dx = rect.left + rect.width / 2 - (big.left + big.width / 2 * s) , dy = rect.top + rect.height / 2 - (big.top + big.height / 2 * s);
    var a = { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(' + s + ')', clipPath: 'inset(' + iy + 'px ' + ix + 'px round 10px)' };
    var b = { transform: 'none', clipPath: 'inset(0px 0px round 6px)' };
    return lbImg.animate(reverse ? [b, a] : [a, b], { duration: duration, easing: 'cubic-bezier(.3,.8,.2,1)', fill: 'both' });
  };
  var showItem = function (i, dir) {
    lbIndex = (i + lbItems.length) % lbItems.length;
    var btn = lbItems[lbIndex];
    lbImg.src = btn.getAttribute('data-full');
    var cap = btn.getAttribute('data-cap') || '';
    lbImg.alt = cap;
    lbCap.textContent = cap;
    lbCap.classList.toggle('kc-kh-cap', isKh(cap));
    lbCount.textContent = lbItems.length > 1 ? khDigits(lbIndex + 1) + ' / ' + khDigits(lbItems.length) : '';
    lbPrev.hidden = lbNext.hidden = lbItems.length < 2;
    if (dir) {
      lbImg.getAnimations().forEach(function (a) { a.cancel(); });
      lbImg.classList.remove('slide-l', 'slide-r'); void lbImg.offsetWidth;
      lbImg.classList.add(dir > 0 ? 'slide-l' : 'slide-r');
      sfx('card', .25);
    }
  };
  var openLb = function (btn) {
    if (lbBusy) return;
    lastFocus = btn;
    lbItems = galleryThumbs.indexOf(btn) >= 0 ? galleryThumbs : [btn];
    lb.hidden = false;
    document.documentElement.classList.add('kc-locked');
    showItem(lbItems.indexOf(btn), 0);
    lbImg.classList.remove('slide-l', 'slide-r');
    var go = function () {
      requestAnimationFrame(function () {
        lb.classList.add('show');
        if (!reduce) flipFrom(thumbRect(btn), 560, false);
      });
    };
    if (lbImg.complete && lbImg.naturalWidth) go(); else { lbImg.onload = function () { lbImg.onload = null; go(); }; }
    document.getElementById('kc-lb-x').focus({ preventScroll: true });
    sfx('card', .3);
  };
  var closeLb = function () {
    if (lb.hidden || lbBusy) return;
    lbBusy = true;
    var btn = lbItems[lbIndex];
    lb.classList.remove('show');
    lbImg.classList.remove('slide-l', 'slide-r');
    var r = thumbRect(btn);
    var visible = r.bottom > 0 && r.top < window.innerHeight;
    var anim = !reduce && visible ? flipFrom(r, 420, true) : lbImg.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 250, fill: 'both' });
    var done = function () {
      lb.hidden = true; lbBusy = false;
      if (anim) anim.cancel();
      document.documentElement.classList.remove('kc-locked');
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    if (anim) anim.onfinish = done; else done();
    sfx('tap', .22);
  };
  lb.addEventListener('click', function (e) {
    if (e.target.closest('#kc-lb-x') || e.target === lb || e.target.id === 'kc-lb-bg' || e.target.classList.contains('kc-lb-fig')) closeLb();
  });
  lbPrev.addEventListener('click', function (e) { e.stopPropagation(); showItem(lbIndex - 1, -1); });
  lbNext.addEventListener('click', function (e) { e.stopPropagation(); showItem(lbIndex + 1, 1); });
  document.addEventListener('keydown', function (e) {
    if (lb.hidden) return;
    if (e.key === 'Escape') closeLb();
    else if (e.key === 'ArrowRight' && lbItems.length > 1) showItem(lbIndex + 1, 1);
    else if (e.key === 'ArrowLeft' && lbItems.length > 1) showItem(lbIndex - 1, -1);
  });
  var sx = 0, sy = 0, swiping = false;
  lb.addEventListener('pointerdown', function (e) { sx = e.clientX; sy = e.clientY; swiping = true; });
  lb.addEventListener('pointerup', function (e) {
    if (!swiping) return;
    swiping = false;
    var dx = e.clientX - sx, dy = e.clientY - sy;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) && lbItems.length > 1) showItem(lbIndex + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
    else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) closeLb();
  });
  thumbs.forEach(function (b) { b.addEventListener('click', function () { openLb(b); }); });

  /* ─────────── map: tap to show the live map (circle wipe) ─────────── */
  var mapBtn = document.getElementById('kc-map-open');
  if (mapBtn) {
    mapBtn.addEventListener('click', function () {
      var map = document.getElementById('kc-map');
      var frame = map.querySelector('.kc-map-frame');
      if (frame && !frame.src) frame.src = frame.getAttribute('data-src');
      map.classList.add('is-open');
      sfx('open', .3);
    });
  }

  /* ─────────── dress code: silk swatches ripple on tap ─────────── */
  root.querySelectorAll('.kc-swatch').forEach(function (sw) {
    sw.addEventListener('click', function () {
      sw.classList.remove('wave'); void sw.offsetWidth; sw.classList.add('wave');
      sfx('tap', .28);
      var r = sw.getBoundingClientRect();
      fx.burst(r.left + r.width / 2, r.top + r.height / 3, 6);
    });
  });

  /* ─────────── RSVP → the reply floats away as a lotus lantern ─────────── */
  var opts = document.getElementById('kc-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var card = document.getElementById('kc-rsvp-card');
    var form = document.getElementById('kc-rsvp-form'), note = document.getElementById('kc-rsvp-note');
    var err = document.getElementById('kc-rsvp-err');
    var flyL = document.getElementById('kc-fly-lantern');
    var done = document.getElementById('kc-rsvp-done');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.kc-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.kc-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); });
      form.classList.add('show'); err.textContent = '';
      sfx('tap', .3);
      if (chosen === 'yes') { var r = b.getBoundingClientRect(); fx.burst(r.left + r.width / 2, r.top, 10); }
    });
    var send = function () {
      document.getElementById('kc-rsvp-done-title').textContent = chosen === 'yes' ? 'See you there' : 'Thank you';
      document.getElementById('kc-rsvp-done-text').textContent = chosen === 'yes'
        ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ។'
        : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ។';
      if (reduce) { card.hidden = true; done.hidden = false; return; }
      sfx('send', .35);
      card.classList.add('is-sending');
      flyL.classList.remove('is-flying'); void flyL.offsetWidth; flyL.classList.add('is-flying');
      setTimeout(function () {
        card.hidden = true; card.classList.remove('is-sending');
        done.hidden = false;
        sfx('chime', .28);
        var r = done.getBoundingClientRect();
        fx.burst(r.left + r.width / 2, r.top + 40, 26);
      }, 1500);
    };
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var finish = function (ok) {
        btn.disabled = false;
        if (!ok) { err.textContent = 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'; return; }
        send();
      };
      if (!rsvpUrl) { finish(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { finish(r.ok); }).catch(function () { finish(false); });
    });
    document.getElementById('kc-rsvp-again').addEventListener('click', function () {
      done.hidden = true; card.hidden = false;
      sfx('tap', .28);
    });
  }

  /* ─────────── gift: the lotus petals fold open over the QR ─────────── */
  var giftOpen = document.getElementById('kc-gift-open');
  if (giftOpen) {
    giftOpen.addEventListener('click', function () {
      var gift = document.getElementById('kc-gift');
      if (gift.classList.contains('is-open')) return;
      gift.classList.add('is-open');
      giftOpen.disabled = true;
      giftOpen.setAttribute('aria-hidden', 'true');
      document.getElementById('kc-gift-btns').classList.add('is-on');
      sfx('open', .35);
      var r = gift.getBoundingClientRect();
      fx.burst(r.left + r.width / 2, r.top + r.height / 2, 30);
    });
  }

  /* ─────────── copy the account number ─────────── */
  var toast = document.getElementById('kc-toast'), toastT = null;
  var say = function (m) { toast.textContent = m; toast.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(function () { toast.classList.remove('show'); }, 2200); };
  var copyBtn = document.getElementById('kc-copy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var v = copyBtn.getAttribute('data-copy');
      var ok = function () { say('បានចម្លងលេខគណនី ✓'); sfx('tap', .3); var r = copyBtn.getBoundingClientRect(); fx.burst(r.left + r.width / 2, r.top, 8); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(v).then(ok, function () { say(v); });
      else say(v);
    });
  }

  /* ─────────── finale: lotus lanterns float up (once by themselves, then on the button) ─────────── */
  var release = document.getElementById('kc-release');
  if (release) {
    release.addEventListener('click', function () { fx.lanterns(14); fx.rain(20); sfx('chime', .28); });
    var finale = document.getElementById('kc-finale'), sent = false;
    window.addEventListener('scroll', function () {
      if (sent || !root.classList.contains('is-open')) return;
      var r = finale.getBoundingClientRect();
      if (r.top < window.innerHeight * .5) { sent = true; fx.lanterns(8); }
    }, { passive: true });
  }
})();

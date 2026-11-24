/* Kbach Meas — resources/views/invitations/templates/wedding/kbach-meas.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.km');
  if (!root) return;
  root.classList.add('js');

  var $ = function (id) { return document.getElementById(id); };
  var config = {};
  try { config = JSON.parse($('km-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
  var clamp = function (v, a, b) { return Math.max(a, Math.min(b, v)); };
  var gold = getComputedStyle(root).getPropertyValue('--km-gold').trim() || '#c9a14a';
  var gate = $('km-gate');
  if (reduce) document.querySelectorAll('.km-shimmer').forEach(function (a) { a.remove(); });

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
  var audio = $('km-audio');
  var soundBtn = $('km-sound');
  var nowPlaying = $('km-now');
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
    if (e && e.target && e.target.closest && e.target.closest('#km-sound')) return;
    if (!gate) startSong(true); // with the cover, «បើកសំបុត្រ» starts the song
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });
  root.addEventListener('click', function (e) { if (e.target.closest('[data-sfx="tap"]')) sfx('tap', .28); });

  /* ─────────── gold dust rising + gold lotus petals tumbling in 3D ─────────── */
  var dust = { burst: function () {}, rain: function () {}, start: function () {} };
  var canvas = $('km-dust');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var bits = [], motes = [], running = false;
    var tones = [gold, '#f3dc95', '#fff3c4', '#e0b85c', '#a3242c', '#fbf4e3'];
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);
    var shade = function (hex, k) {
      var m = /^#?([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i.exec(hex);
      if (!m) return hex;
      return 'rgb(' + [1, 2, 3].map(function (i) { return Math.round(parseInt(m[i], 16) * k); }).join(',') + ')';
    };
    var make = function (x, y, vx, vy, color) {
      return { x: x, y: y, vx: vx, vy: vy, c: color || tones[(Math.random() * tones.length) | 0], s: 6 + Math.random() * 7,
        rot: Math.random() * 6.28, vr: (Math.random() - .5) * .16, flip: Math.random() * 6.28, vf: .06 + Math.random() * .1, sway: Math.random() * 6.28, life: 1, ambient: false };
    };
    var petal = function (r) { // a lotus petal: pointed tip, round base
      ctx.beginPath();
      ctx.moveTo(0, -r * 1.25);
      ctx.bezierCurveTo(r * .85, -r * .45, r * .7, r * .75, 0, r * .9);
      ctx.bezierCurveTo(-r * .7, r * .75, -r * .85, -r * .45, 0, -r * 1.25);
      ctx.fill();
    };
    dust.burst = function (x, y, n, color) {
      for (var i = 0; i < n; i++) {
        var a = -Math.PI / 2 + (Math.random() - .5) * 2.8, sp = 2.5 + Math.random() * 5;
        bits.push(make(x, y, Math.cos(a) * sp, Math.sin(a) * sp, color && Math.random() < .5 ? color : null));
      }
      kick();
    };
    dust.rain = function (n) {
      for (var i = 0; i < n; i++) {
        var left = i % 2 === 0;
        bits.push(make(left ? -10 : W + 10, H * .85, (left ? 1 : -1) * (4 + Math.random() * 6), -(9 + Math.random() * 7)));
      }
      kick();
    };
    var ambientOn = false;
    var frame = function () {
      ctx.clearRect(0, 0, W, H);
      if (ambientOn) {
        if (motes.length < 22 && Math.random() < .2) motes.push({ x: Math.random() * W, y: H + 10, v: .25 + Math.random() * .5, r: .6 + Math.random() * 1.6, t: Math.random() * 6.28, w: Math.random() * 6.28 });
        if (bits.filter(function (b) { return b.ambient; }).length < 5 && Math.random() < .008) {
          var b0 = make(Math.random() * W, -20, (Math.random() - .5) * .4, .45 + Math.random() * .5, [gold, '#f3dc95', '#a3242c'][(Math.random() * 3) | 0]);
          b0.ambient = true; bits.push(b0);
        }
      }
      for (var m = motes.length - 1; m >= 0; m--) {
        var d = motes[m];
        d.y -= d.v; d.w += .02; d.t += .05; d.x += Math.sin(d.w) * .3;
        if (d.y < -10) { motes.splice(m, 1); continue; }
        ctx.globalAlpha = .25 + Math.abs(Math.sin(d.t)) * .55;
        ctx.fillStyle = '#ffe3a0';
        ctx.beginPath(); ctx.arc(d.x, d.y, d.r, 0, 6.28); ctx.fill();
      }
      for (var i = bits.length - 1; i >= 0; i--) {
        var b = bits[i];
        b.sway += .04;
        if (b.ambient) {
          b.x += b.vx + Math.sin(b.sway) * .6; b.y += b.vy;
        } else {
          b.vy += .13; b.vx *= .985; b.vy *= .985;
          if (b.vy > 2.1) b.vy = 2.1;
          b.x += b.vx + (b.vy > 1 ? Math.sin(b.sway) * .9 : 0); b.y += b.vy;
          if (b.vy > 1.5) b.life -= .004;
        }
        b.rot += b.vr; b.flip += b.vf;
        if (b.y > H + 30 || b.life <= 0) { bits.splice(i, 1); continue; }
        var f = Math.cos(b.flip);
        ctx.save();
        ctx.translate(b.x, b.y); ctx.rotate(b.rot); ctx.scale(1, Math.max(.12, Math.abs(f)));
        ctx.globalAlpha = b.ambient ? .8 : Math.min(1, b.life * 1.6);
        ctx.fillStyle = f < 0 ? shade(b.c, .72) : b.c;
        petal(b.s);
        ctx.restore();
      }
      ctx.globalAlpha = 1;
      if (ambientOn || bits.length || motes.length) requestAnimationFrame(frame);
      else running = false;
    };
    var kick = function () { if (!running) { running = true; requestAnimationFrame(frame); } };
    dust.start = function () { ambientOn = true; kick(); };
    document.addEventListener('pointerdown', function (e) {
      if (!root.classList.contains('is-open') || e.target.closest('button, a, input, textarea, .km-lb')) return;
      dust.burst(e.clientX, e.clientY, 7);
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
      s.className = 'km-w'; s.textContent = p; s.style.setProperty('--w', Math.min(w++, 60));
      el.appendChild(s);
    });
  });

  /* ─────────── reveal on scroll: viewport position, checked every frame something moves
     (not IntersectionObserver: an element that starts fully clipped never counts as visible) ─────────── */
  var pending = [];
  var collect = function () {
    pending = Array.prototype.slice.call(root.querySelectorAll('.km-page .km-r'));
    if (reduce) { pending.forEach(onReveal); pending.forEach(function (el) { el.classList.add('is-in'); }); pending = []; }
  };
  var onReveal = function (el) {
    if (el.classList.contains('km-venue')) {
      var fr = el.querySelector('iframe[data-src]');
      if (fr && !fr.src) fr.src = fr.getAttribute('data-src');
    }
  };
  var revealTick = function (vh) {
    if (!pending.length) return;
    pending = pending.filter(function (el) {
      var r = el.getBoundingClientRect();
      var line = el.getAttribute('data-r') === 'sec' ? vh * .96 : vh * .9;
      if ((r.top < line && r.bottom > 0) || r.bottom <= 0) {
        el.classList.add('is-in');
        onReveal(el);
        return false;
      }
      return true;
    });
  };

  /* ─────────── scroll: parallax, swing with scroll speed, lines that draw as you go, the lotus blooming ─────────── */
  var hang = $('km-hang');
  var silks = Array.prototype.slice.call(root.querySelectorAll('.km-silk'));
  var heroMandala = $('km-hero-mandala');
  var heroImg = document.querySelector('#km-hero-photo img');
  var rites = $('km-rites'), riteItems = rites ? Array.prototype.slice.call(rites.querySelectorAll('.km-rite')) : [];
  var leaves = $('km-leaves');
  var bloom = $('km-bloom');
  var sideOrns = Array.prototype.slice.call(root.querySelectorAll('.km-side-orn'));
  var lastY = window.scrollY, velocity = 0, queued = false, swingTimer = null;
  var tick = function () {
    queued = false;
    var vh = window.innerHeight, y = window.scrollY;
    revealTick(vh);
    if (!reduce) {
      if (heroMandala && y < vh * 1.6) heroMandala.style.transform = 'rotate(' + (y * .05).toFixed(2) + 'deg) scale(' + (1 + y * .00025).toFixed(4) + ')';
      if (heroImg && y < vh * 1.6) heroImg.style.transform = 'translate3d(0,' + (y * .07).toFixed(1) + 'px,0) scale(1.12)';
      velocity = velocity * .6 + (y - lastY) * .4;
      var sw = clamp(velocity * .5, -14, 14);
      if (hang) hang.style.setProperty('--swing', sw.toFixed(1) + 'deg');
      silks.forEach(function (s, i) { s.style.setProperty('--sway', (sw * (i % 2 ? .8 : 1.1)).toFixed(1) + 'deg'); });
      clearTimeout(swingTimer);
      swingTimer = setTimeout(function () {
        if (hang) hang.style.setProperty('--swing', '0deg');
        silks.forEach(function (s) { s.style.setProperty('--sway', '0deg'); });
      }, 130);
    }
    lastY = y;
    if (!reduce && sideOrns.length && window.innerWidth >= 1000) sideOrns.forEach(function (o) {
      var r = o.parentNode.getBoundingClientRect();
      if (r.bottom > -100 && r.top < vh + 100) o.style.setProperty('--spin', ((r.top + r.height / 2 - vh / 2) * (o.classList.contains('r') ? -.06 : .06)).toFixed(1) + 'deg');
    });
    var mark = vh * .62;
    if (rites) {
      var rr = rites.getBoundingClientRect();
      rites.style.setProperty('--p', clamp((mark - rr.top) / rr.height, 0, 1).toFixed(3));
      riteItems.forEach(function (li) {
        var lr = li.getBoundingClientRect();
        var lit = lr.top + lr.height / 2 < mark;
        if (lit && !li.classList.contains('is-lit')) sfx('tap', .14);
        li.classList.toggle('is-lit', lit);
      });
    }
    if (leaves) {
      var lr2 = leaves.getBoundingClientRect();
      leaves.style.setProperty('--p', clamp((mark - lr2.top) / lr2.height, 0, 1).toFixed(3));
    }
    if (bloom && !bloom.classList.contains('is-full')) {
      var br = bloom.getBoundingClientRect();
      var p = clamp((vh - br.top) / (vh * .75), 0, 1);
      p = 1 - Math.pow(1 - p, 2);
      bloom.style.setProperty('--b', (.12 + .88 * p).toFixed(3));
    }
  };
  var queue = function () { if (!queued) { queued = true; requestAnimationFrame(tick); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue);
  window.addEventListener('load', queue);

  /* ─────────── cover: the gate opens ─────────── */
  var page = $('km-top');
  var heroSec = $('km-hero');
  var start = function () {
    root.classList.add('is-open');
    collect();
    tick();
    dust.start();
  };
  var land = function () {
    root.classList.add('is-landed');
    if (heroSec) heroSec.classList.add('is-text');
  };
  if (gate) {
    document.documentElement.classList.add('km-locked');
    window.scrollTo(0, 0);
    requestAnimationFrame(function () { requestAnimationFrame(function () { gate.classList.add('is-ready'); }); });
    var openBtn = $('km-open');
    var opened = false;
    var finish = function () {
      document.documentElement.classList.remove('km-locked');
      gate.classList.add('is-gone');
      setTimeout(function () { if (gate) { gate.remove(); gate = null; } queue(); }, 520);
    };
    openBtn.addEventListener('click', function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      sfx('open', .5);
      startSong(true);
      if (reduce) { start(); land(); finish(); return; }

      var facade = $('km-facade');
      var photo = $('km-cover-photo');
      var later = function (ms, fn) { setTimeout(fn, ms); };

      // 1 · a seam of light opens down the middle of the gate, rays behind it
      gate.classList.add('is-seam');

      // 2 · the carved gate splits into two doors that swing open towards us (the camera pushes through)
      later(420, function () {
        ['l', 'r'].forEach(function (side) {
          var door = facade.cloneNode(true);
          door.removeAttribute('id');
          door.querySelectorAll('[id]').forEach(function (el) { el.removeAttribute('id'); });
          var ph = door.querySelector('.km-cover-photo');
          if (ph) ph.remove();
          door.className = 'km-door ' + side;
          door.setAttribute('aria-hidden', 'true');
          door.inert = true;
          gate.insertBefore(door, facade);
        });
        facade.classList.add('is-split');
        void gate.offsetWidth;
        gate.classList.add('is-swing');
        sfx('send', .4);
        petals(28);
      });
      later(650, function () { gate.classList.add('is-flash'); sfx('chime', .32); });

      // 3 · the page opens behind the doors through a growing circle of light
      later(800, function () {
        page.style.clipPath = 'circle(0px at 50% ' + Math.round(window.innerHeight * .42) + 'px)';
        start();
        void page.offsetWidth;
        page.style.transition = 'clip-path 1.7s cubic-bezier(.65,0,.35,1)';
        page.style.clipPath = 'circle(' + Math.round(Math.hypot(window.innerWidth, window.innerHeight) * 1.1) + 'px at 50% ' + Math.round(window.innerHeight * .42) + 'px)';
        later(1800, function () { page.style.clipPath = ''; page.style.transition = ''; });
      });
      later(1100, function () { gate.classList.add('is-through'); });

      // 4 · the couple's photo flies from the gate into the hero arch (same arch, so it simply settles in)
      later(1150, function () {
        var hero = $('km-hero-photo');
        if (!photo || !hero) return;
        var a = photo.getBoundingClientRect(), b = hero.getBoundingClientRect();
        photo.style.transformOrigin = '0 0';
        photo.style.transition = 'transform 1.35s cubic-bezier(.7,0,.2,1), box-shadow 1.35s ease';
        photo.style.transform = 'translate(' + (b.left - a.left).toFixed(1) + 'px,' + (b.top - a.top).toFixed(1) + 'px) scale(' + (b.width / a.width).toFixed(4) + ')';
        photo.style.boxShadow = 'none';
      });
      later(1900, function () { if (heroSec) heroSec.classList.add('is-text'); });
      later(2520, function () {
        land();
        if (photo) photo.style.visibility = 'hidden';
        dust.burst(window.innerWidth / 2, window.innerHeight * .3, 26);
        finish();
      });
    });
    openBtn.focus({ preventScroll: true });
  } else {
    start();
    land();
  }

  // gold lotus petals floating out of the opening gate
  function petals(n) {
    var box = $('km-petals');
    if (!box) return;
    var html = '';
    for (var i = 0; i < n; i++) {
      var a = Math.random() * Math.PI * 2, d = 160 + Math.random() * 420;
      html += '<span style="--s:' + (12 + Math.random() * 16).toFixed(0) + 'px;--x:' + (Math.cos(a) * d).toFixed(0) + 'px;--y:' + (Math.sin(a) * d * .8 - 120).toFixed(0) + 'px;--r:' + ((Math.random() - .5) * 720).toFixed(0) + 'deg;--t:' + (1.8 + Math.random() * 1.4).toFixed(2) + 's;--d:' + (Math.random() * .5).toFixed(2) + 's">'
        + '<svg viewBox="-10 -30 20 32"><use href="#km-petal" fill="url(#km-gold)"/></svg></span>';
    }
    box.innerHTML = html;
  }

  /* ─────────── countdown in the kbach medallions ─────────── */
  var count = $('km-count');
  if (count) {
    var target = new Date(count.getAttribute('data-target')).getTime();
    var cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var set = function (u, v) {
      var txt = khDigits(String(v).padStart(2, '0'));
      if (cells[u].textContent === txt) return;
      cells[u].textContent = txt;
      if (!reduce) { cells[u].classList.remove('flip'); void cells[u].offsetWidth; cells[u].classList.add('flip'); }
    };
    var countTick = function () {
      var diff = Math.max(0, target - Date.now());
      if (!diff) {
        count.hidden = true;
        var done = $('km-count-done');
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

  /* ─────────── palm leaves: tap turns one over ─────────── */
  root.querySelectorAll('.km-leaf').forEach(function (leaf) {
    leaf.addEventListener('click', function () {
      leaf.classList.toggle('is-flipped');
      sfx('leaf', .4);
    });
  });

  /* ─────────── photo preview: zooms out of the tapped photo (FLIP), prev / next, swipe, keys ─────────── */
  var lb = $('km-lb');
  var phs = Array.prototype.slice.call(root.querySelectorAll('.km-ph'));
  if (lb && phs.length) {
    var lbImg = $('km-lb-img'), lbCap = $('km-lb-cap'), lbCount = $('km-lb-count');
    var prevBtn = $('km-lb-prev'), nextBtn = $('km-lb-next');
    var idx = 0, lastFocus = null, isOpen = false, busy = false;
    if (phs.length < 2) { prevBtn.hidden = true; nextBtn.hidden = true; }
    var natural = function (i) {
      var t = phs[i].querySelector('img');
      if (lbImg.complete && lbImg.naturalWidth && lbImg.getAttribute('src') === phs[i].getAttribute('data-full')) return [lbImg.naturalWidth, lbImg.naturalHeight];
      if (t && t.naturalWidth) return [t.naturalWidth, t.naturalHeight];
      return [3, 4];
    };
    var goal = function (i) {
      var n = natural(i), vw = window.innerWidth, vh = window.innerHeight;
      var maxW = vw - (vw < 700 ? 20 : 180), maxH = vh - (vw < 700 ? 150 : 140);
      var s = Math.min(maxW / n[0], maxH / n[1]);
      var w = n[0] * s, h = n[1] * s;
      return { x: (vw - w) / 2, y: (vh - h) / 2, w: w, h: h };
    };
    var place = function (f) {
      lbImg.style.left = f.x + 'px'; lbImg.style.top = f.y + 'px';
      lbImg.style.width = f.w + 'px'; lbImg.style.height = f.h + 'px';
    };
    // transform + clip that make the big image sit exactly on the thumbnail (cropped like object-fit: cover)
    var fromThumb = function (f, t) {
      var k = Math.max(t.width / f.w, t.height / f.h);
      var ix = (f.w - t.width / k) / 2, iy = (f.h - t.height / k) / 2;
      return {
        transform: 'translate(' + (t.left - f.x - ix * k).toFixed(1) + 'px,' + (t.top - f.y - iy * k).toFixed(1) + 'px) scale(' + k.toFixed(4) + ')',
        clip: 'inset(' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px ' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px)'
      };
    };
    var fill = function (i) {
      idx = (i + phs.length) % phs.length;
      var b = phs[idx];
      lbImg.src = b.getAttribute('data-full');
      lbImg.alt = b.getAttribute('data-cap') || '';
      lbCap.textContent = b.getAttribute('data-cap') || '';
      lbCount.textContent = khDigits(idx + 1) + ' / ' + khDigits(phs.length);
    };
    lbImg.addEventListener('load', function () { if (isOpen && !busy) place(goal(idx)); });
    var open = function (i) {
      lastFocus = phs[i];
      fill(i);
      lb.hidden = false;
      isOpen = true;
      var f = goal(idx);
      place(f);
      var t = phs[idx].getBoundingClientRect();
      lbImg.classList.remove('anim', 'swap');
      if (!reduce) {
        var st = fromThumb(f, t);
        lbImg.style.transform = st.transform;
        lbImg.style.clipPath = st.clip;
      }
      lbImg.style.opacity = '1';
      void lbImg.offsetWidth;
      busy = true;
      lbImg.classList.add('anim');
      lbImg.style.transform = 'none';
      lbImg.style.clipPath = 'inset(0px)';
      lb.classList.add('show');
      setTimeout(function () { busy = false; lbImg.classList.remove('anim'); }, 650);
      $('km-lb-x').focus({ preventScroll: true });
      sfx('card', .35);
    };
    var close = function () {
      if (!isOpen) return;
      isOpen = false;
      var t = phs[idx].getBoundingClientRect();
      var onScreen = t.bottom > 0 && t.top < window.innerHeight && t.width > 0;
      lbImg.classList.remove('swap');
      lbImg.classList.add('anim');
      if (onScreen && !reduce) {
        var st = fromThumb(goal(idx), t);
        lbImg.style.transform = st.transform;
        lbImg.style.clipPath = st.clip;
      } else {
        lbImg.style.opacity = '0';
        lbImg.style.transform = 'scale(.92)';
      }
      lb.classList.remove('show');
      setTimeout(function () {
        lb.hidden = true;
        lbImg.classList.remove('anim');
        lbImg.style.transform = ''; lbImg.style.clipPath = ''; lbImg.style.opacity = '';
      }, 640);
      if (lastFocus) lastFocus.focus({ preventScroll: true });
      sfx('tap', .25);
    };
    var go = function (dir) {
      if (phs.length < 2 || !isOpen) return;
      fill(idx + dir);
      place(goal(idx));
      lastFocus = phs[idx];
      if (!reduce) {
        lbImg.style.setProperty('--from', dir * 70 + 'px');
        lbImg.style.setProperty('--rot', dir * -12 + 'deg');
        lbImg.classList.remove('swap'); void lbImg.offsetWidth; lbImg.classList.add('swap');
      }
      sfx('card', .3);
    };
    phs.forEach(function (b, i) { b.addEventListener('click', function () { open(i); }); });
    prevBtn.addEventListener('click', function () { go(-1); });
    nextBtn.addEventListener('click', function () { go(1); });
    $('km-lb-x').addEventListener('click', close);
    lb.addEventListener('click', function (e) { if (e.target.classList.contains('km-lb-stage') || e.target.classList.contains('km-lb-bg')) close(); });
    document.addEventListener('keydown', function (e) {
      if (!isOpen) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') go(-1);
      else if (e.key === 'ArrowRight') go(1);
      else if (e.key === 'Tab') { // keep focus inside the preview
        var f = Array.prototype.slice.call(lb.querySelectorAll('button:not([hidden])'));
        var at = f.indexOf(document.activeElement);
        e.preventDefault();
        f[(at + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
      }
    });
    // swipe (touch + mouse drag): the photo follows the finger, past 50px it changes
    var sx = null, sy = 0, dx = 0;
    lb.addEventListener('pointerdown', function (e) {
      if (e.target.closest('.km-lb-btn')) return;
      sx = e.clientX; sy = e.clientY; dx = 0;
    });
    lb.addEventListener('pointermove', function (e) {
      if (sx === null || busy) return;
      dx = e.clientX - sx;
      if (Math.abs(dx) > 8 && !reduce) lbImg.style.transform = 'translateX(' + dx * .6 + 'px) rotate(' + dx * .012 + 'deg)';
    });
    var endSwipe = function (e) {
      if (sx === null) return;
      var moved = Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(e.clientY - sy);
      sx = null;
      lbImg.style.transform = isOpen ? 'none' : lbImg.style.transform;
      if (moved) { e.preventDefault(); go(dx < 0 ? 1 : -1); }
      dx = 0;
    };
    lb.addEventListener('pointerup', endSwipe);
    lb.addEventListener('pointercancel', function () { sx = null; dx = 0; if (isOpen) lbImg.style.transform = 'none'; });
    window.addEventListener('resize', function () { if (isOpen) place(goal(idx)); });
  }

  /* ─────────── dress code silks: a tap ripples the cloth ─────────── */
  silks.forEach(function (s) {
    s.addEventListener('click', function () {
      s.classList.remove('ripple'); void s.offsetWidth; s.classList.add('ripple');
      sfx('tap', .3);
      var r = s.getBoundingClientRect();
      dust.burst(r.left + r.width / 2, r.top + r.height / 2, 8, getComputedStyle(s.parentNode).getPropertyValue('--c').trim());
    });
  });

  /* ─────────── RSVP → sealed in a gold envelope ─────────── */
  var opts = $('km-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var card = $('km-rsvp-card');
    var form = $('km-rsvp-form'), note = $('km-rsvp-note');
    var err = $('km-rsvp-err');
    var env = $('km-env');
    var done = $('km-rsvp-done');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.km-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.km-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); });
      form.classList.add('show'); err.textContent = '';
      sfx('tap', .35);
      if (chosen === 'yes') { var r = b.getBoundingClientRect(); dust.burst(r.left + r.width / 2, r.top, 10); }
    });
    var seal = function () {
      $('km-rsvp-done-title').textContent = chosen === 'yes' ? 'See you there' : 'Thank you';
      $('km-rsvp-done-text').textContent = chosen === 'yes'
        ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ និងកិត្តិយសបំផុត។'
        : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ។';
      if (reduce) { card.hidden = true; done.hidden = false; return; }
      var steps = [
        [0, function () { card.classList.add('is-sealing'); env.className = 'km-env is-in'; }],
        [650, function () { env.classList.add('is-closed'); sfx('send', .4); }],
        [1150, function () { env.classList.add('is-stamped'); sfx('tap', .4); var r = env.getBoundingClientRect(); dust.burst(r.left + r.width / 2, r.top + r.height / 2, 16); }],
        [1750, function () { env.classList.add('is-fly'); sfx('chime', .3); }],
        [2600, function () {
          card.hidden = true; card.classList.remove('is-sealing');
          env.className = 'km-env';
          done.hidden = false;
          var r = done.getBoundingClientRect();
          dust.burst(r.left + r.width / 2, r.top + 40, 30);
        }],
      ];
      steps.forEach(function (s) { setTimeout(s[1], s[0]); });
    };
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var finish = function (ok) {
        btn.disabled = false;
        if (!ok) { err.textContent = 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'; return; }
        seal();
      };
      if (!rsvpUrl) { finish(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { finish(r.ok); }).catch(function () { finish(false); });
    });
    $('km-rsvp-again').addEventListener('click', function () {
      done.hidden = true; card.hidden = false;
      sfx('tap', .3);
    });
  }

  /* ─────────── gift: the envelope opens, the QR card rises out ─────────── */
  var giftBtn = $('km-gift-open');
  if (giftBtn) {
    giftBtn.addEventListener('click', function () {
      var gift = $('km-gift');
      if (gift.classList.contains('is-open')) return;
      gift.classList.add('is-open');
      giftBtn.setAttribute('aria-label', 'ស្រោមសំបុត្របានបើក');
      sfx('send', .4);
      setTimeout(function () { sfx('open', .35); var r = gift.getBoundingClientRect(); dust.burst(r.left + r.width / 2, r.top + r.height * .35, 30); }, 600);
      setTimeout(function () { $('km-gift-btns').classList.add('is-on'); }, 1200);
    });
  }

  /* ─────────── copy the account number ─────────── */
  var toast = $('km-toast'), toastT = null;
  var say = function (m) { toast.textContent = m; toast.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(function () { toast.classList.remove('show'); }, 2200); };
  var copyBtn = $('km-copy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var v = copyBtn.getAttribute('data-copy');
      var ok = function () { say('បានចម្លងលេខគណនី ✓'); sfx('tap', .35); var r = copyBtn.getBoundingClientRect(); dust.burst(r.left + r.width / 2, r.top, 10); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(v).then(ok, function () { say(v); });
      else say(v);
    });
  }

  /* ─────────── finale: tap the lotus → full bloom; throw gold petals ─────────── */
  if (bloom) {
    bloom.addEventListener('click', function () {
      bloom.classList.add('is-full');
      bloom.style.setProperty('--b', '1');
      sfx('chime', .32);
      var r = bloom.getBoundingClientRect();
      dust.burst(r.left + r.width / 2, r.top + r.height * .4, 34);
    });
  }
  var throwBtn = $('km-throw');
  if (throwBtn) {
    throwBtn.addEventListener('click', function () {
      dust.rain(70);
      sfx('chime', .3);
    });
  }
})();

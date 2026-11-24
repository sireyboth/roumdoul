/* Paper Bloom — resources/views/invitations/templates/wedding/paper-bloom.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.pb');
  if (!root) return;
  root.classList.add('js');

  var config = {};
  try { config = JSON.parse(document.getElementById('pb-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
  var css = getComputedStyle(root);
  var accent = css.getPropertyValue('--pb-accent').trim() || '#e58f8f';
  var gate = document.getElementById('pb-gate');

  /* ─────────── sound: effects + music, one mute button (effects only after the first tap) ─────────── */
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
  var audio = document.getElementById('pb-audio');
  var soundBtn = document.getElementById('pb-sound');
  var nowPlaying = document.getElementById('pb-now');
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
    if (e && e.target && e.target.closest && e.target.closest('#pb-sound')) return;
    if (!gate) startSong(true); // with the cover, the bow starts the song
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });
  root.addEventListener('click', function (e) { if (e.target.closest('[data-sfx="tap"]')) sfx('tap', .3); });

  /* ─────────── paper confetti: hearts and petals that flip in 3D as they fall ─────────── */
  var confetti = { burst: function () {}, rain: function () {} };
  var canvas = document.getElementById('pb-confetti');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var bits = [];
    var tones = [accent, '#f6dca6', '#ffffff', '#f4b8b8', '#b9cda7', css.getPropertyValue('--pb-deep').trim() || '#b05b55'];
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);
    var shade = function (hex, k) { // darker back side of the paper
      var m = /^#?([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i.exec(hex);
      if (!m) return hex;
      return 'rgb(' + [1, 2, 3].map(function (i) { return Math.round(parseInt(m[i], 16) * k); }).join(',') + ')';
    };
    var make = function (x, y, vx, vy, kind, color) {
      return { x: x, y: y, vx: vx, vy: vy, kind: kind, c: color || tones[(Math.random() * tones.length) | 0], s: kind === 'heart' ? 7 + Math.random() * 6 : 5 + Math.random() * 5,
        rot: Math.random() * 6.28, vr: (Math.random() - .5) * .2, flip: Math.random() * 6.28, vf: .08 + Math.random() * .12, sway: Math.random() * 6.28, life: 1, ambient: false };
    };
    var heartPath = function (r) {
      ctx.beginPath();
      ctx.moveTo(0, r * .4);
      ctx.bezierCurveTo(-r * 1.1, -r * .3, -r * .55, -r * 1.05, 0, -r * .45);
      ctx.bezierCurveTo(r * .55, -r * 1.05, r * 1.1, -r * .3, 0, r * .4);
      ctx.fill();
    };
    var petalPath = function (r) {
      ctx.beginPath();
      ctx.moveTo(0, -r);
      ctx.bezierCurveTo(r * .9, -r * .4, r * .6, r * .7, 0, r);
      ctx.bezierCurveTo(-r * .6, r * .7, -r * .9, -r * .4, 0, -r);
      ctx.fill();
    };
    confetti.burst = function (x, y, n, onlyHearts) {
      for (var i = 0; i < n; i++) {
        var a = -Math.PI / 2 + (Math.random() - .5) * 2.6, sp = 3 + Math.random() * 5;
        bits.push(make(x, y, Math.cos(a) * sp, Math.sin(a) * sp, onlyHearts || Math.random() < .55 ? 'heart' : 'petal'));
      }
    };
    // petals thrown up from both bottom corners (the finale button)
    confetti.rain = function (n) {
      for (var i = 0; i < n; i++) {
        var left = i % 2 === 0;
        bits.push(make(left ? -10 : W + 10, H * .85, (left ? 1 : -1) * (4 + Math.random() * 6), -(9 + Math.random() * 7), Math.random() < .5 ? 'heart' : 'petal'));
      }
    };
    // a few petals always drifting down, once the invitation is open
    var ambientOn = false;
    var addAmbient = function () {
      var b = make(Math.random() * W, -20, (Math.random() - .5) * .4, .5 + Math.random() * .6, 'petal', [accent, '#f4b8b8', '#ffffff'][(Math.random() * 3) | 0]);
      b.ambient = true;
      bits.push(b);
    };
    var frame = function () {
      ctx.clearRect(0, 0, W, H);
      if (ambientOn && bits.filter(function (b) { return b.ambient; }).length < 9 && Math.random() < .02) addAmbient();
      for (var i = bits.length - 1; i >= 0; i--) {
        var b = bits[i];
        b.sway += .04;
        if (b.ambient) {
          b.x += b.vx + Math.sin(b.sway) * .6; b.y += b.vy;
        } else {
          b.vy += .14; b.vx *= .985; b.vy *= .985;
          if (b.vy > 2.2) b.vy = 2.2;
          b.x += b.vx + (b.vy > 1 ? Math.sin(b.sway) * .9 : 0); b.y += b.vy;
          if (b.vy > 1.5) b.life -= .004;
        }
        b.rot += b.vr; b.flip += b.vf;
        if (b.y > H + 30 || b.life <= 0) { bits.splice(i, 1); continue; }
        var f = Math.cos(b.flip);
        ctx.save();
        ctx.translate(b.x, b.y); ctx.rotate(b.rot); ctx.scale(1, Math.max(.12, Math.abs(f)));
        ctx.globalAlpha = b.ambient ? .75 : Math.min(1, b.life * 1.6);
        ctx.fillStyle = f < 0 ? shade(b.c, .82) : b.c;
        if (b.kind === 'heart') heartPath(b.s); else petalPath(b.s);
        ctx.restore();
      }
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
    confetti.start = function () { ambientOn = true; };
    // a tap on an empty spot: a little puff of paper hearts
    document.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button, a, input, textarea, .pb-lb, .pb-deck, .pb-line')) return;
      confetti.burst(e.clientX, e.clientY, 8, true);
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
      s.className = 'pb-w'; s.textContent = p; s.style.setProperty('--w', Math.min(w++, 60));
      el.appendChild(s);
    });
  });

  /* ─────────── reveal on scroll (viewport position, checked every frame something moves) ─────────── */
  var pending = [];
  var collect = function () {
    pending = Array.prototype.slice.call(root.querySelectorAll('.pb-page .pb-r'));
    // siblings that reveal together come in one after another
    pending.forEach(function (el) {
      if (el.style.getPropertyValue('--stagger')) return;
      var sibs = Array.prototype.filter.call(el.parentNode.children, function (c) { return c.classList.contains('pb-r'); });
      el.style.setProperty('--stagger', Math.min(sibs.indexOf(el), 8));
    });
    if (reduce) { pending.forEach(function (el) { el.classList.add('is-in'); }); pending = []; }
  };
  var revealTick = function (vh) {
    if (!pending.length) return;
    pending = pending.filter(function (el) {
      var r = el.getBoundingClientRect();
      if ((r.top < vh * .88 && r.bottom > 0) || r.bottom <= 0) {
        el.classList.add('is-in');
        if (el.classList.contains('pb-cal')) setTimeout(function () { sfx('paper', .25); }, 900);
        return false;
      }
      return true;
    });
  };

  /* ─────────── scroll physics: the name tag and the polaroids swing with scroll speed ─────────── */
  var tag = document.getElementById('pb-tag');
  var line = document.getElementById('pb-line');
  var polaroids = line ? Array.prototype.slice.call(line.querySelectorAll('.pb-polaroid')) : [];
  var lastY = window.scrollY, velocity = 0, queued = false, swingTimer = null;
  var speedEls = Array.prototype.slice.call(root.querySelectorAll('.pb-deco[data-speed]'));
  var tick = function () {
    queued = false;
    var vh = window.innerHeight;
    revealTick(vh);
    var y = window.scrollY;
    if (!reduce) {
      speedEls.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        el.style.transform = 'translate3d(0,' + ((r.top + r.height / 2 - vh / 2) * -parseFloat(el.getAttribute('data-speed'))).toFixed(1) + 'px,0)';
      });
    }
    velocity = velocity * .6 + (y - lastY) * .4;
    lastY = y;
    if (!reduce) {
      var sw = Math.max(-18, Math.min(18, velocity * .6));
      if (tag) tag.style.setProperty('--swing', sw.toFixed(1) + 'deg');
      clearTimeout(swingTimer);
      swingTimer = setTimeout(function () { if (tag) tag.style.setProperty('--swing', '0deg'); }, 120);
    }
  };
  var queue = function () { if (!queued) { queued = true; requestAnimationFrame(tick); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue);
  window.addEventListener('load', queue);

  /* ─────────── depth: the cover sheets (and the hero arch) shift with tilt / pointer ─────────── */
  var depthEls = Array.prototype.slice.call(root.querySelectorAll('[data-depth]'));
  var tx = 0, ty = 0, cx = 0, cy = 0, depthRunning = false;
  var depthLoop = function () {
    cx += (tx - cx) * .08; cy += (ty - cy) * .08;
    depthEls.forEach(function (el) {
      var d = parseFloat(el.getAttribute('data-depth'));
      el.style.transform = 'translate3d(' + (cx * d).toFixed(2) + 'px,' + (cy * d).toFixed(2) + 'px,0)';
    });
    if (Math.abs(tx - cx) > .001 || Math.abs(ty - cy) > .001 || !gateDone) requestAnimationFrame(depthLoop);
    else depthRunning = false;
  };
  var kick = function () { if (!depthRunning) { depthRunning = true; requestAnimationFrame(depthLoop); } };
  var gateDone = false;
  if (!reduce) {
    window.addEventListener('pointermove', function (e) {
      tx = (e.clientX / window.innerWidth - .5) * -1.4;
      ty = (e.clientY / window.innerHeight - .5) * -1.4;
      kick();
    }, { passive: true });
    var onTilt = function (e) {
      if (e.gamma == null) return;
      tx = Math.max(-1, Math.min(1, e.gamma / 25)) * -1.2;
      ty = Math.max(-1, Math.min(1, (e.beta - 45) / 25)) * -1.2;
      kick();
    };
    // Android gives tilt right away; iOS asks permission (asked on the bow tap)
    if (window.DeviceOrientationEvent && typeof DeviceOrientationEvent.requestPermission !== 'function') {
      window.addEventListener('deviceorientation', onTilt, { passive: true });
    }
    // with nothing moving, the cover drifts gently by itself
    var t0 = performance.now();
    var drift = function (now) {
      if (gateDone) return;
      if (Math.abs(tx) < .02 && Math.abs(ty) < .02 || drift.idle) {
        drift.idle = true;
        tx = Math.sin((now - t0) / 1600) * .5; ty = Math.cos((now - t0) / 2100) * .35;
        kick();
      }
      requestAnimationFrame(drift);
    };
    window.addEventListener('pointermove', function () { drift.idle = false; }, { once: true, passive: true });
    requestAnimationFrame(drift);
  }

  /* ─────────── cover: tap the bow → it unties → the camera flies through the paper ─────────── */
  var start = function () {
    root.classList.add('is-open');
    document.documentElement.classList.remove('pb-locked');
    collect();
    tick();
    if (confetti.start) confetti.start();
  };
  // the cover photo leaves the cover and lands in the hero arch: one continuous shot. A fixed clone is flown
  // from the photo's place on the cover, up toward the camera, then down into the hero photo's exact box.
  var flyPhoto = function (done) {
    var from = gate.querySelector('.pb-photo'), toEl = document.getElementById('pb-hero-photo');
    var img = from && from.querySelector('img');
    if (!img || !toEl || !document.body.animate) { done(); return; }
    var a = from.getBoundingClientRect(), b = toEl.getBoundingClientRect();
    var clone = document.createElement('div');
    clone.className = 'pb-fly-photo';
    clone.innerHTML = '<img alt="" src="' + img.currentSrc.replace(/"/g, '&quot;') + '">';
    document.body.appendChild(clone);
    from.style.visibility = 'hidden'; // (opacity is held by its entrance animation)
    var box = function (r, k, rot, rad) {
      var w = r.width * k, h = r.height * k;
      return { left: (r.left + r.width / 2 - w / 2) + 'px', top: (r.top + r.height / 2 - h / 2) + 'px', width: w + 'px', height: h + 'px', transform: 'rotate(' + rot + 'deg)', borderRadius: rad };
    };
    var mid = { left: a.left, top: a.top - window.innerHeight * .04, width: a.width, height: a.height };
    clone.animate([
      box(a, 1, 0, '50% 50% 0 0 / 30% 30% 0 0'),
      Object.assign(box(mid, 1.16, -2.5, '50% 50% 18px 18px / 30% 30% 18px 18px'), { offset: .4 }),
      box(b, 1, 0, '50% 50% 4px 4px / 35% 35% 4px 4px'),
    ], { duration: 1650, easing: 'cubic-bezier(.6,0,.2,1)', fill: 'forwards' }).finished.then(function () {
      done();
      clone.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 260, fill: 'forwards' }).finished.then(function () { clone.remove(); });
    }, function () { clone.remove(); done(); });
  };
  if (gate) {
    document.documentElement.classList.add('pb-locked');
    var bow = document.getElementById('pb-open');
    var opened = false;
    bow.addEventListener('click', function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      if (!reduce && window.DeviceOrientationEvent && typeof DeviceOrientationEvent.requestPermission === 'function') {
        DeviceOrientationEvent.requestPermission().then(function (s) { if (s === 'granted') window.addEventListener('deviceorientation', onTilt, { passive: true }); }).catch(function () {});
      }
      sfx('paper', .5);
      startSong(true);
      bow.classList.add('is-untied');
      if (reduce) {
        gate.classList.add('is-gone'); start(); gateDone = true;
        setTimeout(function () { gate.remove(); gate = null; }, 450);
        return;
      }
      // 1 · the bow unties and the ribbon falls
      gate.classList.add('is-untie');
      var r = bow.getBoundingClientRect();
      root.style.setProperty('--ix', (r.left + r.width / 2) + 'px');
      root.style.setProperty('--iy', (r.top + r.height / 2) + 'px');
      confetti.burst(r.left + r.width / 2, r.top + r.height / 2, 22, true);
      // the hero waits for the photo to land
      root.classList.add('pb-hero-wait', 'is-intro');
      [
        // 2 · the paper sheets peel past the camera, tilting and blurring; the photo lifts off toward the hero
        [750, function () {
          sfx('open', .45); gate.classList.add('is-fly');
          gateDone = true; tx = ty = 0;
          flyPhoto(function () {
            // 4 · it lands: the paper frame wraps round it, a little celebration
            root.classList.remove('pb-hero-wait');
            sfx('chime', .3);
            var h = document.getElementById('pb-hero-photo').getBoundingClientRect();
            confetti.burst(h.left + h.width / 2, h.top + h.height * .3, 30);
          });
        }],
        // 3 · the page opens through a circle that grows from the bow
        [950, function () { root.classList.add('is-iris'); start(); }],
        [2500, function () { root.classList.remove('is-iris'); gate.classList.add('is-gone'); }],
        [3100, function () { gate.remove(); gate = null; depthEls = Array.prototype.slice.call(root.querySelectorAll('[data-depth]')); gateDone = true; }],
        [6000, function () { root.classList.remove('is-intro'); }],
      ].forEach(function (s) { setTimeout(s[1], s[0]); });
    });
    bow.focus({ preventScroll: true });
  } else {
    gateDone = true;
    start();
  }

  /* ─────────── countdown: paper flip cards ─────────── */
  var count = document.getElementById('pb-count');
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
      if (!diff) {
        count.hidden = true;
        var done = document.getElementById('pb-count-done');
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

  /* ─────────── clothesline: the string spans the whole track, cards sway as it scrolls, tap flips ─────────── */
  if (line) {
    var track = line.querySelector('.pb-line-track');
    var sizeString = function () { line.style.setProperty('--track-w', track.scrollWidth + 'px'); };
    sizeString();
    window.addEventListener('resize', sizeString);
    var lastX = line.scrollLeft, swayT = null;
    line.addEventListener('scroll', function () {
      var v = line.scrollLeft - lastX; lastX = line.scrollLeft;
      if (reduce) return;
      var deg = Math.max(-14, Math.min(14, -v * .8));
      polaroids.forEach(function (p) { p.style.setProperty('--sway', deg + 'deg'); });
      clearTimeout(swayT);
      swayT = setTimeout(function () { polaroids.forEach(function (p) { p.style.setProperty('--sway', '0deg'); }); }, 90);
    }, { passive: true });
    polaroids.forEach(function (p) {
      p.addEventListener('click', function () {
        p.classList.toggle('is-flipped');
        sfx('card', .35);
      });
    });
  }

  /* ─────────── photo preview ───────────
     Every photo button carries data-group + data-g (its index in that group). Opening zooms the photo out of
     the button it was tapped on (FLIP), prev/next slide with a little paper tilt, a finger drags the photo
     (release past 70px = next/prev), ←/→/Esc work, and closing flies it back to its place if that's on screen. */
  var lb = document.getElementById('pb-lb');
  var lbFig = document.getElementById('pb-lb-fig'), lbImg = document.getElementById('pb-lb-img');
  var lbCap = document.getElementById('pb-lb-cap'), lbCount = document.getElementById('pb-lb-count');
  var lbPrev = document.getElementById('pb-lb-prev'), lbNext = document.getElementById('pb-lb-next');
  var groups = {};
  root.querySelectorAll('.pb-ph[data-group]').forEach(function (b) {
    var g = b.getAttribute('data-group'), i = +b.getAttribute('data-g');
    groups[g] = groups[g] || [];
    if (!groups[g][i]) groups[g][i] = { src: b.getAttribute('data-full'), cap: b.getAttribute('data-cap') || '' };
  });
  var lbGroup = null, lbIdx = 0, lastFocus = null, lbBusy = false;
  var ease = 'cubic-bezier(.2,.85,.25,1)';
  var visible = function (el) {
    if (!el) return false;
    var r = el.getBoundingClientRect();
    return r.width > 0 && r.bottom > 0 && r.top < window.innerHeight && getComputedStyle(el).visibility !== 'hidden';
  };
  // the button showing photo i of the group that is on screen right now (deck top card or thumbnail)
  var originOf = function (g, i) {
    var list = root.querySelectorAll('.pb-ph[data-group="' + g + '"][data-g="' + i + '"]');
    for (var k = 0; k < list.length; k++) if (visible(list[k])) return list[k];
    return null;
  };
  var fill = function () {
    var item = groups[lbGroup][lbIdx], n = groups[lbGroup].length;
    lbImg.src = item.src;
    lbImg.alt = item.cap;
    lbCap.textContent = item.cap;
    lbCap.classList.toggle('pb-kh', /[ក-៿]/.test(item.cap));
    lbCap.hidden = !item.cap;
    lbCount.textContent = n > 1 ? khDigits(lbIdx + 1) + ' / ' + khDigits(n) : '';
    lbPrev.hidden = lbNext.hidden = n < 2;
    [lbIdx - 1, lbIdx + 1].forEach(function (j) { var it = groups[lbGroup][(j + n) % n]; if (it) { var im = new Image(); im.src = it.src; } });
  };
  var decoded = function () {
    return Promise.race([lbImg.decode ? lbImg.decode().catch(function () {}) : Promise.resolve(), new Promise(function (r) { setTimeout(r, 400); })]);
  };
  // transform that puts the figure exactly over a rect (uniform scale, centred)
  var flipFrom = function (rect) {
    var f = lbFig.getBoundingClientRect();
    var sc = Math.max(.05, Math.min(rect.width / f.width, rect.height / f.height));
    var dx = rect.left + rect.width / 2 - (f.left + f.width / 2);
    var dy = rect.top + rect.height / 2 - (f.top + f.height / 2);
    return 'translate(' + dx + 'px,' + dy + 'px) scale(' + sc + ')';
  };
  var openLb = function (btn) {
    if (lbBusy) return;
    lbGroup = btn.getAttribute('data-group') || 'single';
    if (!groups[lbGroup]) groups[lbGroup] = [{ src: btn.getAttribute('data-full'), cap: btn.getAttribute('data-cap') || '' }];
    lbIdx = +btn.getAttribute('data-g') || 0;
    lastFocus = btn;
    fill();
    lb.hidden = false;
    lb.classList.remove('show');
    sfx('card', .35);
    lbBusy = true;
    decoded().then(function () {
      lb.classList.add('show');
      if (reduce || !lbFig.animate) { lbBusy = false; return; }
      var from = flipFrom(btn.getBoundingClientRect());
      lbFig.animate([{ transform: from, opacity: .6 }, { transform: 'none', opacity: 1 }], { duration: 560, easing: ease })
        .finished.then(function () { lbBusy = false; }, function () { lbBusy = false; });
    });
    document.getElementById('pb-lb-x').focus({ preventScroll: true });
  };
  var closeLb = function () {
    if (lb.hidden || lbBusy) return;
    var origin = originOf(lbGroup, lbIdx);
    lb.classList.remove('show');
    sfx('tap', .25);
    var done = function () { lb.hidden = true; lbFig.style.transform = ''; if (lastFocus) lastFocus.focus({ preventScroll: true }); };
    if (reduce || !lbFig.animate) { setTimeout(done, 250); return; }
    var to = origin ? flipFrom(origin.getBoundingClientRect()) : 'scale(.85)';
    lbBusy = true;
    lbFig.animate([{ transform: 'none', opacity: 1 }, { transform: to, opacity: origin ? .9 : 0 }], { duration: origin ? 460 : 300, easing: 'cubic-bezier(.5,0,.2,1)', fill: 'forwards' })
      .finished.then(function (a) { lbBusy = false; done(); a.cancel && a.cancel(); }, function () { lbBusy = false; done(); });
  };
  var go = function (dir, fromDrag) {
    var n = groups[lbGroup].length;
    if (n < 2 || lbBusy) return;
    lbBusy = true;
    sfx('card', .3);
    var out = reduce || !lbFig.animate ? null : lbFig.animate(
      [{ transform: fromDrag || 'none', opacity: 1 }, { transform: 'translateX(' + (-dir * 70) + '%) rotate(' + (-dir * 8) + 'deg)', opacity: 0 }],
      { duration: 240, easing: 'cubic-bezier(.5,0,.8,.4)', fill: 'forwards' });
    var swap = function () {
      lbIdx = (lbIdx + dir + n) % n;
      fill();
      decoded().then(function () {
        if (out) out.cancel();
        if (reduce || !lbFig.animate) { lbBusy = false; return; }
        lbFig.animate([{ transform: 'translateX(' + (dir * 70) + '%) rotate(' + (dir * 8) + 'deg)', opacity: 0 }, { transform: 'none', opacity: 1 }],
          { duration: 460, easing: 'cubic-bezier(.2,1.2,.35,1)' }).finished.then(function () { lbBusy = false; }, function () { lbBusy = false; });
      });
    };
    if (out) out.finished.then(swap, swap); else swap();
  };
  lbPrev.addEventListener('click', function () { go(-1); });
  lbNext.addEventListener('click', function () { go(1); });
  document.getElementById('pb-lb-x').addEventListener('click', closeLb);
  document.getElementById('pb-lb-bg').addEventListener('click', closeLb);
  document.addEventListener('keydown', function (e) {
    if (lb.hidden) return;
    if (e.key === 'Escape') closeLb();
    else if (e.key === 'ArrowLeft') go(-1);
    else if (e.key === 'ArrowRight') go(1);
  });
  // drag the photo with a finger / mouse
  var lx = null, ldx = 0;
  lbFig.addEventListener('pointerdown', function (e) { if (lbBusy) return; lx = e.clientX; ldx = 0; lbFig.setPointerCapture(e.pointerId); });
  lbFig.addEventListener('pointermove', function (e) {
    if (lx === null) return;
    ldx = e.clientX - lx;
    lbFig.style.transform = 'translateX(' + ldx + 'px) rotate(' + ldx * .03 + 'deg)';
  });
  var lbUp = function () {
    if (lx === null) return;
    lx = null;
    var t = lbFig.style.transform;
    lbFig.style.transform = '';
    if (Math.abs(ldx) > 70 && groups[lbGroup].length > 1) go(ldx < 0 ? 1 : -1, t);
    else if (Math.abs(ldx) > 4 && lbFig.animate) lbFig.animate([{ transform: t }, { transform: 'none' }], { duration: 380, easing: 'cubic-bezier(.2,1.3,.4,1)' });
  };
  lbFig.addEventListener('pointerup', lbUp);
  lbFig.addEventListener('pointercancel', lbUp);
  root.querySelectorAll('.pb-ph').forEach(function (b) {
    if (b.closest('.pb-deck')) return; // the deck handles its own taps
    b.addEventListener('click', function () { openLb(b); });
  });

  /* ─────────── swipe deck: drag the top photo away, it goes to the back ─────────── */
  var deck = document.getElementById('pb-deck');
  if (deck) {
    var cards = Array.prototype.slice.call(deck.querySelectorAll('.pb-deck-card'));
    var total = cards.length, shown = 0;
    var counter = document.getElementById('pb-deck-count');
    var tilts = [-3, 2.5, -1.5, 3.5, -2.5, 1.5];
    var layout = function () {
      cards.forEach(function (c, k) {
        c.style.setProperty('--k', Math.min(k, 3));
        c.style.setProperty('--r', k === 0 ? 0 : tilts[(+c.getAttribute('data-i')) % tilts.length]);
        c.style.zIndex = total - k;
        c.style.opacity = k > 3 ? 0 : 1;
        var btn = c.querySelector('.pb-ph');
        btn.tabIndex = k === 0 ? 0 : -1;
      });
      if (counter) counter.textContent = khDigits(((+cards[0].getAttribute('data-i')) + 1)) + ' / ' + khDigits(total);
    };
    layout();
    var throwCard = function (dir) {
      if (total < 2) return;
      var c = cards.shift();
      c.classList.remove('is-drag');
      c.classList.add('is-away');
      c.style.transform = 'translate(' + dir * 130 + '%, -10%) rotate(' + dir * 28 + 'deg)';
      sfx('card', .35);
      cards.push(c);
      layout();
      c.style.opacity = 0;
      setTimeout(function () { c.classList.remove('is-away'); c.style.transform = ''; layout(); }, 520);
      shown++;
    };
    var back = function () {
      if (total < 2) return;
      var c = cards.pop();
      cards.unshift(c);
      c.style.transform = 'translate(-130%, -10%) rotate(-28deg)';
      c.style.opacity = 0;
      c.classList.add('is-drag');
      void c.offsetWidth;
      c.classList.remove('is-drag');
      c.style.transform = '';
      layout();
      sfx('card', .35);
    };
    var prev = document.getElementById('pb-deck-prev'), next = document.getElementById('pb-deck-next');
    if (prev) prev.addEventListener('click', back);
    if (next) next.addEventListener('click', function () { throwCard(1); });
    var sx = 0, sy = 0, dx = 0, dragging = false, moved = false, top = null;
    deck.addEventListener('pointerdown', function (e) {
      top = cards[0];
      if (!top.contains(e.target)) return;
      dragging = true; moved = false; sx = e.clientX; sy = e.clientY; dx = 0;
      top.classList.add('is-drag');
      deck.setPointerCapture(e.pointerId);
    });
    deck.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      dx = e.clientX - sx;
      var dy = e.clientY - sy;
      if (Math.abs(dx) > 6) moved = true;
      top.style.transform = 'translate(' + dx + 'px,' + dy * .3 + 'px) rotate(' + dx * .08 + 'deg)';
    });
    var end = function () {
      if (!dragging) return;
      dragging = false;
      if (Math.abs(dx) > 80 && total > 1) { throwCard(dx > 0 ? 1 : -1); return; }
      top.classList.remove('is-drag');
      top.style.transform = '';
      if (!moved) openLb(top.querySelector('.pb-ph'));
    };
    deck.addEventListener('pointerup', end);
    deck.addEventListener('pointercancel', end);
    deck.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') throwCard(1);
      else if (e.key === 'ArrowLeft') back();
      else if (e.key === 'Enter' && e.target.closest('.pb-ph')) { e.preventDefault(); openLb(cards[0].querySelector('.pb-ph')); }
    });
  }

  /* ─────────── folded map: tap to unfold (the real map fades in after) ─────────── */
  var mapBtn = document.getElementById('pb-map-open');
  if (mapBtn) {
    mapBtn.addEventListener('click', function () {
      var map = document.getElementById('pb-map');
      map.classList.add('is-open');
      sfx('paper', .45);
      var frame = map.querySelector('.pb-map-frame');
      if (frame && !frame.src) frame.src = frame.getAttribute('data-src');
    });
  }

  /* ─────────── dress code swatches: a tap wobbles the fabric ─────────── */
  root.querySelectorAll('.pb-swatch').forEach(function (sw) {
    sw.addEventListener('click', function () {
      sw.classList.remove('wobble'); void sw.offsetWidth; sw.classList.add('wobble');
      sfx('tap', .3);
      var r = sw.getBoundingClientRect();
      confetti.burst(r.left + r.width / 2, r.top + r.height / 2, 6, true);
    });
  });

  /* ─────────── RSVP → paper plane ─────────── */
  var opts = document.getElementById('pb-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var card = document.getElementById('pb-rsvp-card');
    var form = document.getElementById('pb-rsvp-form'), note = document.getElementById('pb-rsvp-note');
    var err = document.getElementById('pb-rsvp-err');
    var plane = document.getElementById('pb-plane');
    var done = document.getElementById('pb-rsvp-done');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.pb-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.pb-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); });
      form.classList.add('show'); err.textContent = '';
      sfx('tap', .35);
      if (chosen === 'yes') { var r = b.getBoundingClientRect(); confetti.burst(r.left + r.width / 2, r.top, 10, true); }
    });
    var fly = function () {
      document.getElementById('pb-rsvp-done-title').textContent = chosen === 'yes' ? 'See you there!' : 'Thank you';
      document.getElementById('pb-rsvp-done-text').textContent = chosen === 'yes'
        ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥'
        : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ។';
      if (reduce) { card.hidden = true; done.hidden = false; return; }
      sfx('send', .4);
      card.classList.add('is-folding');
      setTimeout(function () { plane.classList.remove('is-flying'); void plane.offsetWidth; plane.classList.add('is-flying'); }, 450);
      setTimeout(function () {
        card.hidden = true; card.classList.remove('is-folding');
        done.hidden = false;
        sfx('chime', .3);
        var r = done.getBoundingClientRect();
        confetti.burst(r.left + r.width / 2, r.top + 40, 30);
      }, 1700);
    };
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var finish = function (ok) {
        btn.disabled = false;
        if (!ok) { err.textContent = 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'; return; }
        fly();
      };
      if (!rsvpUrl) { finish(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { finish(r.ok); }).catch(function () { finish(false); });
    });
    document.getElementById('pb-rsvp-again').addEventListener('click', function () {
      done.hidden = true; card.hidden = false;
      sfx('tap', .3);
    });
  }

  /* ─────────── gift box ─────────── */
  var box = document.getElementById('pb-box');
  if (box) {
    box.addEventListener('click', function () {
      var gift = document.getElementById('pb-gift');
      if (gift.classList.contains('is-open')) return;
      gift.classList.add('is-open');
      box.disabled = true;
      box.setAttribute('aria-label', 'កាដូបានបើក');
      document.getElementById('pb-gift-btns').classList.add('is-on');
      sfx('open', .4);
      var r = box.getBoundingClientRect();
      confetti.burst(r.left + r.width / 2, r.top + 20, 34);
    });
  }

  /* ─────────── copy the account number ─────────── */
  var toast = document.getElementById('pb-toast'), toastT = null;
  var say = function (m) { toast.textContent = m; toast.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(function () { toast.classList.remove('show'); }, 2200); };
  var copyBtn = document.getElementById('pb-copy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var v = copyBtn.getAttribute('data-copy');
      var ok = function () { say('បានចម្លងលេខគណនី ✓'); sfx('tap', .35); var r = copyBtn.getBoundingClientRect(); confetti.burst(r.left + r.width / 2, r.top, 8, true); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(v).then(ok, function () { say(v); });
      else say(v);
    });
  }

  /* ─────────── finale: throw petals ─────────── */
  var throwBtn = document.getElementById('pb-throw');
  if (throwBtn) {
    throwBtn.addEventListener('click', function () {
      confetti.rain(60);
      sfx('chime', .3);
    });
  }
})();

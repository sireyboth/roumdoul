/* Eternal Vows — resources/views/invitations/templates/wedding/eternal-vows.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.ev');
  if (!root) return;
  root.classList.add('js');

  var config = {};
  try { config = JSON.parse(document.getElementById('ev-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
  var $ = function (id) { return document.getElementById(id); };
  var css = getComputedStyle(root);
  var gold = css.getPropertyValue('--ev-gold').trim() || '#b08d57';
  var goldHi = css.getPropertyValue('--ev-gold-hi').trim() || '#e2cfa6';
  var goldLo = css.getPropertyValue('--ev-gold-lo').trim() || '#6f5630';
  var gate = $('ev-gate');
  var center = function (el) { var r = el.getBoundingClientRect(); return [r.left + r.width / 2, r.top + r.height / 2]; };

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
  var audio = $('ev-audio');
  var soundBtn = $('ev-sound');
  var nowPlaying = $('ev-now');
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
  if (soundBtn) soundBtn.addEventListener('click', function () { setMuted(!muted); if (!muted) sfx('tap', .25); });
  var firstTouch = function (e) {
    ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.removeEventListener(t, firstTouch, true); });
    unlocked = true;
    if (e && e.target && e.target.closest && e.target.closest('#ev-sound')) return;
    if (!gate) startSong(true); // with the cover, the «open» button starts the song
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });
  root.addEventListener('click', function (e) { if (e.target.closest('[data-sfx="tap"]')) sfx('tap', .25); });

  /* ─────────── gold dust: twinkles, foil confetti bursts, champagne bubbles ─────────── */
  var dust = { burst: function () {}, bubbles: function () {}, rain: function () {} };
  var canvas = $('ev-dust');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var parts = [], twinkles = [];
    var tones = [gold, goldHi, goldLo, '#fff6dc', gold, '#f3e7c8'];
    var seedTwinkles = function () {
      var n = Math.max(14, Math.min(40, Math.round(W * H / 26000)));
      twinkles = [];
      for (var i = 0; i < n; i++) {
        twinkles.push({ x: Math.random() * W, y: Math.random() * H, r: 1.2 + Math.random() * 2.2, ph: Math.random() * 6.28, sp: .012 + Math.random() * .02,
          vx: (Math.random() - .5) * .12, vy: -(.04 + Math.random() * .14), c: tones[i % tones.length] });
      }
    };
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      seedTwinkles();
    };
    resize();
    window.addEventListener('resize', resize);
    var sparkle = function (x, y, r) { // a four-point star
      ctx.beginPath();
      ctx.moveTo(x, y - r * 2.2); ctx.quadraticCurveTo(x, y, x + r * 2.2, y); ctx.quadraticCurveTo(x, y, x, y + r * 2.2);
      ctx.quadraticCurveTo(x, y, x - r * 2.2, y); ctx.quadraticCurveTo(x, y, x, y - r * 2.2);
      ctx.fill();
    };
    var make = function (x, y, vx, vy, kind, color) {
      return { x: x, y: y, vx: vx, vy: vy, kind: kind, c: color || tones[(Math.random() * tones.length) | 0],
        w: 3 + Math.random() * 3, h: 7 + Math.random() * 7, r: 2 + Math.random() * 4,
        rot: Math.random() * 6.28, vr: (Math.random() - .5) * .25, flip: Math.random() * 6.28, vf: .1 + Math.random() * .15, sway: Math.random() * 6.28, life: 1 };
    };
    dust.burst = function (x, y, n, color) {
      for (var i = 0; i < n; i++) {
        var a = -Math.PI / 2 + (Math.random() - .5) * 2.8, sp = 3 + Math.random() * 6;
        parts.push(make(x, y, Math.cos(a) * sp, Math.sin(a) * sp, Math.random() < .6 ? 'strip' : 'dia', color && Math.random() < .6 ? color : null));
      }
    };
    dust.bubbles = function (n, x0, x1, y) {
      for (var i = 0; i < n; i++) {
        var b = make(x0 == null ? Math.random() * W : x0 + Math.random() * (x1 - x0), (y == null ? H + 10 : y) + Math.random() * 30, (Math.random() - .5) * .6, -(1.2 + Math.random() * 2.4), 'bub', '#fff6dc');
        b.r = 1.5 + Math.random() * 4.5;
        parts.push(b);
      }
    };
    dust.rain = function (n) {
      for (var i = 0; i < n; i++) {
        var left = i % 2 === 0;
        parts.push(make(left ? -10 : W + 10, H * .9, (left ? 1 : -1) * (3 + Math.random() * 6), -(10 + Math.random() * 7), Math.random() < .7 ? 'strip' : 'dia'));
      }
    };
    var frame = function () {
      ctx.clearRect(0, 0, W, H);
      for (var t = 0; t < twinkles.length; t++) {
        var s = twinkles[t];
        s.ph += s.sp; s.x += s.vx; s.y += s.vy;
        if (s.y < -10) { s.y = H + 10; s.x = Math.random() * W; }
        var al = Math.max(0, Math.sin(s.ph));
        if (al < .05) continue;
        ctx.globalAlpha = al * .7;
        ctx.fillStyle = s.c;
        sparkle(s.x, s.y, s.r * (.6 + al * .5));
      }
      for (var i = parts.length - 1; i >= 0; i--) {
        var b = parts[i];
        b.sway += .05;
        if (b.kind === 'bub') {
          b.x += b.vx + Math.sin(b.sway) * .5; b.y += b.vy; b.vy *= 1.004; b.life -= .004;
          if (b.y < -20 || b.life <= 0) { parts.splice(i, 1); continue; }
          ctx.globalAlpha = Math.min(1, b.life * 1.4) * .8;
          ctx.strokeStyle = b.c; ctx.lineWidth = 1;
          ctx.beginPath(); ctx.arc(b.x, b.y, b.r, 0, 6.28); ctx.stroke();
          ctx.fillStyle = 'rgba(255,246,220,.25)'; ctx.fill();
          continue;
        }
        b.vy += .13; b.vx *= .985; b.vy *= .985;
        if (b.vy > 2.4) b.vy = 2.4;
        b.x += b.vx + (b.vy > 1 ? Math.sin(b.sway) * .8 : 0); b.y += b.vy;
        b.rot += b.vr; b.flip += b.vf;
        if (b.vy > 1.4) b.life -= .005;
        if (b.y > H + 30 || b.life <= 0) { parts.splice(i, 1); continue; }
        var f = Math.cos(b.flip);
        ctx.save();
        ctx.translate(b.x, b.y); ctx.rotate(b.rot); ctx.scale(1, Math.max(.1, Math.abs(f)));
        ctx.globalAlpha = Math.min(1, b.life * 1.6);
        ctx.fillStyle = f < 0 ? goldLo : b.c;
        if (b.kind === 'strip') ctx.fillRect(-b.w / 2, -b.h / 2, b.w, b.h);
        else { ctx.beginPath(); ctx.moveTo(0, -b.r * 1.4); ctx.lineTo(b.r, 0); ctx.lineTo(0, b.r * 1.4); ctx.lineTo(-b.r, 0); ctx.closePath(); ctx.fill(); }
        ctx.restore();
      }
      ctx.globalAlpha = 1;
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
    // a tap on an empty spot: a little puff of gold
    document.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button, a, input, textarea, .ev-lb, .ev-gate')) return;
      dust.burst(e.clientX, e.clientY, 8);
    }, { passive: true });
  }

  /* ─────────── foil shimmer: the highlight follows scroll + pointer / tilt ─────────── */
  var foilGrad = $('ev-foil-g');
  var shScroll = 0, shPtr = 0, shQueued = false;
  var applySh = function () {
    shQueued = false;
    var v = .5 + .5 * Math.sin((shScroll + shPtr) * 6.283);
    root.style.setProperty('--ev-sh', v.toFixed(3));
    if (foilGrad) { var d = ((v - .5) * 1.1).toFixed(3); foilGrad.setAttribute('gradientTransform', 'translate(' + d + ' ' + d + ')'); }
  };
  var queueSh = function () { if (!shQueued) { shQueued = true; requestAnimationFrame(applySh); } };
  var gateFrameSvg = gate ? gate.querySelector('.ev-gate-frame .ev-frame-svg') : null;
  if (!reduce) {
    window.addEventListener('pointermove', function (e) {
      var px = e.clientX / window.innerWidth, py = e.clientY / window.innerHeight;
      shPtr = px * .35 + py * .15;
      queueSh();
      if (gateFrameSvg && gate && !gate.classList.contains('is-leave')) {
        gateFrameSvg.style.transform = 'perspective(800px) rotateY(' + ((px - .5) * 8).toFixed(2) + 'deg) rotateX(' + ((.5 - py) * 6).toFixed(2) + 'deg)';
      }
    }, { passive: true });
    window.addEventListener('deviceorientation', function (e) {
      if (e.gamma == null) return;
      shPtr = (e.gamma + 45) / 90 * .4;
      queueSh();
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
      s.className = 'ev-w'; s.textContent = p; s.style.setProperty('--w', Math.min(w++, 50));
      el.appendChild(s);
    });
  });

  /* ─────────── reveal on scroll (viewport position, not IntersectionObserver: clipped elements never «intersect») ─────────── */
  var pending = [];
  var onReveal = function (el) {
    if (el.id === 'ev-clock') setClock();
    if (el.classList.contains('ev-menu')) setTimeout(function () { clink(true); }, 1100);
  };
  var collect = function () {
    pending = Array.prototype.slice.call(root.querySelectorAll('.ev-page .ev-r'));
    if (reduce) { pending.forEach(function (el) { el.classList.add('is-in'); onReveal(el); }); pending = []; }
  };
  var revealTick = function (vh) {
    if (!pending.length) return;
    pending = pending.filter(function (el) {
      var r = el.getBoundingClientRect();
      if ((r.top < vh * .88 && r.bottom > 0) || r.bottom <= 0) {
        el.classList.add('is-in');
        onReveal(el);
        return false;
      }
      return true;
    });
  };

  /* ─────────── scroll: parallax, story line progress, swing physics for the frames and the lights ─────────── */
  var hero = $('ev-hero');
  var heroIn = root.querySelector('.ev-hero-frame-in');
  var speedEls = Array.prototype.slice.call(root.querySelectorAll('[data-speed]')).filter(function (el) { return el !== heroIn; });
  var time = $('ev-time');
  var chapters = time ? Array.prototype.slice.call(time.querySelectorAll('.ev-chapter')) : [];
  var wall = $('ev-wall');
  var swA = 0, swV = 0, physOn = false;
  var phys = function () {
    swV += -.07 * swA; swV *= .9; swA += swV;
    swA = Math.max(-9, Math.min(9, swA));
    if (Math.abs(swA) < .02 && Math.abs(swV) < .02) { swA = 0; swV = 0; physOn = false; }
    if (wall) wall.style.setProperty('--sw', swA.toFixed(2) + 'deg');
    if (hero) hero.style.setProperty('--swing', (swA * .35).toFixed(2) + 'deg');
    if (physOn) requestAnimationFrame(phys);
  };
  var kick = function (imp) { if (reduce) return; swV += imp; if (!physOn) { physOn = true; requestAnimationFrame(phys); } };
  var lastY = window.scrollY, queued = false;
  var tick = function () {
    queued = false;
    var vh = window.innerHeight, y = window.scrollY;
    revealTick(vh);
    shScroll = y / 1400; queueSh();
    if (!reduce) {
      if (heroIn && y < vh * 1.5) heroIn.style.transform = 'translate3d(0,' + (y * .22).toFixed(1) + 'px,0) scale(' + (1 - Math.min(y / vh, 1) * .06).toFixed(3) + ')';
      speedEls.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        el.style.transform = 'translate3d(0,' + ((r.top + r.height / 2 - vh / 2) * -parseFloat(el.getAttribute('data-speed'))).toFixed(1) + 'px,0)';
      });
    }
    if (time) {
      var tr = time.getBoundingClientRect();
      var p = Math.max(0, Math.min(1, (vh * .62 - tr.top) / tr.height));
      time.style.setProperty('--p', p.toFixed(3));
      chapters.forEach(function (c) {
        var lit = c.getBoundingClientRect().top + 30 < vh * .62;
        if (lit && !c.classList.contains('is-lit')) { c.classList.add('is-lit'); sfx('tap', .14); }
        else if (!lit) c.classList.remove('is-lit');
      });
    }
    var dy = y - lastY;
    lastY = y;
    if (Math.abs(dy) > 1) kick(Math.max(-1.4, Math.min(1.4, dy * .02)));
  };
  var queue = function () { if (!queued) { queued = true; requestAnimationFrame(tick); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue);
  window.addEventListener('load', queue);

  /* ─────────── cover: curtains close behind the frame, a light sweep, they part and the frame flies into the hero ─────────── */
  var started = false;
  var start = function () {
    if (started) return;
    started = true;
    root.classList.add('is-open');
    collect();
    tick();
  };
  var land = function () {
    root.classList.add('is-landed');
    document.documentElement.classList.remove('ev-locked');
  };
  if (gate) {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    window.scrollTo(0, 0);
    document.documentElement.classList.add('ev-locked');
    var openBtn = $('ev-open');
    var gFrame = $('ev-gate-frame');
    var heroFrame = $('ev-hero-frame');
    var opened = false;
    openBtn.addEventListener('click', function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      startSong(true);
      sfx('tap', .4);
      var c = center(openBtn);
      dust.burst(c[0], c[1], 26);
      if (reduce) {
        start(); land();
        gate.classList.add('is-gone');
        setTimeout(function () { gate.remove(); gate = null; }, 450);
        return;
      }
      if (gateFrameSvg) gateFrameSvg.style.transform = '';
      // Smoothness rules for this sequence: every moving part is transform/opacity on its own layer, and the heavy
      // work (showing + laying out the whole page) happens while the curtains are fully closed and nothing moves.
      // Each next stage starts from the frame after that work, not from a fixed clock, so nothing can stutter mid-motion.
      var nextFrame = function (fn) { requestAnimationFrame(function () { requestAnimationFrame(fn); }); };
      gate.classList.add('is-leave');
      setTimeout(function () { gate.classList.add('is-close'); sfx('card', .5); }, 160);
      // 1 · curtains are closed (≈160 + 900ms): the page is shown and measured behind them
      setTimeout(function () {
        start();
        var h = heroFrame.getBoundingClientRect(); // forces the layout now, while the screen is still
        // the page's first paint happens in the next frames; wait for it to be done before anything moves again
        var settle = function (fn) { nextFrame(function () { setTimeout(function () { nextFrame(fn); }, 140); }); };
        settle(function () {
          // 2 · a light sweep crosses the velvet
          gate.classList.add('is-sweep');
          sfx('open', .4);
          setTimeout(function () {
            // 3 · the curtains part and the frame flies into the hero (FLIP; transform only)
            var g = gFrame.getBoundingClientRect();
            h = heroFrame.getBoundingClientRect();
            var sc = h.width / g.width;
            gFrame.classList.add('is-flying');
            nextFrame(function () {
              gFrame.style.transform = 'translate3d(' + (h.left - g.left).toFixed(2) + 'px,' + (h.top - g.top).toFixed(2) + 'px,0) scale(' + sc.toFixed(4) + ')';
              gate.classList.add('is-part');
              sfx('card', .35);
            });
            var landed = false;
            var onLand = function () {
              if (landed) return;
              landed = true;
              // 4 · it lands: the hero frame takes over in the same spot, the cover fades, gold dust
              root.classList.add('is-landed');
              gate.classList.add('is-gone');
              sfx('chime', .3);
              var hc = center(heroFrame);
              dust.burst(hc[0], hc[1] - 40, 44);
              setTimeout(function () { dust.burst(window.innerWidth * .2, window.innerHeight * .3, 16); dust.burst(window.innerWidth * .8, window.innerHeight * .3, 16); }, 220);
              // unlocking scroll re-lays out the page: only once the cover has faded and nothing moves
              setTimeout(function () { if (gate) { gate.remove(); gate = null; } land(); }, 700);
            };
            gFrame.addEventListener('transitionend', function (e) { if (e.propertyName === 'transform') onLand(); });
            setTimeout(onLand, 1700); // in case transitionend never fires
          }, 380);
        });
      }, 1140);
    });
    openBtn.focus({ preventScroll: true });
  } else {
    start();
    land();
  }

  /* ─────────── roman clock: the hands spin to the hour of the wedding; the seconds hand keeps time ─────────── */
  var clock = $('ev-clock');
  var clockSet = false;
  var setClock = function () {
    if (!clock || clockSet) return;
    clockSet = true;
    var h = +clock.getAttribute('data-h'), m = +clock.getAttribute('data-m');
    clock.style.setProperty('--ha', (360 + (h % 12) * 30 + m * .5) + 'deg');
    clock.style.setProperty('--ma', (720 + m * 6) + 'deg');
    setTimeout(function () { sfx('chime', .22); }, 2400);
  };
  if (clock) {
    var secDeg = new Date().getSeconds() * 6;
    clock.style.setProperty('--sa', secDeg + 'deg');
    setInterval(function () { secDeg += 6; clock.style.setProperty('--sa', secDeg + 'deg'); }, 1000);
  }

  /* ─────────── countdown: engraved tiles ─────────── */
  var count = $('ev-count');
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
        var done = $('ev-count-done');
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

  /* ─────────── champagne coupes: clink (on reveal and on tap) ─────────── */
  var glasses = $('ev-glasses');
  var clink = function (auto) {
    if (!glasses) return;
    glasses.classList.remove('is-clink'); void glasses.offsetWidth; glasses.classList.add('is-clink');
    setTimeout(function () {
      sfx('chime', auto ? .2 : .32);
      var r = glasses.getBoundingClientRect();
      dust.bubbles(auto ? 8 : 14, r.left + r.width * .3, r.left + r.width * .7, r.top + r.height * .2);
      if (!auto) dust.burst(r.left + r.width / 2, r.top + 10, 10);
    }, 320);
  };
  if (glasses) glasses.addEventListener('click', function () { clink(false); });

  /* ─────────── photo preview: FLIP zoom from the tapped frame, prev / next, swipe, keys ─────────── */
  var lb = $('ev-lb'), fig = $('ev-lb-fig'), lbImg = $('ev-lb-img'), lbCap = $('ev-lb-cap'), lbCount = $('ev-lb-count');
  var lbPrev = $('ev-lb-prev'), lbNext = $('ev-lb-next'), lbX = $('ev-lb-x');
  var group = [], idx = 0, lastFocus = null, lbOpen = false, swapping = false;
  var EASE = 'cubic-bezier(.22,.8,.18,1)';
  var setContent = function (i) {
    var b = group[i];
    lbImg.src = b.getAttribute('data-full');
    lbImg.alt = b.getAttribute('data-cap') || '';
    lbCap.textContent = b.getAttribute('data-cap') || '';
    lbCap.classList.toggle('ev-kh', /[ក-៿]/.test(lbCap.textContent));
    lbCount.textContent = group.length > 1 ? khDigits(i + 1) + ' / ' + khDigits(group.length) : '';
    lbPrev.hidden = lbNext.hidden = group.length < 2;
  };
  var whenReady = function (cb) {
    var done = false, go = function () { if (!done) { done = true; cb(); } };
    if (lbImg.complete && lbImg.naturalWidth) { go(); return; }
    lbImg.onload = go; lbImg.onerror = go;
    setTimeout(go, 1500);
  };
  var thumbRect = function (b) { return (b.querySelector('img') || b).getBoundingClientRect(); };
  // the transform + crop that puts the preview exactly over the thumbnail (thumbnail = object-fit:cover)
  var flipTo = function (f, t) {
    var s = Math.max(t.width / f.width, t.height / f.height);
    var ix = Math.max(0, (f.width - t.width / s) / 2), iy = Math.max(0, (f.height - t.height / s) / 2);
    return { transform: 'translate(' + (t.left - f.left - ix * s).toFixed(1) + 'px,' + (t.top - f.top - iy * s).toFixed(1) + 'px) scale(' + s.toFixed(4) + ')',
      clip: 'inset(' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px)' };
  };
  var openLb = function (btn) {
    if (lbOpen) return;
    lastFocus = btn;
    group = Array.prototype.slice.call(root.querySelectorAll('.ev-ph[data-group="' + btn.getAttribute('data-group') + '"]'));
    idx = Math.max(0, group.indexOf(btn));
    setContent(idx);
    lbOpen = true;
    lb.hidden = false;
    fig.style.cssText = 'opacity:0';
    sfx('card', .3);
    whenReady(function () {
      requestAnimationFrame(function () {
        if (reduce) { fig.style.cssText = ''; lb.classList.add('show'); return; }
        var f = fig.getBoundingClientRect(), m = flipTo(f, thumbRect(btn));
        fig.style.cssText = 'transition:none;transform:' + m.transform + ';clip-path:' + m.clip + ';opacity:1';
        void fig.offsetWidth;
        fig.style.cssText = 'transition:transform .75s ' + EASE + ', clip-path .75s ' + EASE + ';transform:none;clip-path:inset(0px 0px);opacity:1';
        lb.classList.add('show');
        setTimeout(function () { if (lbOpen && !swapping) fig.style.cssText = ''; }, 780);
      });
    });
    lbX.focus({ preventScroll: true });
  };
  var closeLb = function () {
    if (!lbOpen) return;
    lbOpen = false;
    sfx('tap', .22);
    var b = group[idx] || lastFocus;
    var t = thumbRect(b);
    var visible = t.bottom > 0 && t.top < window.innerHeight && t.width > 0;
    lb.classList.remove('show');
    if (!reduce && visible) {
      fig.style.cssText = 'transition:none';
      var f = fig.getBoundingClientRect(), m = flipTo(f, t);
      void fig.offsetWidth;
      fig.style.cssText = 'transition:transform .6s ' + EASE + ', clip-path .6s ' + EASE + ', opacity .25s ease .4s;transform:' + m.transform + ';clip-path:' + m.clip + ';opacity:0';
    } else {
      fig.style.cssText = 'transition:opacity .3s ease;opacity:0';
    }
    setTimeout(function () { if (!lbOpen) { lb.hidden = true; fig.style.cssText = ''; } }, 650);
    if (b) b.focus({ preventScroll: true });
  };
  var nav = function (d) {
    if (group.length < 2 || swapping || !lbOpen) return;
    swapping = true;
    sfx('card', .25);
    var n = (idx + d + group.length) % group.length;
    if (reduce) { idx = n; setContent(idx); swapping = false; return; }
    fig.style.cssText = 'transition:transform .28s ' + EASE + ', opacity .28s ease;transform:translateX(' + (-d * 60) + 'px) rotate(' + (-d * 2) + 'deg);opacity:0';
    setTimeout(function () {
      idx = n;
      setContent(idx);
      fig.style.cssText = 'transition:none;transform:translateX(' + (d * 60) + 'px) rotate(' + (d * 2) + 'deg);opacity:0';
      whenReady(function () {
        void fig.offsetWidth;
        fig.style.cssText = 'transition:transform .45s ' + EASE + ', opacity .35s ease;transform:none;opacity:1';
        setTimeout(function () { swapping = false; fig.style.cssText = ''; }, 460);
      });
    }, 260);
  };
  root.querySelectorAll('.ev-ph').forEach(function (b) { b.addEventListener('click', function () { openLb(b); }); });
  lbX.addEventListener('click', closeLb);
  lbPrev.addEventListener('click', function () { nav(-1); });
  lbNext.addEventListener('click', function () { nav(1); });
  lb.querySelector('.ev-lb-bg').addEventListener('click', closeLb);
  document.addEventListener('keydown', function (e) {
    if (!lbOpen) return;
    if (e.key === 'Escape') closeLb();
    else if (e.key === 'ArrowRight') nav(1);
    else if (e.key === 'ArrowLeft') nav(-1);
    else if (e.key === 'Tab') { // keep focus inside the preview
      var f = [lbPrev, lbNext, lbX].filter(function (x) { return !x.hidden; });
      var k = f.indexOf(document.activeElement);
      e.preventDefault();
      f[(k + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
    }
  });
  var sx = 0, sdx = 0, dragging = false;
  fig.addEventListener('pointerdown', function (e) {
    if (swapping || group.length < 2) return;
    dragging = true; sx = e.clientX; sdx = 0;
    fig.setPointerCapture(e.pointerId);
    fig.style.cssText = 'transition:none';
  });
  fig.addEventListener('pointermove', function (e) {
    if (!dragging) return;
    sdx = e.clientX - sx;
    fig.style.transform = 'translateX(' + sdx + 'px) rotate(' + (sdx * .02) + 'deg)';
  });
  var endDrag = function () {
    if (!dragging) return;
    dragging = false;
    if (Math.abs(sdx) > 60) { nav(sdx < 0 ? 1 : -1); return; }
    fig.style.cssText = 'transition:transform .4s ' + EASE + ';transform:none';
  };
  fig.addEventListener('pointerup', endDrag);
  fig.addEventListener('pointercancel', endDrag);

  /* ─────────── engraved map: tap shows the real map (circle wipe) ─────────── */
  var mapBtn = $('ev-map-open');
  if (mapBtn) {
    mapBtn.addEventListener('click', function () {
      var map = $('ev-map');
      var frameEl = map.querySelector('.ev-map-frame');
      if (frameEl && !frameEl.src) frameEl.src = frameEl.getAttribute('data-src');
      map.classList.add('is-open');
      sfx('paper', .4);
    });
  }

  /* ─────────── dress code fabric chips: a tap makes the fabric flutter ─────────── */
  root.querySelectorAll('.ev-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      chip.classList.remove('flutter'); void chip.offsetWidth; chip.classList.add('flutter');
      sfx('tap', .3);
      var c = center(chip);
      dust.burst(c[0], c[1], 10, getComputedStyle(chip.parentNode).getPropertyValue('--c').trim());
    });
  });

  /* ─────────── RSVP: the reply card gets a foil stamp and turns over ─────────── */
  var opts = $('ev-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var reply = $('ev-reply'), replyIn = $('ev-reply-in');
    var form = $('ev-rsvp-form'), note = $('ev-rsvp-note'), err = $('ev-rsvp-err');
    var again = $('ev-rsvp-again');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.ev-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.ev-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); });
      form.classList.add('show'); err.textContent = '';
      sfx('tap', .3);
      if (chosen === 'yes') { var c = center(b); dust.burst(c[0], c[1], 10); }
    });
    var sent = function () {
      $('ev-rsvp-done-title').textContent = chosen === 'yes' ? 'See you there' : 'Merci';
      $('ev-rsvp-done-text').textContent = chosen === 'yes'
        ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ។'
        : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ។';
      if (reduce) { replyIn.classList.add('is-flipped'); again.tabIndex = 0; return; }
      reply.classList.remove('is-stamped'); void reply.offsetWidth; reply.classList.add('is-stamped');
      sfx('send', .35);
      setTimeout(function () { sfx('tap', .45); var st = center($('ev-stamp')); dust.burst(st[0], st[1], 16); }, 480);
      setTimeout(function () { replyIn.classList.add('is-flipped'); sfx('card', .35); }, 1300);
      setTimeout(function () { again.tabIndex = 0; sfx('chime', .3); var c = center(reply); dust.burst(c[0], c[1] - 40, 30); }, 2400);
    };
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var finish = function (ok) {
        btn.disabled = false;
        if (!ok) { err.textContent = 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'; return; }
        sent();
      };
      if (!rsvpUrl) { finish(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { finish(r.ok); }).catch(function () { finish(false); });
    });
    again.addEventListener('click', function () {
      replyIn.classList.remove('is-flipped');
      reply.classList.remove('is-stamped');
      again.tabIndex = -1;
      sfx('card', .3);
    });
  }

  /* ─────────── gift: the velvet ring box opens ─────────── */
  var lid = $('ev-case-lid');
  if (lid) {
    lid.addEventListener('click', function () {
      var box = $('ev-case');
      if (box.classList.contains('is-open')) return;
      box.classList.add('is-open');
      sfx('card', .35);
      setTimeout(function () {
        sfx('open', .35);
        var c = center(box);
        dust.burst(c[0], c[1] - 20, 36);
        $('ev-gift-btns').classList.add('is-on');
      }, reduce ? 0 : 650);
    });
  }

  /* ─────────── copy the account number ─────────── */
  var toast = $('ev-toast'), toastT = null;
  var say = function (m) { toast.textContent = m; toast.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(function () { toast.classList.remove('show'); }, 2200); };
  var copyBtn = $('ev-copy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var v = copyBtn.getAttribute('data-copy');
      var ok = function () { say('បានចម្លងលេខគណនី ✓'); sfx('tap', .3); var c = center(copyBtn); dust.burst(c[0], c[1], 8); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(v).then(ok, function () { say(v); });
      else say(v);
    });
  }

  /* ─────────── finale: raise a toast ─────────── */
  var raise = $('ev-raise');
  if (raise) {
    raise.addEventListener('click', function () {
      sfx('tap', .4);
      setTimeout(function () { sfx('chime', .3); }, 200);
      dust.rain(50);
      dust.bubbles(40);
      raise.classList.remove('is-clink'); void raise.offsetWidth; raise.classList.add('is-clink');
    });
  }
})();

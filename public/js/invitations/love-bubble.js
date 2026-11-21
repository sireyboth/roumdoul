/* Love Bubble — resources/views/invitations/templates/wedding/love-bubble.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.lb2');
  if (!root) return;
  root.classList.add('js');

  var config = {};
  try { config = JSON.parse(document.getElementById('lb2-config').textContent) || {}; } catch (e) { config = {}; }
  var sfxUrls = config.sfx || {};
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
  var isKh = function (s) { return /[ក-៿]/.test(s || ''); };
  var css = getComputedStyle(root);
  var accent = css.getPropertyValue('--lb2-accent').trim() || '#ff7eb6';
  var $ = function (id) { return document.getElementById(id); };
  var center = function (el) { var r = el.getBoundingClientRect(); return [r.left + r.width / 2, r.top + r.height / 2]; };
  var gate = $('lb2-gate');

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
  var audio = $('lb2-audio');
  var soundBtn = $('lb2-sound');
  var nowPlaying = $('lb2-now');
  var player = $('lb2-player');
  var yt = null, playing = false, songWanted = true, nowTimer = null;
  var setPlaying = function (on) {
    playing = on;
    if (soundBtn) soundBtn.classList.toggle('is-playing', on);
    if (player) player.classList.toggle('is-playing', on);
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
    if (e && e.target && e.target.closest && e.target.closest('#lb2-sound, #lb2-player-btn')) return;
    if (!gate) startSong(true); // with the cover, «Open» starts the song
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
  document.addEventListener('visibilitychange', function () {
    if (!audio) return;
    if (document.hidden) audio.pause();
    else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
  });
  root.addEventListener('click', function (e) { if (e.target.closest('[data-sfx="tap"]')) sfx('tap', .3); });

  /* ─────────── confetti + emoji rain (hearts, stars, dots, paper strips, emoji) ─────────── */
  var confetti = { burst: function () {}, rain: function () {} };
  var canvas = $('lb2-confetti');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2), bits = [], running = false;
    var tones = [accent, '#ffd84d', '#b9a2ff', '#8cc8ff', '#6fdcaa', '#ffffff', '#ffb3d4'];
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);
    var emojiFont = function (s) { return Math.round(s) + 'px "Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif'; };
    var make = function (x, y, vx, vy, kind, emoji) {
      return { x: x, y: y, vx: vx, vy: vy, kind: kind, e: emoji, c: tones[(Math.random() * tones.length) | 0],
        s: kind === 'emoji' ? 18 + Math.random() * 14 : kind === 'heart' ? 7 + Math.random() * 6 : 4 + Math.random() * 5,
        rot: Math.random() * 6.28, vr: (Math.random() - .5) * .25, flip: Math.random() * 6.28, vf: .08 + Math.random() * .14, sway: Math.random() * 6.28, life: 1, g: kind === 'emoji' ? .1 : .16 };
    };
    var heart = function (r) {
      ctx.beginPath(); ctx.moveTo(0, r * .45);
      ctx.bezierCurveTo(-r * 1.15, -r * .3, -r * .55, -r * 1.1, 0, -r * .45);
      ctx.bezierCurveTo(r * .55, -r * 1.1, r * 1.15, -r * .3, 0, r * .45);
      ctx.fill(); ctx.stroke();
    };
    var star = function (r) {
      ctx.beginPath();
      for (var i = 0; i < 8; i++) { var a = i * Math.PI / 4, rr = i % 2 ? r * .38 : r; ctx.lineTo(Math.cos(a) * rr, Math.sin(a) * rr); }
      ctx.closePath(); ctx.fill(); ctx.stroke();
    };
    var frame = function () {
      ctx.clearRect(0, 0, W, H);
      for (var i = bits.length - 1; i >= 0; i--) {
        var b = bits[i];
        b.sway += .05; b.vy += b.g; b.vx *= .985; b.vy *= .985;
        if (b.vy > 3.2) b.vy = 3.2;
        b.x += b.vx + (b.vy > 1 ? Math.sin(b.sway) * .8 : 0); b.y += b.vy;
        if (b.vy > 1.5) b.life -= .005;
        b.rot += b.vr; b.flip += b.vf;
        if (b.y > H + 40 || b.life <= 0) { bits.splice(i, 1); continue; }
        ctx.save();
        ctx.translate(b.x, b.y); ctx.rotate(b.kind === 'emoji' ? b.rot * .3 : b.rot);
        ctx.globalAlpha = Math.min(1, b.life * 1.6);
        if (b.kind === 'emoji') {
          ctx.font = emojiFont(b.s); ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
          ctx.fillText(b.e, 0, 0);
        } else {
          ctx.scale(1, Math.max(.15, Math.abs(Math.cos(b.flip))));
          ctx.fillStyle = b.c; ctx.strokeStyle = '#2b1d3a'; ctx.lineWidth = 1.4;
          if (b.kind === 'heart') heart(b.s);
          else if (b.kind === 'star') star(b.s * 1.4);
          else if (b.kind === 'dot') { ctx.beginPath(); ctx.arc(0, 0, b.s * .8, 0, 6.29); ctx.fill(); ctx.stroke(); }
          else { ctx.fillRect(-b.s, -b.s * .4, b.s * 2, b.s * .8); ctx.strokeRect(-b.s, -b.s * .4, b.s * 2, b.s * .8); }
        }
        ctx.restore();
      }
      if (bits.length) requestAnimationFrame(frame); else { running = false; ctx.clearRect(0, 0, W, H); }
    };
    var go = function () { if (!running) { running = true; requestAnimationFrame(frame); } };
    var kinds = ['heart', 'star', 'dot', 'strip', 'heart'];
    confetti.burst = function (x, y, n, emojis) {
      for (var i = 0; i < n; i++) {
        var a = -Math.PI / 2 + (Math.random() - .5) * 2.8, sp = 3 + Math.random() * 6;
        var em = emojis && Math.random() < .45 ? emojis[(Math.random() * emojis.length) | 0] : null;
        bits.push(make(x, y, Math.cos(a) * sp, Math.sin(a) * sp, em ? 'emoji' : kinds[(Math.random() * kinds.length) | 0], em));
      }
      go();
    };
    // emoji falling from the top (RSVP, finale)
    confetti.rain = function (emojis, n) {
      for (var i = 0; i < n; i++) {
        var b = make(Math.random() * W, -40 - Math.random() * H * .6, (Math.random() - .5) * 1.5, 1 + Math.random() * 2, 'emoji', emojis[(Math.random() * emojis.length) | 0]);
        b.s = 22 + Math.random() * 18; b.life = 1.4;
        bits.push(b);
      }
      go();
    };
    // a tap on an empty spot: a little puff of hearts + stars
    document.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button, a, input, textarea, .lb2-lb, .lb2-cards, .lb2-board, .lb2-drag, .lb2-gate, .lb2-bub')) return;
      confetti.burst(e.clientX, e.clientY, 7);
    }, { passive: true });
  }

  /* ─────────── reveal on scroll (viewport position, checked every frame something moves) ─────────── */
  var pending = [];
  var onReveal = {};
  var collect = function () {
    pending = Array.prototype.slice.call(root.querySelectorAll('.lb2-page .lb2-r:not(.is-in)'));
    // gallery photos that come into view together land one after another
    root.querySelectorAll('.lb2-gi').forEach(function (el, i) { el.style.setProperty('--rd', (i % 4 * .08) + 's'); });
    if (reduce) { pending.forEach(function (el) { el.classList.add('is-in'); fire(el); }); pending = []; }
  };
  var fire = function (el) { if (el.id && onReveal[el.id]) onReveal[el.id](); };
  var revealTick = function (vh) {
    if (!pending.length) return;
    pending = pending.filter(function (el) {
      var r = el.getBoundingClientRect();
      if ((r.top < vh * .88 && r.bottom > 0) || r.bottom <= 0) {
        el.classList.add('is-in');
        fire(el);
        if (el.getAttribute('data-r') === 'slap' && r.bottom > 0) setTimeout(function () { sfx('tap', .14); }, 120);
        return false;
      }
      return true;
    });
  };

  /* ─────────── scroll physics: parallax doodles, marquee speed, the gallery jiggles with scroll velocity ─────────── */
  var speedEls = Array.prototype.slice.call(root.querySelectorAll('.lb2-doodle[data-speed]'));
  var marquee = $('lb2-marquee');
  var jelly = Array.prototype.slice.call(root.querySelectorAll('.lb2-grid, .lb2-duo, .lb2-contacts'));
  var lastY = window.scrollY, velocity = 0, queued = false;
  var scrollHooks = [];
  var tick = function () {
    queued = false;
    var vh = window.innerHeight;
    revealTick(vh);
    var y = window.scrollY;
    velocity = velocity * .6 + (y - lastY) * .4;
    lastY = y;
    if (!reduce) {
      speedEls.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        el.style.transform = 'translate3d(0,' + ((r.top + r.height / 2 - vh / 2) * -parseFloat(el.getAttribute('data-speed'))).toFixed(1) + 'px,0)';
      });
    }
    scrollHooks.forEach(function (f) { f(vh); });
  };
  var queue = function () { if (!queued) { queued = true; requestAnimationFrame(tick); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue);
  window.addEventListener('load', queue);
  // marquee + jelly run on their own loop (eased toward the scroll speed)
  if (!reduce) {
    var mx = 0, skew = 0;
    var loop = function () {
      velocity *= .92;
      var v = Math.max(-40, Math.min(40, velocity));
      if (marquee) {
        mx -= .6 + Math.abs(v) * .25;
        var half = marquee.scrollWidth / 2;
        if (half && -mx > half) mx += half;
        marquee.style.transform = 'translate3d(' + mx.toFixed(1) + 'px,0,0)';
      }
      skew += (v * .12 - skew) * .15;
      if (Math.abs(skew) > .01) jelly.forEach(function (el) { el.style.transform = 'skewY(' + skew.toFixed(2) + 'deg)'; });
      requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);
  }

  /* ─────────── draggable stickers (hero) ─────────── */
  var dragSticker = function (el, bounds) {
    var sx, sy, ox = 0, oy = 0, bx = 0, by = 0, on = false;
    el.addEventListener('pointerdown', function (e) {
      on = true; sx = e.clientX; sy = e.clientY; bx = ox; by = oy;
      el.classList.add('is-dragging');
      el.setPointerCapture(e.pointerId);
      sfx('tap', .3);
      e.preventDefault();
    });
    el.addEventListener('pointermove', function (e) {
      if (!on) return;
      var b = bounds.getBoundingClientRect(), r = el.getBoundingClientRect();
      var nx = bx + e.clientX - sx, ny = by + e.clientY - sy;
      // keep it inside the section
      var dx = nx - ox, dy = ny - oy;
      if (r.left + dx < b.left) nx -= r.left + dx - b.left;
      if (r.right + dx > b.right) nx -= r.right + dx - b.right;
      if (r.top + dy < b.top) ny -= r.top + dy - b.top;
      if (r.bottom + dy > b.bottom) ny -= r.bottom + dy - b.bottom;
      ox = nx; oy = ny;
      el.style.translate = ox + 'px ' + oy + 'px';
    });
    var end = function () {
      if (!on) return;
      on = false; el.classList.remove('is-dragging');
      sfx('pop', .3);
      var c = center(el); confetti.burst(c[0], c[1], 6);
    };
    el.addEventListener('pointerup', end);
    el.addEventListener('pointercancel', end);
  };
  var hero = $('lb2-hero');
  root.querySelectorAll('.lb2-hero .lb2-drag').forEach(function (el) { dragSticker(el, hero); });

  /* ─────────── cover: squish → stickers fly → jelly → blob hole + the photo flies into the hero ─────────── */
  var heroPhoto = $('lb2-hero-photo');
  var started = false;
  var start = function () {
    if (started) return;
    started = true;
    root.classList.add('is-open');
    document.documentElement.classList.remove('lb2-locked');
    collect();
    tick();
  };
  var land = function () {
    root.classList.add('is-landed');
    sfx('chime', .3);
    var c = center(heroPhoto);
    confetti.burst(c[0], c[1], 46, ['💖', '✨', '🥳', '💍', '🎀']);
  };
  // a wobbly blob hole grows through the cover (clip-path polygon, even-odd) around x,y
  var hole = function (el, cx, cy, dur, done) {
    var W = window.innerWidth, H = window.innerHeight, N = 60;
    var maxR = Math.hypot(Math.max(cx, W - cx), Math.max(cy, H - cy)) * 1.3;
    var t0 = performance.now();
    var f = function (now) {
      var t = Math.min(1, (now - t0) / dur);
      var e = t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
      var R = maxR * e;
      var pts = ['0px 0px', W + 'px 0px', W + 'px ' + H + 'px', '0px ' + H + 'px', '0px 0px'];
      for (var i = 0; i <= N; i++) {
        var a = i / N * Math.PI * 2;
        var w = 1 + .16 * Math.sin(5 * a + t * 10) * (1 - t * .7) + .08 * Math.sin(3 * a - t * 7);
        pts.push((cx + Math.cos(a) * R * w).toFixed(1) + 'px ' + (cy + Math.sin(a) * R * w).toFixed(1) + 'px');
      }
      pts.push('0px 0px');
      el.style.clipPath = 'polygon(evenodd, ' + pts.join(',') + ')';
      if (t < 1) requestAnimationFrame(f); else if (done) done();
    };
    requestAnimationFrame(f);
  };
  if (gate) {
    document.documentElement.classList.add('lb2-locked');
    var openBtn = $('lb2-open');
    var story = $('lb2-story');
    var storyPhoto = $('lb2-story-photo');
    var fly = $('lb2-fly');
    var opened = false;
    openBtn.addEventListener('click', function () {
      if (opened) return;
      opened = true;
      unlocked = true;
      sfx('pop', .5);
      startSong(true);
      openBtn.classList.add('is-press');
      if (reduce) {
        root.classList.add('is-opening', 'is-landed');
        gate.style.transition = 'opacity .4s ease';
        gate.style.opacity = '0';
        start();
        setTimeout(function () { gate.remove(); gate = null; }, 450);
        return;
      }
      // every sticker flies off away from the middle
      var cx = window.innerWidth / 2, cy = window.innerHeight / 2;
      gate.querySelectorAll('.lb2-cs').forEach(function (s, i) {
        var c = center(s), dx = c[0] - cx, dy = c[1] - cy, len = Math.hypot(dx, dy) || 1;
        var from = getComputedStyle(s).transform;
        s.style.animation = 'none';
        s.animate([
          { transform: from === 'none' ? 'none' : from, opacity: 1 },
          { transform: 'translate(' + (dx / len * 14).toFixed(0) + 'px,' + (dy / len * 14).toFixed(0) + 'px) scale(1.25)', opacity: 1, offset: .25 },
          { transform: 'translate(' + (dx / len * window.innerWidth).toFixed(0) + 'px,' + (dy / len * window.innerHeight * .85 - 80).toFixed(0) + 'px) rotate(' + ((Math.random() - .5) * 240).toFixed(0) + 'deg) scale(1.4)', opacity: 0 }
        ], { duration: 720, delay: 40 + i * 30, easing: 'cubic-bezier(.45,0,.75,.6)', fill: 'forwards' });
      });
      var b = center(openBtn);
      setTimeout(function () {
        openBtn.classList.remove('is-press');
        gate.classList.add('is-opening');
        story.classList.add('is-jelly');
        confetti.burst(b[0], b[1], 30, ['💌', '💖', '✨']);
        sfx('card', .4);
      }, 100);

      // stage 2: the photo becomes a blob and flies into the hero; the cover melts away around it
      setTimeout(function () {
        root.classList.add('is-opening');
        window.scrollTo(0, 0);
        var from = storyPhoto.getBoundingClientRect();
        var to = heroPhoto.getBoundingClientRect();
        var img = storyPhoto.querySelector('img'), flyImg = fly.querySelector('img');
        if (img && flyImg) flyImg.style.transform = getComputedStyle(img).transform;
        fly.style.left = from.left + 'px'; fly.style.top = from.top + 'px';
        fly.style.width = from.width + 'px'; fly.style.height = from.height + 'px';
        fly.style.transition = 'none';
        fly.style.transform = 'none';
        fly.style.clipPath = 'inset(0px 0px 0px 0px round 0px 0px 0px 0px / 0px 0px 0px 0px)';
        fly.classList.add('is-on');
        story.style.opacity = '0';
        // the hero's aspect, cut from the middle of the story photo, then scaled onto the hero
        var ratio = to.width / to.height;
        var cw = Math.min(from.width, from.height * ratio), ch = cw / ratio;
        var ix = (from.width - cw) / 2, iy = (from.height - ch) / 2, s = to.width / cw;
        var R = function (p, d) { return (p * d).toFixed(1) + 'px'; };
        var round = [R(.46, cw), R(.54, cw), R(.44, cw), R(.56, cw)].join(' ') + ' / ' + [R(.52, ch), R(.44, ch), R(.56, ch), R(.48, ch)].join(' ');
        // the hero photo starts zoomed to the exact crop the flying photo lands with, then relaxes
        var heroImg = heroPhoto.querySelector('img');
        if (img && heroImg && img.naturalWidth) {
          var nw = img.naturalWidth, nh = img.naturalHeight, m = /matrix\(([^,]+)/.exec(getComputedStyle(img).transform), kb = m ? parseFloat(m[1]) : 1;
          var flyVis = cw / (Math.max(from.width / nw, from.height / nh) * kb);
          var heroVis = to.width / Math.max(to.width / nw, to.height / nh);
          heroImg.style.transition = 'none';
          heroImg.style.transform = 'scale(' + Math.max(1, heroVis / flyVis).toFixed(3) + ')';
        }
        void fly.offsetWidth;
        fly.style.transition = 'transform 1s cubic-bezier(.5,-.12,.2,1.12), clip-path .75s cubic-bezier(.4,0,.2,1), opacity .35s ease';
        fly.style.transform = 'translate(' + (to.left - from.left - ix * s).toFixed(1) + 'px,' + (to.top - from.top - iy * s).toFixed(1) + 'px) scale(' + s.toFixed(4) + ')';
        fly.style.clipPath = 'inset(' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px ' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px round ' + round + ')';
        sfx('open', .3);
        var hc = center(heroPhoto);
        setTimeout(function () { gate.classList.add('is-melt'); hole(gate, hc[0], hc[1], 1000); }, 60);
        setTimeout(start, 300);
        setTimeout(function () {
          land();
          fly.style.opacity = '0';
          if (heroImg) { heroImg.style.transition = 'transform 1.2s cubic-bezier(.3,.7,.2,1) .15s'; heroImg.style.transform = ''; }
        }, 920);
        setTimeout(function () {
          if (gate) { gate.remove(); gate = null; }
          fly.classList.remove('is-on'); fly.style.cssText = '';
          root.classList.remove('is-opening');
        }, 1450);
      }, 420);
    });
    openBtn.focus({ preventScroll: true });
  } else {
    root.classList.add('is-landed');
    requestAnimationFrame(start); // after every section below has hooked in
  }

  /* ─────────── chat thread: bubbles type themselves in ─────────── */
  var thread = $('lb2-thread');
  if (thread) {
    var msgs = Array.prototype.slice.call(thread.querySelectorAll('.lb2-msg'));
    var typing = $('lb2-typing');
    var phone = $('lb2-phone');
    var chatI = 0, chatOn = false, chatFast = false;
    var showAll = function () { chatFast = true; msgs.forEach(function (m) { m.classList.add('is-shown'); }); typing.classList.remove('show'); chatI = msgs.length; };
    var nextMsg = function () {
      if (chatFast || chatI >= msgs.length) { typing.classList.remove('show'); return; }
      var m = msgs[chatI];
      typing.classList.toggle('me', m.classList.contains('me'));
      typing.classList.add('show');
      var wait = Math.min(1250, 420 + m.textContent.trim().length * 5);
      setTimeout(function () {
        if (chatFast) return;
        typing.classList.remove('show');
        m.classList.add('is-shown');
        sfx('tap', .2);
        chatI++;
        setTimeout(nextMsg, 380);
      }, wait);
    };
    onReveal['lb2-phone'] = function () {
      if (chatOn) return;
      chatOn = true;
      if (reduce || phone.getBoundingClientRect().bottom < 0) { showAll(); return; }
      setTimeout(nextMsg, 500);
    };
    // scrolled past before it finished: show everything
    scrollHooks.push(function () { if (chatOn && !chatFast && chatI < msgs.length && phone.getBoundingClientRect().bottom < 80) showAll(); });
    // a tap on a bubble = a heart reaction
    thread.addEventListener('click', function (e) {
      var bub = e.target.closest('.lb2-bub'); if (!bub) return;
      bub.classList.toggle('is-liked');
      sfx('pop', .3);
      if (bub.classList.contains('is-liked')) { var r = bub.getBoundingClientRect(); confetti.burst(r.right - 6, r.bottom - 4, 8, ['❤️']); }
    });
  }

  /* ─────────── couple profile cards: tap = like ─────────── */
  root.querySelectorAll('.lb2-profile-card').forEach(function (card) {
    card.addEventListener('click', function () {
      card.classList.toggle('is-liked');
      sfx('pop', .35);
      if (card.classList.contains('is-liked')) { var c = center(card); confetti.burst(c[0], c[1] - 40, 16, ['💖', '😍']); }
    });
  });

  /* ─────────── ticket stub: tear it ─────────── */
  var stub = $('lb2-stub');
  if (stub) {
    stub.addEventListener('click', function () {
      stub.classList.toggle('is-torn');
      sfx('paper', .4);
      if (stub.classList.contains('is-torn')) { var c = center(stub); confetti.burst(c[0], c[1], 12); }
    });
  }

  /* ─────────── «loading wedding…» bar + slot countdown ─────────── */
  var count = $('lb2-count');
  if (count) {
    var target = new Date(count.getAttribute('data-target')).getTime();
    var from0 = new Date(count.getAttribute('data-start')).getTime();
    var pctEl = $('lb2-pct');
    var cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var setCell = function (u, v) {
      var txt = khDigits(String(v).padStart(2, '0'));
      if (cells[u].textContent === txt) return;
      cells[u].textContent = txt;
      if (reduce) return;
      cells[u].classList.remove('roll'); void cells[u].offsetWidth; cells[u].classList.add('roll');
    };
    var countTick = function () {
      var diff = Math.max(0, target - Date.now());
      if (!diff) {
        count.querySelector('.lb2-slots').hidden = true;
        var done = $('lb2-count-done'); if (done) done.hidden = false;
        return;
      }
      var s = Math.floor(diff / 1000);
      setCell('d', Math.floor(s / 86400)); setCell('h', Math.floor(s % 86400 / 3600));
      setCell('m', Math.floor(s % 3600 / 60)); setCell('s', s % 60);
      setTimeout(countTick, 1000);
    };
    countTick();
    onReveal['lb2-count'] = function () {
      var p = Math.max(.04, Math.min(1, (Date.now() - from0) / (target - from0)));
      setTimeout(function () {
        count.style.setProperty('--p', p.toFixed(3));
        var t0 = performance.now(), goal = Math.round(p * 100);
        var step = function (now) {
          var k = Math.min(1, (now - t0) / 1800);
          pctEl.textContent = khDigits(Math.round(goal * (1 - Math.pow(1 - k, 3)))) + '%';
          if (k < 1) requestAnimationFrame(step); else sfx('chime', .2);
        };
        requestAnimationFrame(step);
      }, 400);
    };
  }

  /* ─────────── now playing card ─────────── */
  var playerBtn = $('lb2-player-btn');
  if (playerBtn) {
    playerBtn.addEventListener('click', function () {
      unlocked = true;
      sfx('tap', .3);
      if (playing) { songWanted = false; stopSong(); }
      else { if (muted) setMuted(false); songWanted = true; startSong(true); }
    });
    var prog = $('lb2-player-prog');
    if (audio && prog) {
      audio.addEventListener('timeupdate', function () {
        if (audio.duration) prog.style.transform = 'scaleX(' + (audio.currentTime / audio.duration).toFixed(4) + ')';
      });
    }
  }

  /* ─────────── story cards: coverflow as you swipe ─────────── */
  var cardsEl = $('lb2-cards');
  if (cardsEl) {
    var scards = Array.prototype.slice.call(cardsEl.querySelectorAll('.lb2-scard'));
    var hls = Array.prototype.slice.call(root.querySelectorAll('.lb2-hl'));
    var cardsCount = $('lb2-cards-count');
    var active = 0, cq = false;
    var flow = function () {
      cq = false;
      var mid = cardsEl.scrollLeft + cardsEl.clientWidth / 2, best = 0, bestD = 1e9;
      scards.forEach(function (c, i) {
        var d = (c.offsetLeft + c.offsetWidth / 2 - mid) / (c.offsetWidth + 18);
        if (Math.abs(d) < bestD) { bestD = Math.abs(d); best = i; }
        if (reduce) return;
        var k = Math.max(-1.6, Math.min(1.6, d));
        c.style.transform = 'rotateY(' + (-k * 24).toFixed(1) + 'deg) translateZ(' + (-Math.abs(k) * 60).toFixed(0) + 'px) rotate(' + (k * 3).toFixed(1) + 'deg)';
      });
      if (best !== active) {
        active = best;
        sfx('card', .22);
        hls.forEach(function (h, i) { h.classList.toggle('is-on', i === best); h.setAttribute('aria-selected', i === best ? 'true' : 'false'); });
        if (cardsCount) cardsCount.textContent = khDigits(best + 1) + ' / ' + khDigits(scards.length);
      }
    };
    cardsEl.addEventListener('scroll', function () { if (!cq) { cq = true; requestAnimationFrame(flow); } }, { passive: true });
    window.addEventListener('resize', flow);
    flow();
    var goTo = function (i) {
      i = Math.max(0, Math.min(scards.length - 1, i));
      var c = scards[i];
      cardsEl.scrollTo({ left: c.offsetLeft - (cardsEl.clientWidth - c.offsetWidth) / 2, behavior: reduce ? 'auto' : 'smooth' });
    };
    hls.forEach(function (h) { h.addEventListener('click', function () { sfx('tap', .3); goTo(+h.getAttribute('data-go')); }); });
    var cp = $('lb2-cards-prev'), cn = $('lb2-cards-next');
    if (cp) cp.addEventListener('click', function () { goTo(active - 1); });
    if (cn) cn.addEventListener('click', function () { goTo(active + 1); });
    // mouse: drag to swipe on desktop
    var md = false, mx0 = 0, sl0 = 0;
    cardsEl.addEventListener('pointerdown', function (e) { if (e.pointerType !== 'mouse') return; md = true; mx0 = e.clientX; sl0 = cardsEl.scrollLeft; cardsEl.style.scrollSnapType = 'none'; });
    window.addEventListener('pointermove', function (e) { if (md) cardsEl.scrollLeft = sl0 - (e.clientX - mx0); });
    window.addEventListener('pointerup', function () { if (!md) return; md = false; cardsEl.style.scrollSnapType = ''; goTo(active); });
  }

  /* ─────────── to-do program: ticks itself as you scroll, tap toggles ─────────── */
  var todo = $('lb2-todo');
  if (todo) {
    var tasks = Array.prototype.slice.call(todo.querySelectorAll('.lb2-task'));
    var todoCount = $('lb2-todo-count');
    var manual = false;
    var updCount = function () { todoCount.textContent = khDigits(todo.querySelectorAll('.lb2-task.is-done').length) + '/' + khDigits(tasks.length); };
    scrollHooks.push(function (vh) {
      if (manual || !todo.classList.contains('is-in')) return;
      var r = todo.getBoundingClientRect();
      var p = (vh * .78 - r.top) / r.height;
      tasks.forEach(function (t, i) {
        if (!t.classList.contains('is-done') && (i + .8) / tasks.length < p) {
          t.classList.add('is-done');
          if (r.bottom > 0) sfx('tap', .16);
          updCount();
        }
      });
    });
    tasks.forEach(function (t) {
      t.addEventListener('click', function () {
        manual = true;
        t.classList.toggle('is-done');
        sfx('pop', .3);
        updCount();
        if (t.classList.contains('is-done')) { var r = t.querySelector('.lb2-check').getBoundingClientRect(); confetti.burst(r.left + 15, r.top + 15, 8); }
      });
    });
  }

  /* ─────────── photo preview: zooms out of the tapped photo (FLIP), swipe / arrows / keys ─────────── */
  var lb = $('lb2-lb');
  var fig = $('lb2-lb-fig'), lbImg = $('lb2-lb-img'), lbCap = $('lb2-lb-cap'), lbCount = $('lb2-lb-count');
  var phs = Array.prototype.slice.call(root.querySelectorAll('.lb2-ph'));
  var list = [], idx = 0, lastFocus = null, lbOpen = false;
  var EASE = 'cubic-bezier(.3,1.25,.4,1)';
  var natural = function (btn) {
    var im = btn.querySelector('img');
    return im && im.naturalWidth ? [im.naturalWidth, im.naturalHeight] : [4, 5];
  };
  var finalRect = function (wh) {
    var vw = window.innerWidth, vh = window.innerHeight, small = vw < 600;
    var maxW = vw - (small ? 24 : 180), maxH = vh - (small ? 200 : 150);
    var k = Math.min(maxW / wh[0], maxH / wh[1]);
    var w = wh[0] * k, h = wh[1] * k;
    return { left: (vw - w) / 2, top: (vh - h) / 2 - (small ? 14 : 0), width: w, height: h };
  };
  var setFig = function (f) { fig.style.left = f.left + 'px'; fig.style.top = f.top + 'px'; fig.style.width = f.width + 'px'; fig.style.height = f.height + 'px'; };
  // transform + clip that make the big figure sit exactly on the thumbnail
  var onThumb = function (f, btn) {
    var t = btn.getBoundingClientRect();
    var s = Math.max(t.width / f.width, t.height / f.height);
    var ix = Math.max(0, (f.width - t.width / s) / 2), iy = Math.max(0, (f.height - t.height / s) / 2);
    return {
      transform: 'translate(' + (t.left + t.width / 2 - f.left - f.width * s / 2).toFixed(1) + 'px,' + (t.top + t.height / 2 - f.top - f.height * s / 2).toFixed(1) + 'px) scale(' + s.toFixed(4) + ')',
      clip: 'inset(' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px ' + iy.toFixed(1) + 'px ' + ix.toFixed(1) + 'px round 14px)'
    };
  };
  var OPEN_CLIP = 'inset(-40px -40px -40px -40px round 0px)';
  var fillText = function () {
    var btn = list[idx], cap = btn.getAttribute('data-cap') || '';
    lbImg.src = btn.getAttribute('data-full');
    lbImg.alt = cap;
    lbCap.textContent = cap;
    lbCap.classList.toggle('lb2-kh', isKh(cap));
    lbCount.textContent = khDigits(idx + 1) + ' / ' + khDigits(list.length);
  };
  var openLb = function (btn) {
    var group = btn.getAttribute('data-group');
    list = phs.filter(function (b) { return b.getAttribute('data-group') === group; });
    idx = Math.max(0, list.indexOf(btn));
    lastFocus = btn; lbOpen = true;
    lb.classList.toggle('single', list.length < 2);
    fillText();
    lb.hidden = false;
    var f = finalRect(natural(btn));
    setFig(f);
    sfx('card', .35);
    if (reduce) { fig.style.transform = 'none'; fig.style.clipPath = OPEN_CLIP; lb.classList.add('show'); $('lb2-lb-x').focus({ preventScroll: true }); return; }
    var st = onThumb(f, btn);
    fig.style.transition = 'none'; fig.style.opacity = '1';
    fig.style.transform = st.transform; fig.style.clipPath = st.clip;
    void fig.offsetWidth;
    fig.style.transition = 'transform .6s ' + EASE + ', clip-path .45s cubic-bezier(.2,.8,.2,1)';
    fig.style.transform = 'none'; fig.style.clipPath = OPEN_CLIP;
    requestAnimationFrame(function () { lb.classList.add('show'); });
    $('lb2-lb-x').focus({ preventScroll: true });
  };
  var closeLb = function () {
    if (!lbOpen) return;
    lbOpen = false;
    lb.classList.remove('show');
    sfx('tap', .25);
    var btn = list[idx], r = btn.getBoundingClientRect();
    var visible = r.bottom > 0 && r.top < window.innerHeight;
    if (!reduce && visible) {
      var f = { left: parseFloat(fig.style.left), top: parseFloat(fig.style.top), width: parseFloat(fig.style.width), height: parseFloat(fig.style.height) };
      var st = onThumb(f, btn);
      fig.style.transition = 'transform .45s cubic-bezier(.5,0,.2,1), clip-path .45s cubic-bezier(.5,0,.2,1)';
      fig.style.transform = st.transform; fig.style.clipPath = st.clip;
    } else {
      fig.style.transition = 'opacity .3s ease, transform .3s ease';
      fig.style.opacity = '0'; fig.style.transform = reduce ? 'none' : 'scale(.9)';
    }
    setTimeout(function () { if (!lbOpen) { lb.hidden = true; fig.style.opacity = ''; } }, 460);
    if (lastFocus) lastFocus.focus({ preventScroll: true });
    if (list[idx] !== lastFocus) list[idx].focus({ preventScroll: true });
  };
  var nav = function (dir) {
    if (list.length < 2) return;
    sfx('card', .3);
    idx = (idx + dir + list.length) % list.length;
    var f = finalRect(natural(list[idx]));
    if (reduce) { fillText(); setFig(f); return; }
    fig.style.transition = 'transform .22s ease-in, opacity .22s ease-in';
    fig.style.transform = 'translateX(' + (-dir * 45) + '%) rotate(' + (-dir * 8) + 'deg) scale(.9)';
    fig.style.opacity = '0';
    setTimeout(function () {
      fillText(); setFig(f);
      fig.style.transition = 'none';
      fig.style.transform = 'translateX(' + (dir * 45) + '%) rotate(' + (dir * 8) + 'deg) scale(.9)';
      void fig.offsetWidth;
      fig.style.transition = 'transform .5s ' + EASE + ', opacity .3s ease';
      fig.style.transform = 'none'; fig.style.opacity = '1';
    }, 220);
  };
  phs.forEach(function (b) { b.addEventListener('click', function () { openLb(b); }); });
  $('lb2-lb-x').addEventListener('click', closeLb);
  $('lb2-lb-prev').addEventListener('click', function () { nav(-1); });
  $('lb2-lb-next').addEventListener('click', function () { nav(1); });
  document.addEventListener('keydown', function (e) {
    if (!lbOpen) return;
    if (e.key === 'Escape') closeLb();
    else if (e.key === 'ArrowRight') nav(1);
    else if (e.key === 'ArrowLeft') nav(-1);
    else if (e.key === 'Tab') { // keep focus inside the preview
      var f = Array.prototype.slice.call(lb.querySelectorAll('button')).filter(function (b) { return b.offsetParent; });
      var i = f.indexOf(document.activeElement);
      if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); } else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
    }
  });
  // swipe left / right (the photo follows the finger), tap outside = close
  var lsx = 0, lsy = 0, ldx = 0, ldown = false;
  lb.addEventListener('pointerdown', function (e) {
    if (e.target.closest('button')) return;
    ldown = true; lsx = e.clientX; lsy = e.clientY; ldx = 0;
  });
  lb.addEventListener('pointermove', function (e) {
    if (!ldown || list.length < 2 || reduce) return;
    ldx = e.clientX - lsx;
    if (Math.abs(ldx) > 6) { fig.style.transition = 'none'; fig.style.transform = 'translateX(' + ldx + 'px) rotate(' + (ldx * .03) + 'deg)'; }
  });
  var lend = function (e) {
    if (!ldown) return;
    ldown = false;
    if (Math.abs(ldx) > 60 && list.length > 1) { nav(ldx < 0 ? 1 : -1); return; }
    if (Math.abs(ldx) > 6) { fig.style.transition = 'transform .4s ' + EASE; fig.style.transform = 'none'; return; }
    if (e && e.type === 'pointerup' && !e.target.closest('.lb2-lb-fig') && Math.abs(e.clientY - lsy) < 10) closeLb();
  };
  lb.addEventListener('pointerup', lend);
  lb.addEventListener('pointercancel', lend);
  window.addEventListener('resize', function () { if (lbOpen) { var f = finalRect(natural(list[idx])); setFig(f); } });

  /* ─────────── map: load the real Google map ─────────── */
  var mapLoad = $('lb2-map-load');
  if (mapLoad) {
    mapLoad.addEventListener('click', function () {
      var map = $('lb2-map');
      var frame = map.querySelector('.lb2-map-frame');
      if (frame && !frame.src) frame.src = frame.getAttribute('data-src');
      map.classList.add('is-live');
      sfx('paper', .4);
    });
  }

  /* ─────────── dress code: tap a swatch, the outfit takes the colour ─────────── */
  var outfit = $('lb2-outfit');
  var swatches = Array.prototype.slice.call(root.querySelectorAll('.lb2-swatch'));
  var pickFit = function (sw, quiet) {
    swatches.forEach(function (s) { s.classList.toggle('is-on', s === sw); s.setAttribute('aria-pressed', s === sw ? 'true' : 'false'); });
    if (outfit) {
      outfit.style.setProperty('--fit', sw.getAttribute('data-hex'));
      outfit.classList.remove('is-bounce'); void outfit.getBoundingClientRect(); outfit.classList.add('is-bounce');
    }
    if (quiet) return;
    sfx('pop', .3);
    var c = center(sw); confetti.burst(c[0], c[1], 8);
  };
  swatches.forEach(function (sw) { sw.addEventListener('click', function () { pickFit(sw); }); });
  if (swatches[0]) pickFit(swatches[0], true);
  // the colours cycle by themselves once, when the card is seen
  onReveal['lb2-dress'] = function () {
    if (reduce || swatches.length < 2) return;
    swatches.forEach(function (sw, i) { setTimeout(function () { if (!manualFit) pickFit(sw, true); }, 900 + i * 450); });
    setTimeout(function () { if (!manualFit) pickFit(swatches[0], true); }, 900 + swatches.length * 450);
  };
  var manualFit = false;
  swatches.forEach(function (sw) { sw.addEventListener('pointerdown', function () { manualFit = true; }); });

  /* ─────────── RSVP: emoji buttons → emoji rain ─────────── */
  var opts = $('lb2-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var card = $('lb2-rsvp-card');
    var form = $('lb2-rsvp-form'), note = $('lb2-rsvp-note');
    var err = $('lb2-rsvp-err');
    var done = $('lb2-rsvp-done');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.lb2-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.lb2-opt').forEach(function (o) { o.setAttribute('aria-checked', o === b ? 'true' : 'false'); o.classList.toggle('dim', o !== b); });
      form.classList.add('show'); err.textContent = '';
      sfx('pop', .4);
      var c = center(b); confetti.burst(c[0], c[1] - 20, 14, chosen === 'yes' ? ['🥳', '🎉'] : ['🥲', '💌']);
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var btn = form.querySelector('button'); btn.disabled = true;
      var finish = function (ok) {
        btn.disabled = false;
        if (!ok) { err.textContent = 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត'; return; }
        var yes = chosen === 'yes';
        $('lb2-done-emoji').textContent = yes ? '🥳' : '🥹';
        $('lb2-rsvp-done-title').textContent = yes ? 'See you there!' : 'Thank you!';
        $('lb2-rsvp-done-text').textContent = yes
          ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ 💖'
          : 'សូមអរគុណដែលបានជូនដំណឹង។ ពរជ័យរបស់លោកអ្នក មានន័យណាស់សម្រាប់យើងខ្ញុំ 🫶';
        sfx('send', .4);
        card.hidden = true; done.hidden = false;
        setTimeout(function () { sfx('chime', .3); }, 350);
        confetti.rain(yes ? ['🥳', '🎉', '💖', '🎊', '🥂', '✨'] : ['🥲', '💌', '🫶', '💗'], yes ? 60 : 36);
      };
      if (!rsvpUrl) { finish(true); return; } // demo preview: nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { finish(r.ok); }).catch(function () { finish(false); });
    });
    $('lb2-rsvp-again').addEventListener('click', function () {
      done.hidden = true; card.hidden = false;
      sfx('tap', .3);
    });
  }

  /* ─────────── gift: the «scan me» sticker peels + copy the account number ─────────── */
  var qr = $('lb2-qr');
  if (qr) {
    qr.addEventListener('click', function () {
      qr.classList.remove('is-peel'); void qr.offsetWidth; qr.classList.add('is-peel');
      sfx('paper', .35);
      var c = center(qr); confetti.burst(c[0], c[1] - 60, 18, ['🎁', '💸', '✨']);
    });
  }
  var toast = $('lb2-toast'), toastT = null;
  var say = function (m) { toast.textContent = m; toast.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(function () { toast.classList.remove('show'); }, 2200); };
  var copyBtn = $('lb2-copy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var v = copyBtn.getAttribute('data-copy');
      var ok = function () { say('បានចម្លងលេខគណនី ✓'); sfx('pop', .35); var c = center(copyBtn); confetti.burst(c[0], c[1], 10); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(v).then(ok, function () { say(v); });
      else say(v);
    });
  }

  /* ─────────── sticker sheet: drag stickers onto the photo ─────────── */
  var board = $('lb2-board');
  if (board) {
    var tray = $('lb2-tray');
    var placeAt = function (item, px, py) { // px, py = 0..1 inside the board
      var s = document.createElement('span');
      s.className = 'lb2-placed' + (item.getAttribute('data-text') ? ' txt' + (isKh(item.getAttribute('data-sticker')) ? ' lb2-kh' : '') : '');
      s.textContent = item.getAttribute('data-sticker');
      s.style.rotate = ((Math.random() - .5) * 30).toFixed(0) + 'deg';
      board.appendChild(s);
      s.style.left = 'calc(' + (px * 100).toFixed(2) + '% - ' + s.offsetWidth / 2 + 'px)';
      s.style.top = 'calc(' + (py * 100).toFixed(2) + '% - ' + s.offsetHeight / 2 + 'px)';
      board.classList.add('has-stickers');
      movable(s);
      sfx('pop', .35);
      var c = center(s); confetti.burst(c[0], c[1], 6);
    };
    var inBoard = function (x, y) { var b = board.getBoundingClientRect(); return x > b.left && x < b.right && y > b.top && y < b.bottom; };
    // a placed sticker: drag it around, drag it off the photo to throw it away
    var movable = function (s) {
      var on = false, ox = 0, oy = 0;
      s.addEventListener('pointerdown', function (e) {
        on = true; s.classList.add('is-dragging'); s.setPointerCapture(e.pointerId);
        var r = s.getBoundingClientRect(); ox = e.clientX - r.left; oy = e.clientY - r.top;
        sfx('tap', .25); e.preventDefault();
      });
      s.addEventListener('pointermove', function (e) {
        if (!on) return;
        var b = board.getBoundingClientRect();
        s.style.left = (e.clientX - b.left - ox) + 'px'; s.style.top = (e.clientY - b.top - oy) + 'px';
      });
      var up = function (e) {
        if (!on) return;
        on = false; s.classList.remove('is-dragging');
        if (e && e.clientX != null && !inBoard(e.clientX, e.clientY)) {
          s.remove(); sfx('tap', .3);
          if (!board.querySelector('.lb2-placed')) board.classList.remove('has-stickers');
        } else sfx('pop', .25);
      };
      s.addEventListener('pointerup', up);
      s.addEventListener('pointercancel', up);
    };
    // tray: tap = drop it somewhere on the photo, drag = put it exactly where you let go
    var ghost = null, gItem = null, gx = 0, gy = 0, gMoved = false;
    tray.addEventListener('pointerdown', function (e) {
      var item = e.target.closest('.lb2-tray-item'); if (!item) return;
      gItem = item; gx = e.clientX; gy = e.clientY; gMoved = false;
      item.setPointerCapture(e.pointerId);
      e.preventDefault();
    });
    tray.addEventListener('pointermove', function (e) {
      if (!gItem) return;
      if (!gMoved && Math.hypot(e.clientX - gx, e.clientY - gy) > 6) {
        gMoved = true;
        ghost = document.createElement('span');
        ghost.className = 'lb2-placed lb2-ghost' + (gItem.getAttribute('data-text') ? ' txt' + (isKh(gItem.getAttribute('data-sticker')) ? ' lb2-kh' : '') : '');
        ghost.textContent = gItem.getAttribute('data-sticker');
        root.appendChild(ghost);
        sfx('tap', .3);
      }
      if (ghost) ghost.style.transform = 'translate(' + (e.clientX - ghost.offsetWidth / 2) + 'px,' + (e.clientY - ghost.offsetHeight / 2) + 'px) scale(1.2)';
    });
    var trayUp = function (e) {
      if (!gItem) return;
      var item = gItem; gItem = null;
      if (ghost) { ghost.remove(); ghost = null; }
      var b = board.getBoundingClientRect();
      if (!gMoved) placeAt(item, .18 + Math.random() * .64, .15 + Math.random() * .6);
      else if (e && inBoard(e.clientX, e.clientY)) placeAt(item, (e.clientX - b.left) / b.width, (e.clientY - b.top) / b.height);
    };
    tray.addEventListener('pointerup', trayUp);
    tray.addEventListener('pointercancel', function () { gItem = null; if (ghost) { ghost.remove(); ghost = null; } });
    tray.addEventListener('keydown', function (e) {
      var item = e.target.closest('.lb2-tray-item');
      if (item && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); placeAt(item, .2 + Math.random() * .6, .2 + Math.random() * .55); }
    });
    $('lb2-board-reset').addEventListener('click', function () {
      board.querySelectorAll('.lb2-placed').forEach(function (s) { s.remove(); });
      board.classList.remove('has-stickers');
      sfx('paper', .35);
    });
  }

  /* ─────────── finale: send love ─────────── */
  var love = $('lb2-love');
  if (love) {
    var loveN = 0, loveB = $('lb2-love-n');
    love.addEventListener('click', function () {
      loveN++;
      loveB.hidden = false;
      loveB.textContent = '×' + khDigits(loveN);
      sfx(loveN % 5 === 0 ? 'chime' : 'pop', .35);
      var c = center(love);
      confetti.burst(c[0], c[1] - 10, 24, ['💖', '💕', '🥰', '✨', '💍']);
      if (loveN % 5 === 0) confetti.rain(['💖', '💕', '💗', '🥰'], 40);
    });
  }
})();

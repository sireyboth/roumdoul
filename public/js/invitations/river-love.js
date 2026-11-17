/* River Love — resources/views/invitations/templates/anniversary-category/river-love.blade.php */
(function () {
  'use strict';

  var root = document.querySelector('.rl');
  if (!root) return;

  var config = {};
  try { config = JSON.parse(document.getElementById('rl-config').textContent) || {}; } catch (e) { config = {}; }

  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ─────────── cover video ─────────── */
  var video = document.getElementById('rlVideo');
  if (video) {
    // portrait screens get the portrait cut; <source media> picks it on load, this keeps it right on rotate
    var portraitMq = window.matchMedia && matchMedia('(orientation: portrait)');
    var pickCut = function () {
      var cut = portraitMq && portraitMq.matches ? 'portrait' : 'landscape';
      var src = video.getAttribute('data-' + cut);
      if (!src || video.getAttribute('data-cut') === cut) return;
      var first = !video.hasAttribute('data-cut');
      video.setAttribute('data-cut', cut);
      video.poster = video.getAttribute('data-' + cut + '-poster') || video.poster;
      if (first && video.currentSrc && video.currentSrc.indexOf(src.split('?')[0]) !== -1) return; // already right
      while (video.firstChild) video.removeChild(video.firstChild);
      video.src = src;
      video.load();
      if (!reduce) { var p = video.play(); if (p && p.catch) p.catch(function () {}); }
    };
    pickCut();
    if (portraitMq) {
      if (portraitMq.addEventListener) portraitMq.addEventListener('change', pickCut);
      else if (portraitMq.addListener) portraitMq.addListener(pickCut);
    }

    if (reduce) {
      video.removeAttribute('autoplay');
      video.pause();
    } else {
      // some phones (low-power mode) refuse autoplay: the poster stays, and the first tap starts it
      var tryPlay = function () { var p = video.play(); if (p && p.catch) p.catch(function () {}); };
      tryPlay();
      document.addEventListener('touchstart', tryPlay, { once: true, passive: true });
      document.addEventListener('visibilitychange', function () { if (!document.hidden) tryPlay(); });
    }
  }

  /* ─────────── falling petals & leaves in 3D (infinite) ───────────
     Each petal/leaf is a flat shape spinning on all three axes. Its rotation matrix maps straight onto a
     2D canvas transform (a flat shape seen in 3D = an affine transform), and the same matrix's normal
     lights it: the side facing the light glows, the back is darker, edge-on it almost disappears.
     Depth (z) sets size, speed, fade and focus blur; scrolling and touch push the near ones more. */
  var dropPetals = null; // set below: lets a tap drop petals into the 3D fall
  var canvas = document.getElementById('rlFall');
  if (canvas && canvas.getContext && !reduce) {
    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var W = 0, H = 0, items = [];

    var hex = (getComputedStyle(root).getPropertyValue('--rl-accent') || '#e8789a').trim();
    var rgb = /^#([0-9a-f]{6})$/i.test(hex)
      ? [parseInt(hex.slice(1, 3), 16), parseInt(hex.slice(3, 5), 16), parseInt(hex.slice(5, 7), 16)]
      : [232, 120, 154];
    var tint = function (c, t) { // t > 0 → towards white, t < 0 → towards a deep plum
      return 'rgb(' + c.map(function (v, i) {
        return Math.round(t >= 0 ? v + (255 - v) * t : v + ([70, 10, 40][i] - v) * -t);
      }).join(',') + ')';
    };
    var rand = function (a, b) { return a + Math.random() * (b - a); };

    /* ---- sprites, drawn once: [front, back, light, shadow] for each kind ---- */
    var SP = 96; // sprite size (px); shapes are drawn in a -1..1 box scaled to it
    var sprite = function (draw, blur) {
      var c = document.createElement('canvas');
      c.width = c.height = SP;
      var g = c.getContext('2d');
      if (blur && 'filter' in g) g.filter = 'blur(' + blur + 'px)';
      g.translate(SP / 2, SP / 2);
      g.scale(SP * .38, SP * .38);
      draw(g);
      return c;
    };
    var petalPath = function (g) { // cherry-blossom petal, tip notch at the top
      g.beginPath();
      g.moveTo(0, 1);
      g.bezierCurveTo(-.95, .5, -.9, -.62, -.24, -1);
      g.quadraticCurveTo(-.08, -.86, 0, -.7);
      g.quadraticCurveTo(.08, -.86, .24, -1);
      g.bezierCurveTo(.9, -.62, .95, .5, 0, 1);
      g.closePath();
    };
    var leafPath = function (g) {
      g.beginPath();
      g.moveTo(0, -1.05);
      g.bezierCurveTo(.62, -.6, .6, .5, 0, 1);
      g.bezierCurveTo(-.6, .5, -.62, -.6, 0, -1.05);
      g.closePath();
    };
    var petalArt = function (base, back) {
      return function (g) {
        petalPath(g);
        var gr = g.createRadialGradient(0, .95, .05, 0, .1, 1.25); // deeper colour at the base, pale at the rim
        gr.addColorStop(0, tint(base, back ? -.25 : -.05));
        gr.addColorStop(.45, tint(base, back ? .15 : .35));
        gr.addColorStop(1, tint(base, back ? .45 : .82));
        g.fillStyle = gr; g.fill();
        g.save(); g.clip(); // fine veins
        g.strokeStyle = tint(base, back ? -.15 : .05); g.globalAlpha = .22; g.lineWidth = .025;
        for (var k = -2; k <= 2; k++) { g.beginPath(); g.moveTo(0, .95); g.quadraticCurveTo(k * .22, .1, k * .3, -.8); g.stroke(); }
        g.restore();
      };
    };
    var leafArt = function (base, back) {
      return function (g) {
        leafPath(g);
        var gr = g.createLinearGradient(-.6, -1, .6, 1);
        gr.addColorStop(0, back ? '#9fbf86' : '#bfe39a');
        gr.addColorStop(.55, base);
        gr.addColorStop(1, back ? '#4f7a3c' : '#5f9444');
        g.fillStyle = gr; g.fill();
        g.strokeStyle = back ? 'rgba(230,245,210,.35)' : 'rgba(255,255,240,.6)'; g.lineWidth = .045;
        g.beginPath(); g.moveTo(0, -.95); g.quadraticCurveTo(.05, 0, 0, 1.1); g.stroke(); // midrib + stem
        g.lineWidth = .025;
        for (var k = 0; k < 4; k++) {
          var y = -.6 + k * .38;
          g.beginPath(); g.moveTo(0, y + .15); g.quadraticCurveTo(.2, y, .4, y - .12); g.stroke();
          g.beginPath(); g.moveTo(0, y + .15); g.quadraticCurveTo(-.2, y, -.4, y - .12); g.stroke();
        }
      };
    };
    var solid = function (path, color) { return function (g) { path(g); g.fillStyle = color; g.fill(); }; };

    var kinds = [];
    [.0, .2, .4].forEach(function (shift) {          // three petal tones from the accent colour
      var base = rgb.map(function (v) { return Math.round(v + (255 - v) * shift); });
      kinds.push({ leaf: false, art: [petalArt(base, false), petalArt(base, true), solid(petalPath, '#fff'), solid(petalPath, '#3c1426')] });
    });
    ['#8cc068', '#79ad57'].forEach(function (c) {      // two greens
      kinds.push({ leaf: true, art: [leafArt(c, false), leafArt(c, true), solid(leafPath, '#fffbe8'), solid(leafPath, '#1d3315')] });
    });
    // 0 = sharp, 1 = soft (far), 2 = very soft (bokeh, very near)
    kinds.forEach(function (k) {
      k.sp = [0, 1.6, 4].map(function (b) { return k.art.map(function (a) { return sprite(a, b); }); });
    });

    var LIGHT = (function () { var l = [-.45, -.6, .66], n = Math.hypot(l[0], l[1], l[2]); return [l[0] / n, l[1] / n, l[2] / n]; })();

    var spawn = function (p, fromTop) {
      var leaf = Math.random() < .24;
      var pool = kinds.filter(function (k) { return k.leaf === leaf; });
      p.kind = pool[(Math.random() * pool.length) | 0];
      // depth: mostly middle distance, a few far, a rare big one right in front of the lens
      var r = Math.random();
      p.z = r < .12 ? rand(.92, 1) : r < .4 ? rand(0, .3) : rand(.3, .9);
      p.size = (leaf ? 19 : 16) * (.45 + p.z * 1.35) * rand(.85, 1.2);
      p.focus = p.z > .9 ? 2 : p.z < .22 ? 1 : 0;
      p.alpha = p.z > .9 ? .55 : .5 + p.z * .5;
      p.x = rand(-40, W + 40);
      p.y = fromTop ? rand(-H * .2, -30) : rand(-30, H);
      p.fall = rand(.45, .8) * (.5 + p.z * 1.1);
      p.drift = rand(.05, .3) * (.5 + p.z);
      p.swing = rand(.6, 1.6);
      p.swingSpeed = rand(.008, .018);
      p.phase = rand(0, Math.PI * 2);
      // tilt (x) rocks back and forth like a falling petal; turn (y) is a slow spin, so most of the time it
      // shows its face and every few seconds it rolls over; z is the gentle in-plane twirl
      p.tilt = rand(.5, 1.1); p.tiltSpeed = rand(.012, .026); p.tiltPhase = rand(0, Math.PI * 2);
      p.ay = rand(0, Math.PI * 2); p.az = rand(0, Math.PI * 2);
      p.vay = rand(.006, .022) * (Math.random() < .5 ? -1 : 1);
      p.vaz = rand(-.014, .014);
      p.ax = 0;
      p.pushX = 0; p.pushY = 0;
      return p;
    };

    var sortByDepth = function () { items.sort(function (a, b) { return a.z - b.z; }); };
    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
      var want = Math.max(24, Math.min(W < 640 ? 40 : 70, Math.round((W * H) / 19000)));
      while (items.length < want) items.push(spawn({}, false));
      if (items.length > want) items.length = want;
      sortByDepth();
    };

    // scroll wind: when the page moves, near petals move with it more than far ones (parallax)
    var lastScroll = window.scrollY;
    window.addEventListener('scroll', function () {
      var d = window.scrollY - lastScroll;
      lastScroll = window.scrollY;
      for (var i = 0; i < items.length; i++) items[i].pushY -= d * (.15 + items[i].z * .55);
    }, { passive: true });
    // a finger or mouse brushes nearby petals aside
    var pointer = null;
    window.addEventListener('pointermove', function (e) { pointer = { x: e.clientX, y: e.clientY, vx: e.movementX || 0, vy: e.movementY || 0 }; }, { passive: true });

    // petals dropped by a tap: they pop up and out, then fall like the others and are gone
    var drops = [];
    dropPetals = function (x, y, scale) {
      var petals = kinds.filter(function (k) { return !k.leaf; }), leaves = kinds.filter(function (k) { return k.leaf; });
      var n = 7 + ((Math.random() * 3) | 0);
      for (var i = 0; i < n; i++) {
        var leaf = i >= n - (1 + ((Math.random() * 2) | 0)); // the last one or two are leaves
        var p = spawn({}, false);
        var pool = leaf ? leaves : petals;
        p.kind = pool[(Math.random() * pool.length) | 0];
        p.z = rand(.55, .85); p.focus = 0; p.alpha = .95;
        p.size = (leaf ? 18 : 15) * scale * rand(.85, 1.15);
        var ang = (i / n) * Math.PI * 2 + rand(-.3, .3), r = rand(8, 22) * scale;
        p.x = x + Math.cos(ang) * r; p.y = y + Math.sin(ang) * r;
        p.pushX = Math.cos(ang) * rand(1.4, 3.2);
        p.pushY = Math.sin(ang) * rand(1, 2.4) - rand(2.2, 3.6);  // a little hop up before gravity wins
        p.fall = rand(.9, 1.5);
        p.vay *= 2.2; p.vaz *= 2;                                    // fresh petals spin harder at first
        drops.push(p);
      }
      if (drops.length > 160) drops.splice(0, drops.length - 160);
    };

    var t = 0;
    // move one petal and draw it; returns false once it has left the screen
    var step = function (p) {
      p.ax = Math.sin(t * p.tiltSpeed + p.tiltPhase) * p.tilt; p.ay += p.vay; p.az += p.vaz;

      // rotation matrix R = Rz · Ry · Rx
      var cx = Math.cos(p.ax), sx = Math.sin(p.ax), cy = Math.cos(p.ay), sy = Math.sin(p.ay), cz = Math.cos(p.az), sz = Math.sin(p.az);
      var r00 = cz * cy, r01 = cz * sy * sx - sz * cx, r02 = cz * sy * cx + sz * sx;
      var r10 = sz * cy, r11 = sz * sy * sx + cz * cx, r12 = sz * sy * cx - cz * sx;
      var r22 = cy * cx;

      // a leaf lying flat catches the air: it falls slower and glides sideways; edge-on it drops
      var flat = Math.abs(r12);
      var sway = Math.sin(t * p.swingSpeed + p.phase);
      p.y += p.fall * (1.25 - flat * .6) + p.pushY;
      p.x += p.drift + sway * p.swing * (.4 + flat * .8) + p.pushX;
      p.pushX *= .92; p.pushY *= .88;

      if (pointer) {
        var dx = p.x - pointer.x, dy = p.y - pointer.y, dist = dx * dx + dy * dy;
        if (dist < 14400) { var f = (1 - dist / 14400) * (.2 + p.z * .5); p.pushX += pointer.vx * f * .12 + Math.sign(dx) * f; p.pushY += pointer.vy * f * .12; }
      }
      if (p.y > H + 60 || p.y < -H * .6 || p.x < -120 || p.x > W + 120) return false;

      // lighting from the face normal (third column); back face shows the back sprite
      var nx = r02, ny = r12, nz = r22;
      var front = nz >= 0;
      var lam = (nx * LIGHT[0] + ny * LIGHT[1] + nz * LIGHT[2]) * (front ? 1 : -1);
      var edge = Math.abs(nz);

      var s = p.size / (SP * .38);
      ctx.setTransform(r00 * s * dpr, r10 * s * dpr, r01 * s * dpr, r11 * s * dpr, p.x * dpr, p.y * dpr);
      var sp = p.kind.sp[p.focus];
      var o = -SP / 2;
      ctx.globalAlpha = p.alpha * (.55 + .45 * Math.min(1, edge * 1.8)); // edge-on = thin and faint
      ctx.drawImage(sp[front ? 0 : 1], o, o);
      if (lam > .35) { ctx.globalAlpha = p.alpha * (lam - .35) * .75; ctx.drawImage(sp[2], o, o); }          // the glint
      var dark = (1 - Math.max(0, lam)) * .32;
      if (dark > .02) { ctx.globalAlpha = p.alpha * dark; ctx.drawImage(sp[3], o, o); }                   // turned away
      return true;
    };
    var frame = function () {
      t++;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var i = 0; i < items.length; i++) if (!step(items[i])) spawn(items[i], true); // recycle → falls forever
      for (var j = drops.length - 1; j >= 0; j--) if (!step(drops[j])) drops.splice(j, 1); // fallen → gone
      ctx.globalAlpha = 1;
      requestAnimationFrame(frame);
    };

    resize();
    window.addEventListener('resize', resize);
    requestAnimationFrame(frame);
  }

  /* ─────────── reveal on scroll ─────────── */
  var reveals = root.querySelectorAll('.rl-rv');
  if (!reduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
      });
    }, { threshold: .15, rootMargin: '0px 0px -8% 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-in'); });
  }

  // the letter's photo stack: the prints fly in once, when a third of it is on screen
  root.querySelectorAll('.rl-l-prints').forEach(function (stack) {
    if (reduce || !('IntersectionObserver' in window)) { stack.classList.add('is-flown'); return; }
    var fio = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) return;
      stack.classList.add('is-flown');
      fio.disconnect();
    }, { threshold: .33 });
    fio.observe(stack);
  });

  // the photo reels only slide while they're on screen
  var reel = root.querySelector('.rl-reel');
  if (reel && 'IntersectionObserver' in window) {
    reel.classList.add('is-off');
    new IntersectionObserver(function (entries) {
      reel.classList.toggle('is-off', !entries[0].isIntersecting);
    }, { rootMargin: '120px 0px' }).observe(reel);
  }

  // smooth in-page links (respecting reduced motion)
  root.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a) return;
    var target = document.querySelector(a.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
  });

  /* ─────────── days together: count up when seen ─────────── */
  var days = root.querySelector('.rl-days b[data-count]');
  if (days && !reduce && 'IntersectionObserver' in window) {
    var target = parseInt(days.getAttribute('data-count'), 10) || 0;
    days.textContent = '0';
    var dio = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) return;
      dio.disconnect();
      var start = null, dur = 2200;
      var step = function (now) {
        if (start === null) start = now; // time from the first frame: a frame stamped before "now" would count below 0
        var k = Math.min(1, Math.max(0, (now - start) / dur)), e = 1 - Math.pow(1 - k, 3);
        days.textContent = Math.round(target * e).toLocaleString('en-US');
        if (k < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    }, { threshold: .5 });
    dio.observe(days);
  }

  /* ─────────── countdown ─────────── */
  var count = document.getElementById('rlCount');
  if (count && config.countdown) {
    var end = new Date(config.countdown).getTime();
    var cells = {};
    count.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
    var tick = function () {
      var left = Math.max(0, end - Date.now());
      cells.d.textContent = Math.floor(left / 864e5);
      cells.h.textContent = Math.floor(left / 36e5) % 24;
      cells.m.textContent = Math.floor(left / 6e4) % 60;
      cells.s.textContent = Math.floor(left / 1e3) % 60;
    };
    tick();
    setInterval(tick, 1000);
  }

  /* ─────────── birdsong: a gapless loop, from the first tap ───────────
     Web Audio loops a decoded buffer with no gap (an <audio loop> clicks at the seam on most phones).
     Browsers block sound until the guest touches the page, so it starts on the first tap/key. While
     «Play our song» is on, the birds duck down to a whisper; the sound button mutes everything. */
  var soundBtn = document.getElementById('rlSound');
  var muted = false;
  var birds = (function () {
    var BIRDS = .55, DUCKED = .12;          // birdsong volume alone / under the song
    var AC = window.AudioContext || window.webkitAudioContext;
    var ac = null, gain = null, el = null, started = false, ducked = false;
    var target = function () { return muted ? 0 : (ducked ? DUCKED : BIRDS); };
    var apply = function (secs) {
      if (gain) {
        var now = ac.currentTime;
        gain.gain.cancelScheduledValues(now);
        gain.gain.setValueAtTime(gain.gain.value, now);
        gain.gain.linearRampToValueAtTime(target(), now + secs);
      } else if (el) {                       // fallback: <audio>, stepped fade
        var from = el.volume, to = target(), steps = 20, i = 0;
        clearInterval(el._fade);
        el._fade = setInterval(function () { i++; el.volume = from + (to - from) * (i / steps); if (i >= steps) clearInterval(el._fade); }, secs * 1000 / steps);
      }
    };
    var start = function () {
      if (started || !config.birds) return;
      started = true;
      if (AC) {
        ac = new AC();
        gain = ac.createGain();
        gain.gain.value = 0;
        gain.connect(ac.destination);
        if (ac.state === 'suspended') ac.resume();
        fetch(config.birds).then(function (r) { return r.arrayBuffer(); })
          .then(function (buf) { return new Promise(function (ok, no) { ac.decodeAudioData(buf, ok, no); }); })
          .then(function (decoded) {
            var src = ac.createBufferSource();
            src.buffer = decoded;
            src.loop = true;
            src.connect(gain);
            src.start();
            apply(2.5);                       // fade the birds in
          })
          .catch(function () { gain = null; ac = null; fallback(); });
      } else {
        fallback();
      }
    };
    var fallback = function () {
      el = new Audio(config.birds);
      el.loop = true;
      el.volume = 0;
      var p = el.play();
      if (p && p.catch) p.catch(function () {});
      apply(2.5);
    };
    // pause in a background tab, carry on when the guest comes back
    document.addEventListener('visibilitychange', function () {
      if (ac) { if (document.hidden) ac.suspend(); else ac.resume(); }
      else if (el) { if (document.hidden) el.pause(); else { var p = el.play(); if (p && p.catch) p.catch(function () {}); } }
    });
    return {
      start: start,
      duck: function (on) { ducked = on; apply(1.2); },
      refresh: function () { apply(.6); },
    };
  })();
  var firstTouch = function () {
    ['pointerdown', 'keydown', 'touchend'].forEach(function (e) { document.removeEventListener(e, firstTouch, true); });
    if (!muted) birds.start();
  };
  ['pointerdown', 'keydown', 'touchend'].forEach(function (e) { document.addEventListener(e, firstTouch, true); });

  /* ─────────── music (nothing downloads until Play) ─────────── */
  var playBtn = document.getElementById('rlPlay');
  var stopSong = function () {};
  if (playBtn) {
    var txt = document.getElementById('rlPlayTxt');
    var label = txt.textContent;
    var audio = document.getElementById('rlAudio');
    var yt = null, playing = false;
    var set = function (on) {
      playing = on;
      playBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
      txt.textContent = on ? 'Pause' : label;
      birds.duck(on);                        // the song plays → birds go quiet; paused → they come back
    };
    stopSong = function () {
      if (!playing) return;
      if (audio) audio.pause();
      if (yt) { yt.remove(); yt = null; }
      set(false);
    };
    playBtn.addEventListener('click', function () {
      if (muted) setMuted(false);           // pressing play means they want sound
      if (audio) {
        if (playing) { audio.pause(); set(false); return; }
        var p = audio.play();
        if (p && p.catch) p.catch(function () { set(false); });
        set(true);
      } else if (config.music && config.music.youtube) {
        if (playing && yt) { yt.remove(); yt = null; set(false); return; }
        yt = document.createElement('iframe');
        yt.src = 'https://www.youtube.com/embed/' + config.music.youtube + '?autoplay=1&loop=1&playlist=' + config.music.youtube + '&controls=0';
        yt.allow = 'autoplay; encrypted-media';
        yt.title = 'Our song';
        yt.setAttribute('aria-hidden', 'true');
        yt.style.cssText = 'position:fixed;width:1px;height:1px;left:-9999px;bottom:0;border:0;';
        document.body.appendChild(yt);
        set(true);
      }
    });
    if (audio) audio.addEventListener('ended', function () { set(false); });
  }

  /* ─────────── sound on / off (birds + song) ─────────── */
  function setMuted(on) {
    muted = on;
    if (soundBtn) {
      soundBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
      soundBtn.setAttribute('aria-label', on ? 'Turn sound on' : 'Mute sound');
    }
    if (on) stopSong();
    else birds.start();
    birds.refresh();
    if (window.InvScratch) window.InvScratch.setMuted(on); // the scratch cards' sounds follow the sound button
  }
  if (soundBtn) {
    soundBtn.addEventListener('click', function () { setMuted(!muted); });
  }

  /* ─────────── a tap drops petals ───────────
     where the guest taps, a handful of petals (and a leaf or two) drop into the 3D petal fall.
     click (not pointerdown) so a scroll or swipe never sets it off; keyboard clicks (no position) skip it.
     dropPetals stays null when the petals are off (reduced motion). */
  document.addEventListener('click', function (e) {
    if (!dropPetals || !e.detail || (e.clientX === 0 && e.clientY === 0)) return;
    dropPetals(e.clientX, e.clientY, 0.85 + Math.random() * 0.3);
  }, true);

  /* ─────────── scratch cards (shared component): a revealed card bursts into petals ─────────── */
  root.addEventListener('inv-scratch:reveal', function (e) {
    if (!dropPetals) return;
    var r = e.target.getBoundingClientRect();
    dropPetals(r.left + r.width / 2, r.top + r.height * .45, 1.2);
  });

    /* ─────────── photo preview ─────────── */
  var lb = document.getElementById('rlLb');
  if (lb) {
    var img = document.getElementById('rlLbImg'), cap = document.getElementById('rlLbCap'), last = null;
    var close = function () { lb.hidden = true; img.removeAttribute('src'); if (last) last.focus(); };
    root.addEventListener('click', function (e) {
      var b = e.target.closest('.rl-ph-btn');
      if (!b) return;
      last = b;
      img.src = b.getAttribute('data-full');
      cap.textContent = b.getAttribute('data-cap') || '';
      lb.hidden = false;
      document.getElementById('rlLbX').focus();
    });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.closest('.rl-lb-x')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lb.hidden) close(); });
  }
})();

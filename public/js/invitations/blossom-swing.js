/* Blossom Swing — resources/views/invitations/templates/wedding/blossom-swing.blade.php (CSS: public/css/invitations/blossom-swing.css)

   Every [data-swing] garland is a little branch of nested .bs-bone pieces (one per flower cluster): the middle cluster
   is the root, each outer cluster hangs off its neighbour and turns round the joint they share. Angles are RELATIVE to
   the parent, so they add up along the branch: the ends swing widest and the motion rolls outward like a whip.

   Bloom: a garland starts as a folded bud (pieces small, turned in towards the middle) and opens outward from the
   middle when it first comes into view — each ring of the branch a beat later, overshooting its rest a little and
   settling back like a flower unfolding. Then it keeps breathing (a slow, slight open / close).

   Swing, per bone:
     · a breeze: three sines with unrelated periods (6.2 s, 3.7 s, 2.3 s) so the loop never visibly repeats; outer bones
       feel the same wind a little later (the wave travels down the branch), and the wind itself rises and falls (14 s);
     · plus a soft damped spring kicked by gusts: a tap, a finger brushing through, a fast scroll, opening, RSVP, «send
       love». A gust reaches the far pieces a little later than the near ones;
     · and a small skew that trails the angle, so the tips bend instead of tilting stiffly.
   Only garlands on screen are animated (and only they get will-change); nothing moves with reduced motion. */
(function () {
  'use strict';
  var root = document.querySelector('.bs');
  if (!root || !window.InviteKit) return;
  root.classList.add('js');

  var cover = document.getElementById('bs-cover');
  var kit = InviteKit({ root: root, cover: !!cover });
  var reduce = kit.reduce;

  /* ───────── swing + bloom engine ───────── */
  var TAU = Math.PI * 2;
  var clamp01 = function (x) { return x < 0 ? 0 : x > 1 ? 1 : x; };
  var smooth = function (x) { x = clamp01(x); return x * x * (3 - 2 * x); };
  var bloomEase = function (x) {                 // gentle easeOutBack: opens ~6% past rest, settles back
    x = clamp01(x);
    var c = 1.2, u = x - 1;
    return 1 + (c + 1) * u * u * u + c * u * u;
  };
  var BLOOM_MS = 1700, RING_MS = 280;

  var swings = [].map.call(root.querySelectorAll('[data-swing]'), function (el) {
    var top = el.getAttribute('data-swing') === 'top';
    var bones = [].map.call(el.querySelectorAll('.bs-bone'), function (b) {
      var depth = parseInt(b.getAttribute('data-depth'), 10) || 0;
      var x = parseFloat(b.getAttribute('data-x'));
      return {
        el: b, petal: b.firstElementChild, depth: depth,
        side: parseInt(b.getAttribute('data-side'), 10) || 0,
        x: isNaN(x) ? .5 : x,
        amp: depth === 0 ? (top ? 2.6 : 1.8) : Math.max(1, (top ? 2.3 : 1.7) - (depth - 1) * .2),   // degrees, relative
        seed: Math.random() * 6, p: 0, v: 0, lag: 0, kicks: [], shown: -1
      };
    });
    return { el: el, top: top, bones: bones, visible: false, bloomAt: null, inCover: !!(cover && cover.contains(el)) };
  });

  var gust = function (sw, x0, strength) {
    if (reduce || !sw) return;
    var now = performance.now();
    sw.bones.forEach(function (b) {
      b.kicks.push({ at: now + b.depth * 140 + Math.abs(b.x - x0) * 200, f: strength * (2.4 + Math.random() * 1.1) });
    });
  };
  var gustAll = function (strength, onlyVisible) {
    swings.forEach(function (sw) { if (!onlyVisible || sw.visible) gust(sw, Math.random() < .5 ? 0 : 1, strength); });
  };
  var bloom = function (sw, delay) {
    if (sw.bloomAt !== null) return;
    sw.bloomAt = performance.now() + (delay || 0);
    // a soft rustle as it opens (silent until the guest's first tap, like every sfx)
    if (!sw.inCover) setTimeout(function () { rustle(.16); }, (delay || 0) + 150);
  };

  if (reduce || !swings.length) {
    root.classList.remove('bs-bud');
  } else {
    swings.forEach(function (sw) { sw.bones.forEach(function (b) { b.petal.style.opacity = '0'; }); });
    root.classList.remove('bs-bud');
    root.classList.add('bs-live');

    var setVisible = function (sw, on) {
      sw.visible = on;
      sw.el.classList.toggle('is-live', on);
      if (on && (sw.inCover || root.classList.contains('is-open'))) bloom(sw, sw.top ? 80 : 300);
    };
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          swings.forEach(function (sw) { if (sw.el === e.target) setVisible(sw, e.isIntersecting); });
        });
      }, { rootMargin: '60px 0px' });
      swings.forEach(function (sw) { io.observe(sw.el); });
    } else {
      swings.forEach(function (sw) { setVisible(sw, true); });
    }

    var last = performance.now(), t0 = last;
    var frame = function (now) {
      var dt = Math.min(.05, (now - last) / 1000);
      last = now;
      var t = (now - t0) / 1000;
      var breeze = .58 + .42 * Math.sin(t * TAU / 14 + 1.1);       // the wind itself comes and goes
      for (var k = 0; k < swings.length; k++) {
        var sw = swings[k];
        if (!sw.visible || sw.bloomAt === null) continue;
        var since = now - sw.bloomAt;
        for (var j = 0; j < sw.bones.length; j++) {
          var b = sw.bones[j];
          // bloom: this ring opens RING_MS after the one it hangs from
          var g = clamp01((since - b.depth * RING_MS) / BLOOM_MS);
          var open = bloomEase(g), settle = smooth(g);
          if (b.kicks.length) {
            for (var q = b.kicks.length - 1; q >= 0; q--) if (b.kicks[q].at <= now) { b.v += b.kicks[q].f; b.kicks.splice(q, 1); }
          }
          // soft spring: ~2 s period, settles in ~3.5 s (semi-implicit Euler, stable at any frame rate here)
          b.v += (-10 * b.p - 1.25 * b.v) * dt;
          b.p += b.v * dt;
          if (b.p > 2.6) { b.p = 2.6; b.v *= -.3; } else if (b.p < -2.6) { b.p = -2.6; b.v *= -.3; }
          var lt = t - b.depth * .42;                                  // outer pieces feel the wind later
          var wind = .55 * Math.sin(lt * TAU / 6.2 + b.seed * .3)
                   + .30 * Math.sin(lt * TAU / 3.7 + b.seed)
                   + .15 * Math.sin(lt * TAU / 2.3 + b.seed * 2);
          var a = b.amp * (wind * breeze + b.p) * (.25 + .75 * settle);
          var rot = sw.top ? -a : a;                                    // one wind direction for hanging + growing flowers
          b.lag += (rot - b.lag) * Math.min(1, dt * 2.4);
          var skew = (rot - b.lag) * .35;
          var unfurl = b.side ? -b.side * (sw.top ? 16 : 9) * (1 - settle) : 0;   // folded in towards the middle, opening out
          var breath = 1 + (b.depth ? .014 : .01) * Math.sin(t * TAU / 6.8 + b.seed);
          var scale = (.3 + .7 * open) * breath;
          b.el.style.transform = 'rotate(' + (rot + unfurl).toFixed(3) + 'deg) skewX(' + skew.toFixed(3) + 'deg) scale(' + scale.toFixed(4) + ')';
          var op = g >= .6 ? 1 : smooth(g / .6);
          if (op !== b.shown) { b.petal.style.opacity = op.toFixed(3); b.shown = op; }
        }
      }
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
  }

  /* touch the flowers: a tap sends a gust from that spot, a finger / mouse brushing through stirs them */
  var lastRustle = 0;
  var rustle = function (vol) {
    var now = performance.now();
    if (now - lastRustle < 450) return;
    lastRustle = now;
    kit.sfx('rustle', vol);
  };
  swings.forEach(function (sw) {
    var lastX = null, lastT = 0;
    var at = function (e) { var r = sw.el.getBoundingClientRect(); return clamp01((e.clientX - r.left) / (r.width || 1)); };
    sw.el.addEventListener('pointerdown', function (e) {
      gust(sw, at(e), 1.1);
      rustle(.32);
      lastX = e.clientX; lastT = performance.now();
    });
    sw.el.addEventListener('pointermove', function (e) {
      var now = performance.now();
      if (lastX === null) { lastX = e.clientX; lastT = now; return; }
      var speed = Math.abs(e.clientX - lastX) / Math.max(16, now - lastT);   // px per ms
      lastX = e.clientX; lastT = now;
      if (speed > .6) { gust(sw, at(e), Math.min(.9, speed * .35)); rustle(.18); }
    });
    sw.el.addEventListener('pointerleave', function () { lastX = null; });
  });

  /* a fast scroll is a breath of wind for the garlands on screen */
  var lastScrollGust = 0;
  kit.onScroll(function (s) {
    var v = Math.abs(s.velocity), now = performance.now();
    if (v > 14 && now - lastScrollGust > 700) {
      lastScrollGust = now;
      gustAll(Math.min(.8, v / 60), true);
    }
  });

  /* ───────── gallery: dotted frames ─────────
     an SVG line of round dots (pink → gold) through the middle of each photo's mat, following its shape: rounded
     corners, or the arched top of the wide photos (CSS shrinks 999px radii so the top becomes a half circle the width
     of the box). Drawn in pixels and redrawn on resize, so the dots never stretch. */
  var dotSeq = 0, SVGNS = 'http://www.w3.org/2000/svg';
  var dotAccent = (getComputedStyle(root).getPropertyValue('--bs-accent') || '').trim() || '#d0668c';
  var drawDots = function (b) {
    var w = b.offsetWidth, h = b.offsetHeight;
    if (!w || !h) return;
    var svg = b.querySelector('svg.bs-dots');
    if (!svg) {
      var id = 'bs-dg' + (++dotSeq);
      svg = document.createElementNS(SVGNS, 'svg');
      svg.setAttribute('class', 'bs-dots');
      svg.setAttribute('aria-hidden', 'true');
      // the dots stay still; each stop's colour cycles rose → gold → pale gold → rose, a third of a cycle apart, so the
      // pink and gold slowly flow round the frame (none with reduced motion)
      var cycle = [dotAccent, '#d9b36c', '#f3d9a0', dotAccent].join(';');
      var stop = function (off, phase) {
        return '<stop offset="' + off + '" stop-color="' + dotAccent + '">'
          + (reduce ? '' : '<animate attributeName="stop-color" values="' + cycle + '" dur="6s" begin="-' + phase + 's" repeatCount="indefinite"/>')
          + '</stop>';
      };
      svg.innerHTML = '<defs><linearGradient id="' + id + '" gradientUnits="userSpaceOnUse">'
        + stop(0, 0) + stop(.5, 2) + stop(1, 4)
        + '</linearGradient></defs><path stroke="url(#' + id + ')"/>';
      b.appendChild(svg);
    }
    svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
    var g = svg.querySelector('linearGradient');
    g.setAttribute('x1', 0); g.setAttribute('y1', 0); g.setAttribute('x2', w); g.setAttribute('y2', h);
    // a rounded rectangle 3.5px in (through the middle of the 7px mat); corner = the box's radius minus that inset
    // (wide photos 25px, the others 18px)
    var i = 3.5, c = (b.classList.contains('wide') ? 25 : 18) - i;
    var d = 'M' + (i + c) + ',' + i + 'H' + (w - i - c) + 'A' + c + ',' + c + ' 0 0 1 ' + (w - i) + ',' + (i + c)
      + 'V' + (h - i - c) + 'A' + c + ',' + c + ' 0 0 1 ' + (w - i - c) + ',' + (h - i)
      + 'H' + (i + c) + 'A' + c + ',' + c + ' 0 0 1 ' + i + ',' + (h - i - c)
      + 'V' + (i + c) + 'A' + c + ',' + c + ' 0 0 1 ' + (i + c) + ',' + i + 'Z';
    svg.querySelector('path').setAttribute('d', d);
  };
  var galBtns = root.querySelectorAll('.bs-gal button');
  galBtns.forEach(drawDots);
  if ('ResizeObserver' in window) {
    var ro = new ResizeObserver(function (entries) { entries.forEach(function (e) { drawDots(e.target); }); });
    galBtns.forEach(function (b) { ro.observe(b); });
  } else {
    addEventListener('resize', function () { galBtns.forEach(drawDots); });
  }

  /* ───────── page parts (shared kit) ───────── */
  kit.words('[data-ik-words]');
  kit.lightbox();
  kit.countdown(document.getElementById('bs-count'));
  root.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () { kit.copy(b.getAttribute('data-copy'), 'បានចម្លងលេខគណនី ✓'); });
  });
  // taps on [data-sfx="tap"], the photo preview and the RSVP buttons already play their sounds inside the kit

  var thanks = document.getElementById('bs-rsvp-thanks');
  var rsvpCard = document.getElementById('bs-rsvp-card');
  kit.rsvp({
    opts: document.getElementById('bs-rsvp-opts'),
    form: document.getElementById('bs-rsvp-form'),
    note: document.getElementById('bs-rsvp-note'),
    url: root.getAttribute('data-rsvp-url') || null,
    onDone: function (status, ok) {
      if (!thanks) return;
      thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត' : (status === 'yes' ? 'សូមអរគុណ! រង់ចាំជួបលោកអ្នក ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។');
      if (ok) {
        kit.sfx('rustle', .3);
        var r = thanks.getBoundingClientRect();
        kit.burst(r.left + r.width / 2, r.top);
        if (rsvpCard) gust(swings.filter(function (sw) { return rsvpCard.contains(sw.el); })[0], .5, 1.4);
      }
    }
  });

  /* finale: «send love» — a big gust through the last garlands, petals and hearts */
  var breezeBtn = document.getElementById('bs-breeze');
  if (breezeBtn) {
    breezeBtn.addEventListener('click', function () {
      kit.sfx('rustle', .4);
      kit.sfx('pop', .25);
      gustAll(1.6, true);
      var r = breezeBtn.getBoundingClientRect();
      kit.burst(r.left + r.width / 2, r.top, { n: 22 });
      if (window.InvFlowers) for (var i = 0; i < 3; i++) window.InvFlowers.drop(r.left + r.width / 2 + (i - 1) * 60, r.top - 40, 1);
    });
  }

  /* ───────── opening: «blossom burst» ─────────
     tap → gold rings burst from the button, a gust, the words float up and away, the garlands part →
     a pink-white light blooms out of the button and fills the screen while a spiral of petals and leaves flies out
     toward the camera → under the light the cover is swapped for the page → the floral frame glides (above the light)
     to its place in the hero → the light fades, the hero garlands bloom, hearts burst where the frame lands. */
  var bloomHero = function (d) {
    swings.forEach(function (sw) { if (!sw.inCover && sw.visible) bloom(sw, d + (sw.top ? 0 : 300)); });
  };
  // heroDelay: when the page's garlands start blooming (false = later, by whoever calls bloomHero)
  var start = function (heroDelay) {
    root.classList.add('is-open');
    kit.reveal('.ik-r');
    if (heroDelay !== false) bloomHero((heroDelay || 0) + 450);
  };
  if (!cover) { root.classList.add('bs-landed'); start(); return; }

  var make = function (cls, parent) { var e = document.createElement('i'); e.className = cls; parent.appendChild(e); return e; };
  var accent = (getComputedStyle(root).getPropertyValue('--bs-accent') || '').trim() || '#d0668c';

  // returns a Promise that resolves once the light covers the whole screen, and fade() to dissolve it
  var blossomBurst = function (x, y) {
    var W = innerWidth, H = innerHeight;
    var reach = Math.sqrt(Math.pow(Math.max(x, W - x), 2) + Math.pow(Math.max(y, H - y), 2));
    var fx = document.createElement('div');
    fx.className = 'bs-fx';
    fx.style.setProperty('--bs-accent', accent);
    document.body.appendChild(fx);
    var at = 'translate(' + x + 'px,' + y + 'px) ';

    // 1 · gold rings
    for (var i = 0; i < 3; i++) {
      make('bs-fx-ring', fx).animate([
        { transform: at + 'scale(.5)', opacity: .95 },
        { transform: at + 'scale(' + (5 + i * 3) + ')', opacity: 0 }
      ], { duration: 900 + i * 260, delay: i * 120, easing: 'cubic-bezier(.2,.7,.2,1)', fill: 'both' });
    }

    // 2 · the light blooms out of the button (a 100px disc scaled past the farthest corner)
    var glow = make('bs-fx-glow', fx);
    var k = reach / 50 * 1.12;
    var grow = glow.animate([
      { transform: at + 'scale(0)', opacity: .9 },
      { transform: at + 'scale(' + (k * .35) + ')', opacity: 1, offset: .45 },
      { transform: at + 'scale(' + k + ')', opacity: 1 }
    ], { duration: 900, delay: 260, easing: 'cubic-bezier(.6,0,.3,1)', fill: 'forwards' });

    // 3 · a spiral of petals (and a few leaves) flying out toward the camera
    var N = W < 600 ? 30 : 42;
    for (var p = 0; p < N; p++) {
      var leaf = p % 6 === 5;
      var el = make('bs-fx-petal' + (leaf ? ' leaf' : ''), fx);
      el.style.setProperty('--s', (leaf ? 12 : 16) + Math.random() * 16 + 'px');
      var a0 = p / N * Math.PI * 2 + Math.random() * .5;
      var swirl = 1.1 + Math.random() * .8;                         // how far round it curls on the way out
      var dist = reach * (.75 + Math.random() * .55);
      var pt = function (f, s, r, o) {
        var a = a0 + swirl * f, d = dist * f;
        return { transform: 'translate(' + (x + Math.cos(a) * d) + 'px,' + (y + Math.sin(a) * d) + 'px) rotate(' + r + 'deg) rotate3d(1,.6,0,' + (r * 1.6) + 'deg) scale(' + s + ')', opacity: o, offset: f };
      };
      var spin = (Math.random() < .5 ? -1 : 1) * (200 + Math.random() * 360);
      var big = 2.2 + Math.random() * 2.2;
      el.animate([pt(0, .2, 0, 0), pt(.12, .6, spin * .1, 1), pt(.55, big * .55, spin * .55, 1), pt(1, big, spin, 0)],
        { duration: 1500 + Math.random() * 700, delay: 180 + Math.random() * 420, easing: 'cubic-bezier(.25,.6,.3,1)', fill: 'both' });
    }

    return {
      covered: grow.finished,
      remove: function () { fx.remove(); }       // called once the flower wall hides it
    };
  };

  /* ───────── second act: «flower wall» ─────────
     after the blossom burst has landed: dozens of watercolour flowers (cut from the floral frame art,
     assets/head-flowers/bloom-1…5.webp) bloom open from the middle outward until they cover the whole screen,
     hold for a breath, then part from the centre (each drifting outward, turning and fading) under a last shower of
     petals, and the page is back. Resolves when the screen is clear. */
  var bloomSrc = (root.getAttribute('data-blooms') || '').split('|').filter(Boolean);
  var bloomImgs = bloomSrc.map(function (s) { var im = new Image(); im.decoding = 'async'; im.src = s; return im; });   // warm the cache now
  var flowerWall = function (onCovered) {
    return new Promise(function (resolve) {
      if (!bloomSrc.length) { if (onCovered) onCovered(); resolve(); return; }
      var W = innerWidth, H = innerHeight;
      var wall = document.createElement('div');
      wall.className = 'bs-wall';
      wall.style.setProperty('--bs-accent', accent);
      var wash = make('bs-wall-wash', wall);
      document.body.appendChild(wall);
      var cell = Math.max(W, H) / (W < 600 ? 6 : 6.5);
      var size = cell * 1.6;                                   // overlapping, so no gaps show
      var cx = W / 2, cy = H * .45;
      var flowers = [], maxD = 0;
      var rows = Math.ceil(H / (cell * .86)) + 1;
      for (var r = 0; r < rows; r++) {
        var y = r * cell * .86;
        var off = r % 2 ? cell / 2 : 0;                         // honeycomb rows
        for (var x = -off; x < W + cell; x += cell) {
          var fx = x + (Math.random() - .5) * cell * .35, fy = y + (Math.random() - .5) * cell * .35;
          var d = Math.sqrt(Math.pow(fx - cx, 2) + Math.pow(fy - cy, 2));
          maxD = Math.max(maxD, d);
          flowers.push({ x: fx, y: fy, d: d, s: size * (.85 + Math.random() * .35), rot: Math.random() * 360 });
        }
      }
      flowers.sort(function (a, b) { return b.d - a.d; });     // far ones underneath, the middle on top
      flowers.forEach(function (f, i) {
        var img = document.createElement('img');
        img.src = bloomSrc[i % bloomSrc.length];
        img.alt = '';
        img.className = 'bs-wall-flower';
        img.style.width = img.style.height = f.s + 'px';
        img.style.left = (f.x - f.s / 2) + 'px';
        img.style.top = (f.y - f.s / 2) + 'px';
        wall.appendChild(img);
        f.el = img;
        var t = f.d / maxD;
        f.el.animate([
          { transform: 'rotate(' + (f.rot - 70) + 'deg) scale(0)', opacity: 0 },
          { transform: 'rotate(' + (f.rot - 12) + 'deg) scale(.75)', opacity: 1, offset: .35 },
          { transform: 'rotate(' + (f.rot + 6) + 'deg) scale(1.1)', opacity: 1, offset: .75 },
          { transform: 'rotate(' + f.rot + 'deg) scale(1)', opacity: 1 }
        ], { duration: 900 + Math.random() * 250, delay: t * 750 + Math.random() * 140, easing: 'cubic-bezier(.3,.7,.3,1)', fill: 'both' });
      });
      wash.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 700, delay: 350, easing: 'ease', fill: 'both' });
      kit.sfx('rustle', .4);
      setTimeout(function () { kit.sfx('rustle', .3); }, 550);
      setTimeout(function () { kit.sfx('chime', .28); if (onCovered) onCovered(); }, 1500);   // the screen is all flowers now

      // hold, then part from the centre outward
      setTimeout(function () {
        kit.sfx('reveal', .3);
        kit.sfx('rustle', .35);
        var last = 0;
        flowers.forEach(function (f) {
          var t = f.d / maxD;
          var ang = Math.atan2(f.y - cy, f.x - cx) + (Math.random() - .5) * .6;
          var push = Math.max(W, H) * (.35 + Math.random() * .3);
          var delay = t * 750 + Math.random() * 120, dur = 950 + Math.random() * 350;
          last = Math.max(last, delay + dur);
          f.el.animate([
            { transform: 'translate(0,0) rotate(' + f.rot + 'deg) scale(1)', opacity: 1 },
            { transform: 'translate(' + Math.cos(ang) * push + 'px,' + (Math.sin(ang) * push + 60) + 'px) rotate(' + (f.rot + (Math.random() < .5 ? -1 : 1) * 120) + 'deg) scale(' + (1.3 + Math.random() * .5) + ')', opacity: 0 }
          ], { duration: dur, delay: delay, easing: 'cubic-bezier(.5,0,.6,1)', fill: 'both' });
        });
        wash.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 900, delay: 150, easing: 'ease-in-out', fill: 'forwards' });
        // a last shower of petals over the page
        if (window.InvFlowers) {
          for (var k = 0; k < 4; k++) {
            (function (k) { setTimeout(function () { window.InvFlowers.drop(W * (.12 + .25 * k), H * (.05 + Math.random() * .2), .7); }, 250 + k * 110); })(k);
          }
        }
        setTimeout(function () { resolve(); }, Math.min(last, 1500));
        setTimeout(function () { wall.remove(); }, last + 100);
      }, 2300);
    });
  };

  kit.lock(true);
  var opened = false;
  var openBtn = document.getElementById('bs-open');
  openBtn.addEventListener('click', function () {
    if (opened) return;
    opened = true;
    kit.startSong();
    kit.sfx('rustle', .45);
    kit.sfx('reveal', .35);
    swings.forEach(function (sw) { if (sw.inCover) gust(sw, .5, 2.2); });
    var coverFrame = document.querySelector('#bs-cover-frame .bs-nf-box');
    var guestName = document.getElementById('bs-guest-name');
    var burst = null, floater = null;
    // the page is fully open (first seen now): scroll allowed, the invitation's lines rise in, hearts at the guest's name
    var finish = function () {
      kit.lock(false);
      root.classList.add('bs-landed');
      setTimeout(function () {
        if (!guestName) return;
        var r = guestName.getBoundingClientRect();
        kit.burst(r.left + r.width / 2, r.top + r.height / 2, { n: 18 });
        kit.sfx('chime', .3);
      }, 450);
    };
    // the cover's frame (art only; the names inside float away) rises gently above the light, a fixed copy
    var floatFrame = function () {
      var img = coverFrame && coverFrame.querySelector('img');
      if (!img) return;
      var r = coverFrame.getBoundingClientRect();
      floater = document.createElement('div');
      floater.className = 'bs-float-frame';
      floater.style.cssText = 'left:' + r.left + 'px;top:' + r.top + 'px;width:' + r.width + 'px;height:' + r.height + 'px';
      var ci = document.createElement('img');
      ci.src = img.currentSrc || img.src; ci.alt = '';
      floater.appendChild(ci);
      document.body.appendChild(floater);
      coverFrame.style.visibility = 'hidden';
      floater.animate([
        { transform: 'translateY(0) rotate(0deg) scale(1)' },
        { transform: 'translateY(-4%) rotate(-2deg) scale(1.1)', offset: .5 },
        { transform: 'translateY(-7%) rotate(1deg) scale(1.18)' }
      ], { duration: 2400, easing: 'cubic-bezier(.3,.6,.3,1)', fill: 'forwards' });
    };
    // second act: the flower wall blooms over the light and takes the floating frame in; once it covers everything the
    // light and the frame are cleared underneath, and the first section is revealed when the flowers part
    var secondAct = function () {
      flowerWall(function () {
        burst.remove();
        if (floater) floater.remove();
      }).then(finish);
      bloomHero(2300 + 150);                     // the page's garlands open as the flowers part
      setTimeout(function () { root.classList.add('bs-landed'); }, 2300 + 350);   // …and the invitation's lines rise in
    };

    if (reduce) {                                // reduced motion: just a soft fade to the page
      start();
      cover.classList.add('is-gone');
      finish();
      return;
    }

    var b = openBtn.getBoundingClientRect();
    var bx = b.left + b.width / 2, by = b.top + b.height / 2;
    cover.classList.add('is-opening');           // words float away, garlands part
    start(false);                                // the page renders under the cover now; its garlands bloom when revealed
    kit.settle(function () {
      burst = blossomBurst(bx, by);
      floatFrame();
      setTimeout(secondAct, 700);                // early: the blooms open round the frame while it floats
      burst.covered.then(function () {
        cover.style.transition = 'none';         // swapped while the light hides everything
        cover.classList.add('is-gone');
        kit.sfx('chime', .22);                   // the light stays up until the flower wall covers it
      });
    });
  });
})();

/* Green Wedding — resources/views/invitations/templates/wedding/green-wedding.blade.php (CSS: public/css/invitations/green-wedding.css)

   The wisteria curtain ([data-curtain]) is cut into strands (.gw-strand, one band of the picture each). Every strand
   hangs from the top of its band and swings on its own, like a real hanging flower:
     · a breeze that blows mostly from the left to the right: three sines with unrelated periods (never visibly
       repeats) that reach each strand a little later the further right it hangs, so the motion rolls across the
       curtain like a wave; the wind itself rises and falls, and leans the strands slightly to the right;
     · random gusts: every 2–6 s a gust of random strength crosses the curtain (mostly left → right, now and then
       back), kicking each strand's soft damped spring as it passes;
     · a fast flutter of its own (the shake of the blossoms) and a lagging skew, so the tips trail and bend instead
       of tilting stiffly;
     · touch: a tap pushes the strands away from the finger (a ripple both ways), a finger brushing through pushes
       the strands it passes in the direction it moves, a fast scroll is a breath of wind. Each with a leaf rustle.
   The curtain unrolls left → right (strands growing down from the top) the first time it shows.
   Only curtains on screen are animated; nothing moves with reduced motion. */
(function () {
  'use strict';
  var root = document.querySelector('.gw');
  if (!root || !window.InviteKit) return;
  root.classList.add('js');

  var cover = document.getElementById('gw-cover');
  var kit = InviteKit({ root: root, cover: !!cover });
  var reduce = kit.reduce;

  var TAU = Math.PI * 2;
  var clamp01 = function (x) { return x < 0 ? 0 : x > 1 ? 1 : x; };
  var smooth = function (x) { x = clamp01(x); return x * x * (3 - 2 * x); };
  var backEase = function (x) { x = clamp01(x); var c = 1.25, u = x - 1; return 1 + (c + 1) * u * u * u + c * u * u; };
  var rnd = function (a, b) { return a + Math.random() * (b - a); };
  var UNROLL_MS = 1500, ACROSS_MS = 900;

  /* ───────── sound: a soft leaf rustle, never more than one at a time ───────── */
  var lastRustle = 0;
  var rustle = function (vol) {
    var now = performance.now();
    if (now - lastRustle < 450) return;
    lastRustle = now;
    kit.sfx('rustle', vol);
  };

  /* ───────── butterflies ─────────
     Glowing butterflies wander through the night garden over the page (one canvas, wings drawn once as sprites):
     cream-gold, mint and pearl, with a faint glow. Each one flaps (its wings fold from full open to edge-on and
     back), takes short glides, wanders on a smooth random heading, bobs up and down, and turns back in from the screen
     edges. The curtain's wind blows them too: a gust that crosses the curtain carries them (mostly to the right).
     A tap near one makes it dart away (soft pop). fall.drop(x, y, n) releases a little flight from a point (opening,
     RSVP, «send love») that flutters off the screen. Off with reduced motion. */
  var fall = (function () {
    var api = { wind: function () {}, drop: function () {}, start: function () {} };
    if (reduce) return api;
    var cv = document.createElement('canvas');
    cv.className = 'gw-fall';
    cv.setAttribute('aria-hidden', 'true');
    root.appendChild(cv);
    var ctx = cv.getContext('2d');
    if (!ctx) return api;
    var dpr = Math.min(2, window.devicePixelRatio || 1), W = 0, H = 0;
    var size = function () { W = innerWidth; H = innerHeight; cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0); };
    size();
    addEventListener('resize', size);

    // one side's wings (fore + hind), drawn with the body line at x = 0, head up; the other side is the mirror
    var WW = 30, WH = 36;
    var wing = function (c0, c1, edge, dots) {
      var c = document.createElement('canvas');
      c.width = WW * 2; c.height = WH * 2;
      var g = c.getContext('2d');
      g.scale(2, 2);
      var fill = function (path, x0, y0, x1, y1) {
        var gr = g.createLinearGradient(x0, y0, x1, y1);
        gr.addColorStop(0, c0); gr.addColorStop(.65, c1); gr.addColorStop(1, edge);
        g.fillStyle = gr; path(); g.fill();
        g.strokeStyle = edge; g.lineWidth = .9; path(); g.stroke();
      };
      fill(function () { g.beginPath(); g.moveTo(1, 15); g.bezierCurveTo(6, 2, 22, -1, 28, 4); g.bezierCurveTo(30, 10, 22, 17, 1, 19); g.closePath(); }, 0, 16, 28, 2);   // fore wing
      fill(function () { g.beginPath(); g.moveTo(1, 19); g.bezierCurveTo(16, 18, 24, 24, 20, 31); g.bezierCurveTo(16, 36, 6, 33, 1, 24); g.closePath(); }, 0, 20, 20, 33);  // hind wing
      g.strokeStyle = 'rgba(255,255,255,.35)'; g.lineWidth = .5;                                   // veins
      g.beginPath(); g.moveTo(2, 16); g.lineTo(24, 5); g.moveTo(2, 17); g.lineTo(26, 11); g.moveTo(2, 21); g.lineTo(17, 29); g.stroke();
      g.fillStyle = dots;
      [[22, 6, 1.4], [25, 9, 1], [16, 28, 1.3]].forEach(function (d) { g.beginPath(); g.arc(d[0], d[1], d[2], 0, TAU); g.fill(); });
      return c;
    };
    var KINDS = [
      { w: wing('#fffdf0', '#f6e2a6', '#c99a45', '#fff6d6'), glow: 'rgba(255,232,160,' },   // cream-gold
      { w: wing('#f4fff0', '#bfe6b4', '#5f9a5a', '#ffffff'), glow: 'rgba(190,240,170,' },   // mint
      { w: wing('#ffffff', '#e8eef7', '#b8913f', '#f0d590'), glow: 'rgba(240,240,255,' }    // pearl
    ];

    var flies = [], wind = 0, on = false;
    var make = function (x, y, burst) {
      var z = rnd(.6, 1.1);
      return {
        k: KINDS[Math.floor(Math.random() * KINDS.length)], x: x, y: y, z: z,
        h: burst ? rnd(0, TAU) : rnd(-Math.PI, 0),                     // heading (radians, 0 = right)
        turn: 0, speed: rnd(22, 36) * z, boost: burst ? 2.4 : 0,
        ph: rnd(0, TAU), flapHz: rnd(4.2, 5.8), glide: 0, bob: rnd(0, TAU),
        life: burst ? rnd(6, 9) : Infinity, alpha: 0
      };
    };
    var N = W < 600 ? 6 : 11;
    for (var i = 0; i < N; i++) flies.push(make(rnd(0, W), rnd(H * .1, H * .95), false));

    var last = performance.now();
    var tick = function (now) {
      var dt = Math.min(.05, (now - last) / 1000);
      last = now;
      wind *= Math.pow(.35, dt);
      ctx.clearRect(0, 0, W, H);
      if (on) {
        for (var j = flies.length - 1; j >= 0; j--) {
          var b = flies[j];
          // wander: the heading drifts smoothly at random; near an edge (ambient ones only) it turns back in
          b.turn += (rnd(-1, 1) * 3 - b.turn * 1.5) * dt;
          b.h += b.turn * dt;
          if (b.life === Infinity) {
            var m = 50, tx = b.x < m ? 1 : b.x > W - m ? -1 : 0, ty = b.y < H * .08 ? 1 : b.y > H - m ? -1 : 0;
            if (tx || ty) {
              var want = Math.atan2(ty, tx), d = Math.atan2(Math.sin(want - b.h), Math.cos(want - b.h));
              b.h += d * Math.min(1, dt * 2.2);
            }
          }
          b.boost *= Math.pow(.3, dt);
          var sp = b.speed * (1 + b.boost);
          // short glides now and then: wings held half open
          if (b.glide > 0) b.glide -= dt; else if (Math.random() < dt * .25) b.glide = rnd(.4, 1.1);
          var gliding = b.glide > 0;
          b.ph += (gliding ? 0 : b.flapHz * (1 + b.boost * .6)) * TAU * dt;
          b.bob += dt * 2.2;
          b.x += (Math.cos(b.h) * sp + wind * b.z) * dt;
          // each downstroke lifts it a little, between beats it sinks: the bouncy flight of a real butterfly
          var lift = gliding ? 4 : -Math.max(0, Math.sin(b.ph)) * 30 + 9;
          b.y += (Math.sin(b.h) * sp + (lift + Math.sin(b.bob) * 4) * b.z) * dt;
          b.life -= dt;
          b.alpha = Math.min(1, b.alpha + dt * .8);
          if (b.life !== Infinity && (b.life <= 0 || b.x < -60 || b.x > W + 60 || b.y < -60 || b.y > H + 60)) { flies.splice(j, 1); continue; }
          if (b.life === Infinity) {                                      // a gust can carry one off: it comes back from the other side
            if (b.x > W + 50) { b.x = -40; b.alpha = 0; } else if (b.x < -50) { b.x = W + 40; b.alpha = 0; }
            if (b.y < -50) b.y = H + 40; else if (b.y > H + 50) b.y = -40;
          }
          var open = gliding ? .55 : .1 + .9 * (.5 + .5 * Math.cos(b.ph));   // 1 = wings flat open, ~0 = folded up
          var s = b.z * .68, fade = Math.min(1, b.life) * b.alpha;
          ctx.save();
          ctx.translate(b.x, b.y);
          ctx.globalAlpha = fade * .22;                                    // a faint glow, just enough to read on the dark sky
          var hg = ctx.createRadialGradient(0, 0, 0, 0, 0, 13 * s);
          hg.addColorStop(0, b.k.glow + '.6)'); hg.addColorStop(1, b.k.glow + '0)');
          ctx.fillStyle = hg; ctx.beginPath(); ctx.arc(0, 0, 13 * s, 0, TAU); ctx.fill();
          ctx.rotate(b.h + Math.PI / 2 + b.turn * .08 + Math.sin(b.ph) * .05);   // head along the flight, banking into turns
          ctx.scale(s, s);
          ctx.globalAlpha = fade * (.82 + .18 * b.z);
          for (var side = -1; side <= 1; side += 2) {
            ctx.save();
            ctx.scale(side * open, 1);
            ctx.drawImage(b.k.w, 0, -WH / 2, WW, WH);
            ctx.restore();
          }
          ctx.fillStyle = '#5a4520';                                      // body + antennae
          ctx.beginPath(); ctx.ellipse(0, 1, 1.4, 8, 0, 0, TAU); ctx.fill();
          ctx.strokeStyle = '#5a4520'; ctx.lineWidth = .6;
          ctx.beginPath(); ctx.moveTo(-.5, -6); ctx.quadraticCurveTo(-3, -11, -4, -13); ctx.moveTo(.5, -6); ctx.quadraticCurveTo(3, -11, 4, -13); ctx.stroke();
          ctx.restore();
        }
      }
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    // a tap near a butterfly: it darts away from the finger
    root.addEventListener('pointerdown', function (e) {
      if (!on) return;
      var hit = false;
      flies.forEach(function (b) {
        var dx = b.x - e.clientX, dy = b.y - e.clientY;
        if (dx * dx + dy * dy < 55 * 55) { b.h = Math.atan2(dy, dx) + rnd(-.4, .4); b.boost = 3; b.glide = 0; hit = true; }
      });
      if (hit) kit.sfx('pop', .14);
    }, { passive: true });

    api.start = function () { on = true; };
    api.wind = function (f) { wind = Math.max(-140, Math.min(140, wind + f)); };
    api.drop = function (x, y, n) { on = true; for (var k = 0; k < Math.min(n || 5, 8); k++) flies.push(make(x + rnd(-15, 15), y + rnd(-10, 10), true)); };
    return api;
  })();

  /* ───────── the curtains ───────── */
  var curtains = [].map.call(root.querySelectorAll('[data-curtain]'), function (el) {
    var strands = [].map.call(el.querySelectorAll('.gw-strand'), function (s) {
      var x = parseFloat(s.getAttribute('data-x'));
      return {
        el: s, cpy: s.parentNode, mirror: s.parentNode.classList.contains('is-mirror'),
        x: isNaN(x) ? .5 : x, gx: x,
        amp: rnd(2.1, 3.3),                // degrees
        seed: rnd(0, 10), flut: rnd(.9, 1.5), flutAmp: rnd(.18, .32),
        p: 0, v: 0, lag: 0, kicks: [], shown: -1, delay: 0
      };
    });
    return { el: el, strands: strands, visible: false, unrollAt: null, inCover: !!(cover && cover.contains(el)) };
  });

  // where each strand hangs across the whole curtain (0 = left edge, 1 = right edge), mirrored copy included
  var place = function (c) {
    var r = c.el.getBoundingClientRect();
    c.strands.forEach(function (s) {
      var cr = s.cpy.getBoundingClientRect();
      if (!r.width || !cr.width) { s.gx = s.x; return; }
      var lx = s.mirror ? 1 - s.x : s.x;
      s.gx = clamp01((cr.left - r.left + lx * cr.width) / r.width);
    });
  };
  var placeAll = function () { curtains.forEach(place); };

  // a gust crossing the curtain: dir 1 = left → right, -1 = right → left; each strand feels it as it passes
  var gust = function (c, strength, dir) {
    if (reduce || !c) return;
    var now = performance.now();
    c.strands.forEach(function (s) {
      var along = dir > 0 ? s.gx : 1 - s.gx;
      s.kicks.push({ at: now + along * ACROSS_MS + rnd(0, 90), f: dir * strength * rnd(2, 3.2) });
    });
  };
  // a tap: the strands are pushed away from the finger, the ripple spreading both ways
  var poke = function (c, x0, strength) {
    if (reduce || !c) return;
    var now = performance.now();
    c.strands.forEach(function (s) {
      var d = s.gx - x0, near = Math.max(0, 1 - Math.abs(d) * 1.6);
      var dir = Math.abs(d) < .03 ? (Math.random() < .5 ? -1 : 1) : (d > 0 ? 1 : -1);
      s.kicks.push({ at: now + Math.abs(d) * 650, f: dir * strength * (1 + 2.4 * near) * rnd(.85, 1.15) });
    });
  };
  // a finger brushing through: only the strands near it, pushed the way it moves
  var brush = function (c, x0, dir, strength) {
    if (reduce || !c) return;
    var now = performance.now();
    c.strands.forEach(function (s) {
      var near = 1 - Math.abs(s.gx - x0) / .14;
      if (near > 0) s.kicks.push({ at: now, f: dir * strength * near * 3 });
    });
  };
  var gustAll = function (strength, onlyVisible, dir) {
    curtains.forEach(function (c) { if (!onlyVisible || c.visible) gust(c, strength, dir || 1); });
    fall.wind(strength * (dir || 1) * 70);
  };
  var unroll = function (c, delay) {
    if (c.unrollAt !== null) return;
    place(c);
    c.unrollAt = performance.now() + (delay || 0);
    c.strands.forEach(function (s) {
      s.delay = s.gx * 700 + rnd(0, 140);       // left → right
      s.kicks.push({ at: c.unrollAt + s.delay + 200, f: rnd(1.6, 2.6) });   // it swings in as it drops
    });
    if (!c.inCover) setTimeout(function () { rustle(.16); }, (delay || 0) + 200);
  };

  if (reduce || !curtains.length) {
    root.classList.remove('gw-bud');
  } else {
    curtains.forEach(function (c) { c.strands.forEach(function (s) { s.el.style.opacity = '0'; }); });
    root.classList.remove('gw-bud');
    root.classList.add('gw-live');

    var setVisible = function (c, on) {
      c.visible = on;
      c.el.classList.toggle('is-live', on);
      if (on && (c.inCover || root.classList.contains('is-open'))) unroll(c, 120);
    };
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          curtains.forEach(function (c) { if (c.el === e.target) setVisible(c, e.isIntersecting); });
        });
      }, { rootMargin: '80px 0px' });
      curtains.forEach(function (c) { io.observe(c.el); });
    } else {
      curtains.forEach(function (c) { setVisible(c, true); });
    }

    // random gusts: random strength and timing, mostly from the left
    var nextGust = performance.now() + rnd(1800, 3500);
    var last = performance.now(), t0 = last;
    var frame = function (now) {
      var dt = Math.min(.05, (now - last) / 1000);
      last = now;
      var t = (now - t0) / 1000;
      if (now >= nextGust) {
        var strength = rnd(.45, 1.35), dir = Math.random() < .85 ? 1 : -1;
        curtains.forEach(function (c) { if (c.visible && c.unrollAt !== null) gust(c, strength, dir); });
        setTimeout(function () { fall.wind(strength * dir * 70); }, 350);    // the falling florets feel it too
        if (strength > 1.05) rustle(.1);
        nextGust = now + rnd(2200, 6200);
      }
      var breeze = .55 + .45 * Math.sin(t * TAU / 17 + 1.3);      // the wind comes and goes
      for (var k = 0; k < curtains.length; k++) {
        var c = curtains[k];
        if (!c.visible || c.unrollAt === null) continue;
        var since = now - c.unrollAt;
        for (var j = 0; j < c.strands.length; j++) {
          var s = c.strands[j];
          var g = clamp01((since - s.delay) / UNROLL_MS);
          if (s.kicks.length) {
            for (var q = s.kicks.length - 1; q >= 0; q--) if (s.kicks[q].at <= now) { s.v += s.kicks[q].f; s.kicks.splice(q, 1); }
          }
          // soft spring: ~1.8 s period, settles in ~3 s
          s.v += (-12 * s.p - 1.4 * s.v) * dt;
          s.p += s.v * dt;
          if (s.p > 3) { s.p = 3; s.v *= -.3; } else if (s.p < -3) { s.p = -3; s.v *= -.3; }
          var lt = t - s.gx * 1.6;                                      // the wave rolls left → right
          var wind = .5 * Math.sin(lt * TAU / 5.3 + s.seed * .2)
                   + .3 * Math.sin(lt * TAU / 3.1 + s.seed * .5)
                   + .2 * Math.sin(lt * TAU / 1.9 + s.seed);
          var push = (wind * .75 + .3) * breeze + s.p;                  // > 0 = tips blown to the right
          var flutter = s.flutAmp * Math.sin(t * TAU / s.flut + s.seed * 3);
          // CSS rotate(+) swings the bottom of a top-hung strand to the LEFT, so the push goes in negative
          var rot = -s.amp * push * (.3 + .7 * smooth(g)) + flutter;
          s.lag += (rot - s.lag) * Math.min(1, dt * 2.2);
          var skew = (rot - s.lag) * .45;
          var drop = .12 + .88 * backEase(g);                           // unrolling: grows down from the top
          var breath = 1 + .012 * Math.sin(t * TAU / 6.4 + s.seed);
          var m = s.mirror ? -1 : 1;                                    // the mirrored copy turns the other way
          s.el.style.transform = 'rotate(' + (rot * m).toFixed(3) + 'deg) skewX(' + (skew * m).toFixed(3) + 'deg) scaleY(' + (drop * breath).toFixed(4) + ')';
          var op = g >= .5 ? 1 : smooth(g / .5);
          if (op !== s.shown) { s.el.style.opacity = op.toFixed(3); s.shown = op; }
        }
      }
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
  }

  /* touch the curtain: a tap pushes the strands aside, a finger / mouse brushing through stirs the ones it passes */
  curtains.forEach(function (c) {
    var lastX = null, lastT = 0;
    var at = function (e) { var r = c.el.getBoundingClientRect(); return clamp01((e.clientX - r.left) / (r.width || 1)); };
    c.el.addEventListener('pointerdown', function (e) {
      place(c);
      poke(c, at(e), 1);
      rustle(.32);
      lastX = e.clientX; lastT = performance.now();
    });
    c.el.addEventListener('pointermove', function (e) {
      var now = performance.now();
      if (lastX === null) { lastX = e.clientX; lastT = now; return; }
      var dx = e.clientX - lastX, speed = Math.abs(dx) / Math.max(16, now - lastT);
      lastX = e.clientX; lastT = now;
      if (speed > .5) { brush(c, at(e), dx > 0 ? 1 : -1, Math.min(1, speed * .4)); rustle(.16); }
    });
    c.el.addEventListener('pointerleave', function () { lastX = null; });
  });
  var placeTimer = 0;
  addEventListener('resize', function () { clearTimeout(placeTimer); placeTimer = setTimeout(placeAll, 200); });

  /* the pinned curtain lifts as the page scrolls down (so it never covers the text), a fast scroll is a breath of wind */
  var liftQueued = false;
  var setLift = function () {
    liftQueued = false;
    root.style.setProperty('--gw-lift', clamp01(window.scrollY / (innerHeight * .5)).toFixed(3));
  };
  addEventListener('scroll', function () { if (!liftQueued) { liftQueued = true; requestAnimationFrame(setLift); } }, { passive: true });
  setLift();
  var lastScrollGust = 0;
  kit.onScroll(function (s) {
    var v = Math.abs(s.velocity), now = performance.now();
    if (v > 14 && now - lastScrollGust > 700) {
      lastScrollGust = now;
      gustAll(Math.min(.9, v / 55), true, s.velocity > 0 ? 1 : -1);
    }
  });

  /* ───────── gallery: a line of gold dots round each photo (SVG, redrawn on resize so the dots stay round) ───────── */
  var dotSeq = 0, SVGNS = 'http://www.w3.org/2000/svg';
  var drawDots = function (b) {
    var w = b.offsetWidth, h = b.offsetHeight;
    if (!w || !h) return;
    var svg = b.querySelector('svg.gw-dots');
    if (!svg) {
      var id = 'gw-dg' + (++dotSeq);
      svg = document.createElementNS(SVGNS, 'svg');
      svg.setAttribute('class', 'gw-dots');
      svg.setAttribute('aria-hidden', 'true');
      var cycle = ['#e2c27d', '#fff1c4', '#9fc58a', '#e2c27d'].join(';');
      var stop = function (off, phase) {
        return '<stop offset="' + off + '" stop-color="#e2c27d">'
          + (reduce ? '' : '<animate attributeName="stop-color" values="' + cycle + '" dur="6s" begin="-' + phase + 's" repeatCount="indefinite"/>')
          + '</stop>';
      };
      svg.innerHTML = '<defs><linearGradient id="' + id + '" gradientUnits="userSpaceOnUse">'
        + stop(0, 0) + stop(.5, 2) + stop(1, 4) + '</linearGradient></defs><path stroke="url(#' + id + ')"/>';
      b.appendChild(svg);
    }
    svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
    var g = svg.querySelector('linearGradient');
    g.setAttribute('x1', 0); g.setAttribute('y1', 0); g.setAttribute('x2', w); g.setAttribute('y2', h);
    var i = 3.5, c = (b.classList.contains('wide') ? 25 : 18) - i;
    var d = 'M' + (i + c) + ',' + i + 'H' + (w - i - c) + 'A' + c + ',' + c + ' 0 0 1 ' + (w - i) + ',' + (i + c)
      + 'V' + (h - i - c) + 'A' + c + ',' + c + ' 0 0 1 ' + (w - i - c) + ',' + (h - i)
      + 'H' + (i + c) + 'A' + c + ',' + c + ' 0 0 1 ' + i + ',' + (h - i - c)
      + 'V' + (i + c) + 'A' + c + ',' + c + ' 0 0 1 ' + (i + c) + ',' + i + 'Z';
    svg.querySelector('path').setAttribute('d', d);
  };
  var galBtns = root.querySelectorAll('.gw-gal button');
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
  kit.countdown(document.getElementById('gw-count'));
  root.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () { kit.copy(b.getAttribute('data-copy'), 'បានចម្លងលេខគណនី ✓'); });
  });

  var thanks = document.getElementById('gw-rsvp-thanks');
  var rsvpCard = document.getElementById('gw-rsvp-card');
  kit.rsvp({
    opts: document.getElementById('gw-rsvp-opts'),
    form: document.getElementById('gw-rsvp-form'),
    note: document.getElementById('gw-rsvp-note'),
    url: root.getAttribute('data-rsvp-url') || null,
    onDone: function (status, ok) {
      if (!thanks) return;
      thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត' : (status === 'yes' ? 'សូមអរគុណ! រង់ចាំជួបលោកអ្នក ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។');
      if (ok) {
        kit.sfx('rustle', .3);
        var r = thanks.getBoundingClientRect();
        kit.burst(r.left + r.width / 2, r.top);
        fall.drop(r.left + r.width / 2, r.top - 20, 5);
        if (rsvpCard) gust(curtains.filter(function (c) { return rsvpCard.contains(c.el); })[0], 1.3, 1);
      }
    }
  });

  /* finale: «send love» — a big gust through every curtain on screen, petals and hearts */
  var breezeBtn = document.getElementById('gw-breeze');
  if (breezeBtn) {
    breezeBtn.addEventListener('click', function () {
      kit.sfx('rustle', .4);
      kit.sfx('pop', .25);
      gustAll(1.5, false, 1);
      var r = breezeBtn.getBoundingClientRect();
      kit.burst(r.left + r.width / 2, r.top, { n: 22 });
      fall.drop(r.left + r.width / 2, r.top - 30, 7);
    });
  }

  /* ───────── opening ─────────
     tap → a strong gust sweeps the curtain left → right and it rises away, the words float up, the heart's gold lines
     flare → «the heart opens»: the inside of the heart becomes a window onto the invitation and grows, heart-shaped, to
     fill the screen, its glowing gold rim throwing off wisteria florets, leaves and gold sparks; butterflies fly out of
     it → the page curtain unrolls and the invitation's lines rise in, hearts at the guest's name. */
  var start = function (unrollDelay) {
    root.classList.add('is-open');
    fall.start();
    kit.reveal('.ik-r');
    curtains.forEach(function (c) { if (!c.inCover && c.visible) unroll(c, unrollDelay || 0); });
  };
  if (!cover) { root.classList.add('gw-landed'); start(300); return; }

  var make = function (cls, parent) { var e = document.createElement('i'); e.className = cls; parent.appendChild(e); return e; };

  // the heart of the name frame (same path as in the Blade, 300 × 300 box, centred on 150 148)
  var HEART = 'M150 284C62 222 8 162 8 96C8 46 45 12 90 12C118 12 140 28 150 48C160 28 182 12 210 12C255 12 292 46 292 96C292 162 238 222 150 284Z';
  var heartMask = 'url("data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"><path d="' + HEART + '"/></svg>') + '")';
  // points along the heart's outline (0–1 of the box), for the florets thrown off its edge
  var edge = (function () {
    var pts = [];
    try {
      var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      p.setAttribute('d', HEART);
      var len = p.getTotalLength();
      for (var i = 0; i < 90; i++) { var q = p.getPointAtLength(len * i / 90); pts.push([q.x / 300, q.y / 300]); }
    } catch (e) {}
    if (!pts.length) for (var j = 0; j < 90; j++) { var a = j / 90 * TAU; pts.push([.5 + .47 * Math.sin(a), .48 - .4 * Math.cos(a)]); }
    return pts;
  })();

  /* «the heart opens»: the inside of the heart becomes a window onto the invitation, and the window grows, heart-shaped,
     until it fills the screen. Its rim is a glowing gold heart line that throws off wisteria florets, leaves and gold
     sparks (blown a little to the right by the wind) as it passes. The cover is masked (a heart hole cut out of it),
     driven frame by frame. onHalf: the page's lines start rising; resolves when the cover is gone. */
  var heartOpen = function (rect, onHalf) {
    return new Promise(function (resolve) {
      var W = innerWidth, H = innerHeight;
      var S0 = rect.width, x0 = rect.left, y0 = rect.top;
      var cx = x0 + S0 * .5, cy = y0 + S0 * .493;                            // the heart's middle on screen
      var S1 = Math.max(W, H) * 4.2;
      var fx = document.createElement('div');
      fx.className = 'gw-fx';
      root.appendChild(fx);
      var rim = document.createElement('div');
      rim.className = 'gw-iris';
      rim.innerHTML = '<svg viewBox="0 0 300 300" aria-hidden="true"><defs><linearGradient id="gw-iris-g" x1="0" y1="0" x2="1" y2="1">'
        + '<stop offset="0" stop-color="#fff4cf"/><stop offset=".45" stop-color="#e2c27d"/><stop offset=".55" stop-color="#fffaf0"/><stop offset="1" stop-color="#d4a94f"/>'
        + '</linearGradient></defs>'
        + '<path class="halo" d="' + HEART + '"/><path d="' + HEART + '"/>'
        + '<path class="inner" d="' + HEART + '" transform="translate(150 148) scale(.93) translate(-150 -148)"/></svg>';
      root.appendChild(rim);
      var ms = cover.style;
      ms.webkitMaskImage = ms.maskImage = heartMask + ', linear-gradient(#000 0 0)';
      ms.webkitMaskRepeat = ms.maskRepeat = 'no-repeat';
      ms.webkitMaskComposite = 'xor';
      ms.maskComposite = 'exclude';
      var DUR = 2100, t0 = performance.now(), half = false, lastSpawn = 0;
      var ease = function (x) { return x < .5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2; };
      var spawn = function (S, L, T) {
        for (var k = 0; k < 3; k++) {
          var e = edge[Math.floor(Math.random() * edge.length)];
          var x = L + e[0] * S, y = T + e[1] * S;
          if (x < -40 || x > W + 40 || y < -40 || y > H + 40) continue;
          var r = Math.random(), kind = r < .16 ? ' gold' : r < .34 ? ' heart' : r < .54 ? ' leaf' : '';
          var el = make('gw-fx-petal' + kind, fx);
          el.style.setProperty('--s', (kind === ' gold' ? rnd(4, 8) : kind === ' heart' ? rnd(8, 14) : rnd(9, 18)) + 'px');
          var ang = Math.atan2(y - cy, x - cx), d = rnd(40, 120), spin = rnd(-300, 300);
          var tx = x + Math.cos(ang) * d + rnd(30, 90), ty = y + Math.sin(ang) * d + rnd(10, 50);   // outward, then the wind
          el.animate([
            { transform: 'translate(' + x + 'px,' + y + 'px) rotate(0deg) scale(.4)', opacity: 0 },
            { transform: 'translate(' + (x + (tx - x) * .3) + 'px,' + (y + (ty - y) * .3) + 'px) rotate(' + spin * .3 + 'deg) scale(1)', opacity: 1, offset: .2 },
            { transform: 'translate(' + tx + 'px,' + ty + 'px) rotate(' + spin + 'deg) rotate3d(1,.6,0,' + spin + 'deg) scale(.8)', opacity: 0 }
          ], { duration: rnd(1100, 1700), easing: 'cubic-bezier(.2,.7,.3,1)', fill: 'both' });
        }
      };
      var frame = function (now) {
        var p = Math.min(1, (now - t0) / DUR), e = ease(p);
        var S = S0 * Math.pow(S1 / S0, e);                                    // grows like a zoom, slow then fast
        var L = cx - S * .5, T = cy - S * .493;
        ms.webkitMaskSize = ms.maskSize = S + 'px ' + S + 'px, 100% 100%';
        ms.webkitMaskPosition = ms.maskPosition = L + 'px ' + T + 'px, 0 0';
        rim.style.cssText = 'left:' + L + 'px;top:' + T + 'px;width:' + S + 'px;height:' + S + 'px;opacity:' + (1 - Math.max(0, (e - .75) / .25));
        if (now - lastSpawn > 45 && e < .85) { lastSpawn = now; spawn(S, L, T); }
        if (!half && p > .45) { half = true; if (onHalf) onHalf(); }
        if (p < 1) { requestAnimationFrame(frame); return; }
        cover.style.transition = 'none';
        cover.classList.add('is-gone');
        rim.remove();
        setTimeout(function () { fx.remove(); }, 1800);
        resolve();
      };
      requestAnimationFrame(frame);
    });
  };

  /* the prelude to «the heart opens» (≈ 1.1 s): the heart frame beats twice — lub-dub — each beat a soft pop and a gold
     heart outline rippling out of it, while ten little gold hearts circle round it and are drawn into its middle */
  var heartbeat = function (nf) {
    var r = nf.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height * .493;
    var fx = document.createElement('div');
    fx.className = 'gw-fx';
    root.appendChild(fx);
    nf.animate([
      { transform: 'scale(1)' }, { transform: 'scale(1.09)', offset: .14 }, { transform: 'scale(.98)', offset: .26 },
      { transform: 'scale(1.13)', offset: .4 }, { transform: 'scale(1)', offset: .62 }, { transform: 'scale(1)' }
    ], { duration: 1100, easing: 'ease-in-out' });
    [0, 440].forEach(function (d) {
      setTimeout(function () { kit.sfx('pop', .2); }, d + 120);
      var ring = document.createElement('div');
      ring.className = 'gw-iris gw-ripple';
      ring.style.cssText = 'left:' + r.left + 'px;top:' + r.top + 'px;width:' + r.width + 'px;height:' + r.height + 'px';
      ring.innerHTML = '<svg viewBox="0 0 300 300" aria-hidden="true"><path d="' + HEART + '"/></svg>';
      fx.appendChild(ring);
      ring.animate([{ transform: 'scale(1)', opacity: .9 }, { transform: 'scale(1.55)', opacity: 0 }],
        { duration: 900, delay: d + 100, easing: 'cubic-bezier(.2,.7,.3,1)', fill: 'both' });
    });
    for (var i = 0; i < 10; i++) {
      var h = make('gw-fx-petal heart', fx);
      h.style.setProperty('--s', rnd(9, 15) + 'px');
      var a0 = i / 10 * TAU, R = r.width * rnd(.62, .78), keys = [];
      for (var k = 0; k <= 6; k++) {
        var f = k / 6, a = a0 + f * 2.4, rr = R * (1 - .9 * Math.pow(f, 2.2));   // round and round, then drawn in
        keys.push({
          transform: 'translate(' + (cx + Math.cos(a) * rr) + 'px,' + (cy + Math.sin(a) * rr * .9) + 'px) rotate(' + (a * 57) + 'deg) scale(' + (f < .15 ? f / .15 : 1 - .6 * f) + ')',
          opacity: f < .15 ? f / .15 : f > .85 ? (1 - f) / .15 : 1, offset: f
        });
      }
      h.animate(keys, { duration: 1100, delay: i * 25, easing: 'ease-in-out', fill: 'both' });
    }
    setTimeout(function () { fx.remove(); }, 1600);
  };
  kit.lock(true);
  var opened = false;
  var openBtn = document.getElementById('gw-open');
  openBtn.addEventListener('click', function () {
    if (opened) return;
    opened = true;
    kit.startSong();
    kit.sfx('rustle', .45);
    kit.sfx('reveal', .35);
    curtains.forEach(function (c) { if (c.inCover) gust(c, 2.2, 1); });
    var guestName = document.getElementById('gw-guest-name');
    var finish = function () {
      kit.lock(false);
      root.classList.add('gw-landed');
      setTimeout(function () {
        if (!guestName) return;
        var r = guestName.getBoundingClientRect();
        kit.burst(r.left + r.width / 2, r.top + r.height / 2, { n: 18 });
        kit.sfx('chime', .3);
      }, 500);
    };
    var nf = document.getElementById('gw-cover-frame');
    if (reduce || !nf) {
      start();
      cover.classList.add('is-gone');
      finish();
      return;
    }

    // tap → the words float away and the curtain rises, the heart's gold lines flare up, it beats twice (gold ripples)
    // while little gold hearts circle in → the heart opens
    cover.classList.add('is-opening');
    nf.classList.add('is-glow');
    heartbeat(nf);
    setTimeout(function () {
      var box = nf.querySelector('.gw-nf-box');
      var rect = (box || nf).getBoundingClientRect();
      if (box) box.style.visibility = 'hidden';                             // the gold rim takes over from the frame
      kit.sfx('chime', .25);
      start(400);                                                           // the page is there, behind the heart,
      root.classList.add('gw-landed');                                       // its lines rising in as the window opens
      fall.drop(rect.left + rect.width / 2, rect.top + rect.height * .45, 6);   // butterflies fly out of the heart
      heartOpen(rect).then(function () {
        kit.sfx('rustle', .3);
        finish();
      });
    }, 1150);
  });
})();

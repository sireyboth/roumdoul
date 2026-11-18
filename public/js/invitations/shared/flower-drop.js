/* Falling flowers — resources/views/components/flower-drop.blade.php (taken from River Love's petal fall).
   Each petal/leaf is a flat shape spinning on all three axes. Its rotation matrix maps straight onto a 2D
   canvas transform (a flat shape seen in 3D = an affine transform), and the same matrix's normal lights it:
   the side facing the light glows, the back is darker, edge-on it almost disappears. Depth (z) sets size,
   speed, fade and focus blur; scrolling and touch push the near ones more.
   window.InvFlowers: drop(x, y, scale), pause(), resume(). */
(function () {
  'use strict';

  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var api = { drop: function () {}, pause: function () {}, resume: function () {} };
  window.InvFlowers = api;

  var init = function () {
    var canvas = document.querySelector('[data-inv-flowers]');
    if (!canvas || !canvas.getContext || reduce || canvas.getAttribute('data-ready')) return;
    canvas.setAttribute('data-ready', '1');

    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var W = 0, H = 0, items = [], drops = [];
    var rand = function (a, b) { return a + Math.random() * (b - a); };
    var toRgb = function (hex, d) {
      return /^#([0-9a-f]{6})$/i.test(hex || '')
        ? [parseInt(hex.slice(1, 3), 16), parseInt(hex.slice(3, 5), 16), parseInt(hex.slice(5, 7), 16)] : d;
    };
    var rgb = toRgb(canvas.getAttribute('data-color'), [232, 120, 154]);
    var leafHex = canvas.getAttribute('data-leaves') || '';
    var rose = canvas.getAttribute('data-shape') === 'rose';
    var density = parseFloat(canvas.getAttribute('data-density')) || 1;
    var deep = rgb.map(function (v) { return Math.round(v * .28); }); // the petal colour in deep shadow
    var tint = function (c, t) { // t > 0 → towards white, t < 0 → towards the deep shade
      return 'rgb(' + c.map(function (v, i) {
        return Math.round(t >= 0 ? v + (255 - v) * t : v + (deep[i] - v) * -t);
      }).join(',') + ')';
    };

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
    var blossomPath = function (g) { // cherry-blossom petal, notch at the tip
      g.beginPath();
      g.moveTo(0, 1);
      g.bezierCurveTo(-.95, .5, -.9, -.62, -.24, -1);
      g.quadraticCurveTo(-.08, -.86, 0, -.7);
      g.quadraticCurveTo(.08, -.86, .24, -1);
      g.bezierCurveTo(.9, -.62, .95, .5, 0, 1);
      g.closePath();
    };
    var rosePath = function (g) { // round rose petal: narrow at the base, wide soft top with a tiny dip
      g.beginPath();
      g.moveTo(0, 1);
      g.bezierCurveTo(-.7, .82, -1.02, .05, -.8, -.55);
      g.bezierCurveTo(-.62, -1.02, -.15, -1.05, 0, -.9);
      g.bezierCurveTo(.15, -1.05, .62, -1.02, .8, -.55);
      g.bezierCurveTo(1.02, .05, .7, .82, 0, 1);
      g.closePath();
    };
    var leafPath = function (g) {
      g.beginPath();
      g.moveTo(0, -1.05);
      g.bezierCurveTo(.62, -.6, .6, .5, 0, 1);
      g.bezierCurveTo(-.6, .5, -.62, -.6, 0, -1.05);
      g.closePath();
    };
    var petalPath = rose ? rosePath : blossomPath;
    var petalArt = function (base, back) {
      return function (g) {
        petalPath(g);
        var gr = g.createRadialGradient(0, .95, .05, 0, .1, 1.25); // deeper colour at the base, pale at the rim
        gr.addColorStop(0, tint(base, back ? -.25 : -.05));
        gr.addColorStop(.45, tint(base, back ? .15 : .35));
        gr.addColorStop(1, tint(base, back ? .45 : .82));
        g.fillStyle = gr; g.fill();
        g.save(); g.clip();
        g.strokeStyle = tint(base, back ? -.15 : .05); g.globalAlpha = .22; g.lineWidth = .025;
        if (rose) { // a soft crease down the middle and a light cupped rim
          g.beginPath(); g.moveTo(0, .95); g.quadraticCurveTo(.06, .1, 0, -.75); g.stroke();
          g.globalAlpha = .18; g.strokeStyle = '#fff'; g.lineWidth = .09;
          g.beginPath(); g.moveTo(-.75, -.5); g.bezierCurveTo(-.55, -.95, .55, -.95, .75, -.5); g.stroke();
        } else { // fine veins
          for (var k = -2; k <= 2; k++) { g.beginPath(); g.moveTo(0, .95); g.quadraticCurveTo(k * .22, .1, k * .3, -.8); g.stroke(); }
        }
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
    [.0, .2, .4].forEach(function (shift) { // three petal tones from the colour
      var base = rgb.map(function (v) { return Math.round(v + (255 - v) * shift); });
      kinds.push({ leaf: false, art: [petalArt(base, false), petalArt(base, true), solid(petalPath, '#fff'), solid(petalPath, tint(rgb, -1))] });
    });
    if (leafHex) {
      var lr = toRgb(leafHex, [140, 192, 104]);
      [leafHex, 'rgb(' + lr.map(function (v) { return Math.round(v * .86); }).join(',') + ')'].forEach(function (c) { // two greens
        kinds.push({ leaf: true, art: [leafArt(c, false), leafArt(c, true), solid(leafPath, '#fffbe8'), solid(leafPath, '#1d3315')] });
      });
    }
    // 0 = sharp, 1 = soft (far), 2 = very soft (bokeh, very near)
    kinds.forEach(function (k) {
      k.sp = [0, 1.6, 4].map(function (b) { return k.art.map(function (a) { return sprite(a, b); }); });
    });
    var petalKinds = kinds.filter(function (k) { return !k.leaf; });
    var leafKinds = kinds.filter(function (k) { return k.leaf; });
    var pick = function (pool) { return pool[(Math.random() * pool.length) | 0]; };

    var LIGHT = (function () { var l = [-.45, -.6, .66], n = Math.hypot(l[0], l[1], l[2]); return [l[0] / n, l[1] / n, l[2] / n]; })();

    var spawn = function (p, fromTop) {
      var leaf = leafKinds.length > 0 && Math.random() < .24;
      p.kind = pick(leaf ? leafKinds : petalKinds);
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
      // tilt (x) rocks back and forth; turn (y) is a slow spin, so most of the time it shows its face and
      // every few seconds it rolls over; z is the gentle in-plane twirl
      p.tilt = rand(.5, 1.1); p.tiltSpeed = rand(.012, .026); p.tiltPhase = rand(0, Math.PI * 2);
      p.ay = rand(0, Math.PI * 2); p.az = rand(0, Math.PI * 2);
      p.vay = rand(.006, .022) * (Math.random() < .5 ? -1 : 1);
      p.vaz = rand(-.014, .014);
      p.ax = 0;
      p.pushX = 0; p.pushY = 0;
      return p;
    };

    var resize = function () {
      W = window.innerWidth; H = window.innerHeight;
      canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
      var want = Math.round(Math.max(24, Math.min(W < 640 ? 40 : 70, Math.round((W * H) / 19000))) * density);
      while (items.length < want) items.push(spawn({}, false));
      if (items.length > want) items.length = want;
      items.sort(function (a, b) { return a.z - b.z; });
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

    // a handful dropped at a point: they pop up and out, then fall like the others and are gone
    api.drop = function (x, y, scale) {
      scale = scale || 1;
      var n = 7 + ((Math.random() * 3) | 0);
      var nLeaves = leafKinds.length ? 1 + ((Math.random() * 2) | 0) : 0;
      for (var i = 0; i < n; i++) {
        var leaf = i >= n - nLeaves; // the last one or two are leaves
        var p = spawn({}, false);
        p.kind = pick(leaf ? leafKinds : petalKinds);
        p.z = rand(.55, .85); p.focus = 0; p.alpha = .95;
        p.size = (leaf ? 18 : 15) * scale * rand(.85, 1.15);
        var ang = (i / n) * Math.PI * 2 + rand(-.3, .3), r = rand(8, 22) * scale;
        p.x = x + Math.cos(ang) * r; p.y = y + Math.sin(ang) * r;
        p.pushX = Math.cos(ang) * rand(1.4, 3.2);
        p.pushY = Math.sin(ang) * rand(1, 2.4) - rand(2.2, 3.6); // a little hop up before gravity wins
        p.fall = rand(.9, 1.5);
        p.vay *= 2.2; p.vaz *= 2; // fresh petals spin harder at first
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

      // lying flat it catches the air: falls slower and glides sideways; edge-on it drops
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

      // lighting from the face normal (third column); the back face shows the back sprite
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
      if (lam > .35) { ctx.globalAlpha = p.alpha * (lam - .35) * .75; ctx.drawImage(sp[2], o, o); } // the glint
      var dark = (1 - Math.max(0, lam)) * .32;
      if (dark > .02) { ctx.globalAlpha = p.alpha * dark; ctx.drawImage(sp[3], o, o); } // turned away
      return true;
    };

    var paused = false;
    var frame = function () {
      if (paused) return;
      t++;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var i = 0; i < items.length; i++) if (!step(items[i])) spawn(items[i], true); // recycle → falls forever
      for (var j = drops.length - 1; j >= 0; j--) if (!step(drops[j])) drops.splice(j, 1); // fallen → gone
      ctx.globalAlpha = 1;
      requestAnimationFrame(frame);
    };
    api.pause = function () { paused = true; };
    api.resume = function () { if (paused) { paused = false; requestAnimationFrame(frame); } };

    resize();
    window.addEventListener('resize', resize);
    requestAnimationFrame(frame);

    /* a tap drops petals where the finger was. click (not pointerdown) so a scroll or swipe never sets it
       off; keyboard clicks (no position) skip it. */
    if (canvas.getAttribute('data-tap') === '1') {
      document.addEventListener('click', function (e) {
        if (!e.detail || (e.clientX === 0 && e.clientY === 0)) return;
        api.drop(e.clientX, e.clientY, .85 + Math.random() * .3);
      }, true);
    }

    /* the shared scratch card bursts into petals when revealed; a memory-map pin lets a few fall */
    if (canvas.getAttribute('data-bursts') === '1') {
      document.addEventListener('inv-scratch:reveal', function (e) {
        var r = e.target.getBoundingClientRect();
        api.drop(r.left + r.width / 2, r.top + r.height * .45, 1.2);
      });
      document.addEventListener('inv-map:open', function (e) {
        var pin = e.target.querySelectorAll('.inv-map__pin')[e.detail && e.detail.index];
        if (!pin) return;
        var r = pin.getBoundingClientRect();
        api.drop(r.left + r.width / 2, r.top + r.height / 2, .6);
      });
    }
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

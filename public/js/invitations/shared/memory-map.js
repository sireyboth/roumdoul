/* Shared memory map — resources/views/components/memory-map.blade.php (styles: css/invitations/shared/memory-map.css).
   Paints the paper (hills, contour rings, an optional river, compass, label, grain) on each [data-inv-map] canvas,
   pops the pins in when the map scrolls into view, and opens the polaroid when a pin is tapped.

   window.InvMap
     init(root)   set up any maps added later (runs by itself on load and after wire:navigate)

   A pin tap fires a bubbling `inv-map:open` event (detail: { index, place }) — hook a sound or petals to it.
   Tapping the paper, the ✕ or pressing Escape closes the polaroid. Colours come from the --inv-map-* variables. */
(function () {
  'use strict';
  if (window.InvMap) { window.InvMap.init(document); return; }

  var muted = false, sounds = {};
  var play = function (url) { // a short sfx with a little pitch variety; preloaded on first use per URL
    if (!url || muted) return;
    var a = sounds[url] || (sounds[url] = new Audio(url));
    try { a.currentTime = 0; a.volume = .6; a.playbackRate = .95 + Math.random() * .1; var p = a.play(); if (p && p.catch) p.catch(function () {}); } catch (e) {}
  };

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var rng = function (seed) { // small deterministic PRNG, so the map looks the same on every visit
    var s = seed >>> 0 || 1;
    return function () { s = (s + 0x6D2B79F5) >>> 0; var t = s; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
  };

  var noise = null;
  var grain = function () {
    if (noise) return noise;
    noise = document.createElement('canvas'); noise.width = noise.height = 128;
    var x = noise.getContext('2d'), d = x.createImageData(128, 128), r = rng(3);
    for (var i = 0; i < d.data.length; i += 4) { var v = 110 + r() * 110; d.data[i] = d.data[i + 1] = d.data[i + 2] = v; d.data[i + 3] = 22; }
    x.putImageData(d, 0, 0);
    return noise;
  };

  // a CSS colour → "r,g,b" (so it can be drawn with any alpha)
  var probe = null;
  var rgb = function (css, fallback) {
    if (!probe) { probe = document.createElement('canvas').getContext('2d'); }
    probe.fillStyle = fallback; probe.fillStyle = (css || '').trim() || fallback;
    var c = probe.fillStyle;
    if (c.charAt(0) === '#') return [1, 3, 5].map(function (k) { return parseInt(c.substr(k, 2), 16); }).join(',');
    var m = c.match(/[\d.]+/g); return m ? m.slice(0, 3).join(',') : '0,0,0';
  };

  var draw = function (map) {
    var cv = map.querySelector('.inv-map__canvas'), paper = map.querySelector('.inv-map__paper');
    var W = 800, MH = Math.round(W * paper.offsetHeight / Math.max(1, paper.offsetWidth)), ys = MH / 600;
    cv.width = W; cv.height = MH;
    var cs = getComputedStyle(map), x = cv.getContext('2d'), r = rng(+map.getAttribute('data-seed') || 7), g;
    var paperC = rgb(cs.getPropertyValue('--inv-map-paper'), '#f1ebdd'), ink = rgb(cs.getPropertyValue('--inv-map-ink'), '#1f1e1b');
    var land = (cs.getPropertyValue('--inv-map-land') || '').trim() || 'rgba(178,205,160,.3)', landRgb = rgb(land, '#b2cda0');
    var water = rgb(cs.getPropertyValue('--inv-map-water'), '#8fb4cf');
    x.scale(1, ys);
    x.fillStyle = 'rgb(' + paperC + ')'; x.fillRect(0, 0, 800, 600);
    g = x.createRadialGradient(400, 300, 100, 400, 300, 520); g.addColorStop(0, 'rgba(255,253,248,.55)'); g.addColorStop(1, 'rgba(200,185,150,.4)');
    x.fillStyle = g; x.fillRect(0, 0, 800, 600);

    [[170, 370, 165], [380, 180, 150], [590, 400, 185], [700, 150, 105]].forEach(function (h) {
      var bx = h[0], by = h[1], br = h[2], k, a, d, px, py, rr;
      for (k = 0; k < 70; k++) {
        a = r() * 6.283; d = br * Math.sqrt(r()) * .85; px = bx + Math.cos(a) * d; py = by + Math.sin(a) * d; rr = br * (.18 + r() * .3);
        g = x.createRadialGradient(px, py, 0, px, py, rr); g.addColorStop(0, 'rgba(248,236,218,.45)'); g.addColorStop(1, 'rgba(248,236,218,0)');
        x.fillStyle = g; x.beginPath(); x.arc(px, py, rr, 0, 7); x.fill();
      }
      for (k = 0; k < 26; k++) {
        a = r() * 6.283; d = br * Math.sqrt(r()) * .6; px = bx + Math.cos(a) * d; py = by + Math.sin(a) * d; rr = br * (.12 + r() * .2);
        g = x.createRadialGradient(px, py, 0, px, py, rr); g.addColorStop(0, 'rgba(' + landRgb + ',.3)'); g.addColorStop(1, 'rgba(' + landRgb + ',0)');
        x.fillStyle = g; x.beginPath(); x.arc(px, py, rr, 0, 7); x.fill();
      }
      for (var ring = 0; ring < 3; ring++) {
        x.strokeStyle = 'rgba(' + ink + ',' + (.2 - ring * .05) + ')'; x.lineWidth = 1.4; x.beginPath();
        var N = 28, base = br * (.55 + ring * .22), ph = r() * 6;
        for (var j = 0; j <= N; j++) {
          a = j / N * 6.283; rr = base * (1 + .12 * Math.sin(a * 3 + ph) + .06 * Math.sin(a * 7 + ph * 2));
          px = bx + Math.cos(a) * rr; py = by + Math.sin(a) * rr * .8; if (j) x.lineTo(px, py); else x.moveTo(px, py);
        }
        x.stroke();
      }
    });

    if (map.getAttribute('data-river') === '1') { // a soft river winding from the top-left to the bottom-right
      var pts = [[-20, 120], [120, 160], [230, 260], [330, 300], [470, 330], [560, 440], [690, 470], [830, 560]];
      var path = function () { x.beginPath(); x.moveTo(pts[0][0], pts[0][1]);
        for (var i = 1; i < pts.length - 1; i++) x.quadraticCurveTo(pts[i][0], pts[i][1], (pts[i][0] + pts[i + 1][0]) / 2, (pts[i][1] + pts[i + 1][1]) / 2);
        x.lineTo(pts[pts.length - 1][0], pts[pts.length - 1][1]); };
      x.lineCap = 'round'; x.lineJoin = 'round';
      path(); x.strokeStyle = 'rgba(' + water + ',.28)'; x.lineWidth = 46; x.stroke();
      path(); x.strokeStyle = 'rgba(' + water + ',.55)'; x.lineWidth = 22; x.stroke();
      path(); x.strokeStyle = 'rgba(255,255,255,.35)'; x.lineWidth = 3; x.setLineDash([18, 22]); x.stroke(); x.setLineDash([]);
    }

    x.strokeStyle = 'rgba(' + ink + ',.45)'; x.lineWidth = 1.5; x.fillStyle = 'rgba(' + ink + ',.55)';
    var cx = 730, cy = 530;
    x.beginPath(); x.arc(cx, cy, 26, 0, 7); x.stroke();
    x.beginPath(); x.moveTo(cx, cy - 40); x.lineTo(cx + 7, cy); x.lineTo(cx, cy + 40); x.lineTo(cx - 7, cy); x.closePath(); x.fill();
    x.font = 'italic 16px "Cormorant Garamond", serif'; x.textAlign = 'center'; x.fillText('N', cx, cy - 48);
    var label = map.getAttribute('data-label');
    if (label) {
      x.font = 'italic 24px "Cormorant Garamond", "Kantumruy Pro", serif'; // the label can be Khmer names
      x.fillStyle = 'rgba(' + ink + ',.35)'; x.textAlign = 'left'; x.fillText(label, 40, 560);
    }
    x.setTransform(1, 0, 0, 1, 0, 0); x.fillStyle = x.createPattern(grain(), 'repeat'); x.fillRect(0, 0, W, MH);
  };

  var setup = function (map) {
    if (map._invMap) return;
    map._invMap = true;
    var card = map.querySelector('.inv-map__card'), photo = card.querySelector('.inv-map__photo'), img = photo.querySelector('img');
    var pins = map.querySelectorAll('.inv-map__pin'), active = null;

    var paint = function () { draw(map); };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(paint); else paint();
    paint();
    var lastW = window.innerWidth, t = 0;
    window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(function () { if (window.innerWidth !== lastW) { lastW = window.innerWidth; paint(); } }, 200); });

    map.classList.add('is-ready');
    if (reduce || !('IntersectionObserver' in window)) map.classList.add('is-in');
    else {
      var io = new IntersectionObserver(function (e) { if (e[0].isIntersecting) { map.classList.add('is-in'); io.disconnect(); } }, { threshold: .35 });
      io.observe(map);
    }

    var close = function () {
      if (!active) return;
      card.classList.remove('is-open'); active.classList.remove('is-active');
      var back = active; active = null; if (card.contains(document.activeElement)) back.focus();
    };
    pins.forEach(function (pin) {
      pin.addEventListener('click', function () {
        if (active === pin) { close(); return; }
        if (active) active.classList.remove('is-active');
        active = pin; pin.classList.add('is-seen', 'is-active');
        play(map.getAttribute('data-sound'));
        var name = pin.getAttribute('data-name') || '', src = pin.getAttribute('data-photo');
        card.querySelector('.inv-map__name').textContent = name;
        card.querySelector('.inv-map__date').textContent = pin.getAttribute('data-date') || '';
        card.querySelector('.inv-map__note').textContent = pin.getAttribute('data-note') || '';
        card.setAttribute('aria-label', name);
        card.classList.toggle('no-photo', !src);
        if (src) { img.src = src; photo.setAttribute('data-full', src); photo.setAttribute('data-cap', name); photo.setAttribute('aria-label', 'Open photo: ' + name); }
        card.classList.remove('is-open'); void card.offsetWidth; card.classList.add('is-open');
        map.dispatchEvent(new CustomEvent('inv-map:open', { bubbles: true, detail: { index: +pin.getAttribute('data-i'), place: { name: name, date: pin.getAttribute('data-date'), note: pin.getAttribute('data-note'), photo: src } } }));
      });
    });
    // clicks inside the polaroid still bubble (so a template's photo preview can open its photo)
    card.addEventListener('click', function (e) { if (e.target.closest('.inv-map__x')) close(); });
    map.querySelector('.inv-map__paper').addEventListener('click', function (e) { if (!e.target.closest('.inv-map__card, .inv-map__pin')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && active && !document.querySelector('[role="dialog"][aria-modal="true"]:not([hidden])')) close(); });
  };

  var init = function (root) { (root || document).querySelectorAll('[data-inv-map]').forEach(setup); };
  window.InvMap = { init: init, setMuted: function (on) { muted = !!on; }, get muted() { return muted; } };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(document); });
  else init(document);
  document.addEventListener('livewire:navigated', function () { init(document); });
})();

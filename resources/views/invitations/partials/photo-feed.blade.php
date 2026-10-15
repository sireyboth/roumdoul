{{--
  Vertical photo feed: one tall, rounded photo per row, the next one peeking in at the bottom.
  Self-contained (own CSS + vanilla JS, no GSAP) so any template can drop it in.

  Expects:
    $photos  Collection|array of absolute image URLs (1–30)
    $alt     base alt text
  Optional:
    $snap    'none' | 'proximity' | 'mandatory'   (scroll-snap on the page; default 'none')
--}}
@php
    $pfPhotos = collect($photos ?? [])->filter()->values();
    $pfAlt = trim($alt ?? '') ?: 'Photo';
    $pfSnap = in_array($snap ?? 'none', ['none', 'proximity', 'mandatory'], true) ? ($snap ?? 'none') : 'none';
@endphp
@if ($pfPhotos->isNotEmpty())
<div class="pf" data-pf data-snap="{{ $pfSnap }}">
  <div class="pf-bg" aria-hidden="true"><i></i><i></i></div>
  <div class="pf-bloom pf-bloom-top" aria-hidden="true">
    <svg viewBox="0 0 120 24"><path d="M0 12h46M74 12h46" stroke="currentColor" stroke-width="1" fill="none"/><path d="M60 3c4 4 4 14 0 18-4-4-4-14 0-18Zm-9 5c6 1 9 7 9 13-6-1-9-7-9-13Zm18 0c-6 1-9 7-9 13 6-1 9-7 9-13Z" fill="currentColor"/></svg>
  </div>

  <button type="button" class="pf-auto" data-pf-auto aria-pressed="false" aria-label="Auto scroll">
    <span class="pf-auto-ic" aria-hidden="true">&#9654;</span><span class="pf-auto-tx">Auto</span>
  </button>

  <ol class="pf-list">
    @foreach ($pfPhotos as $i => $url)
      <li class="pf-item">
        <button type="button" class="pf-ph" data-pf-open="{{ $i }}" aria-label="{{ $pfAlt }} {{ $i + 1 }} / {{ $pfPhotos->count() }}">
          <img src="{{ $url }}" alt="{{ $pfAlt }} {{ $i + 1 }}"
               @if ($i > 0) loading="lazy" @else fetchpriority="high" @endif decoding="async" draggable="false">
        </button>
      </li>
    @endforeach
  </ol>

  <div class="pf-bloom pf-bloom-bot" aria-hidden="true">
    <svg viewBox="0 0 120 24"><path d="M0 12h46M74 12h46" stroke="currentColor" stroke-width="1" fill="none"/><path d="M60 3c4 4 4 14 0 18-4-4-4-14 0-18Zm-9 5c6 1 9 7 9 13-6-1-9-7-9-13Zm18 0c-6 1-9 7-9 13 6-1 9-7 9-13Z" fill="currentColor"/></svg>
  </div>
</div>

<style>
  .pf{ --pf-h:66vh; --pf-gap:24px; --pf-r:16px; --pf-gold:#C9A24D; --pf-blush:#F4D9D6;
       position:relative; margin:0 -26px; padding:8px 0 4px; isolation:isolate; }
  @supports (height:1dvh){ .pf{ --pf-h:66dvh; } }
  .pf-bg{ position:absolute; inset:-40px -26px; z-index:-1; overflow:hidden; pointer-events:none;
          background:linear-gradient(180deg,#fbf3ea,#f8e9e4 55%,#fbf3ea); }
  .pf-bg i{ position:absolute; inset:-30px; background-size:cover; background-position:center;
            filter:blur(28px) saturate(.9) brightness(1.15); opacity:0; transition:opacity .9s ease; transform:translateZ(0); }
  .pf-bg i.on{ opacity:.5; }
  .pf-bg::after{ content:""; position:absolute; inset:0; background:rgba(251,243,234,.55); }
  .pf-bloom{ display:flex; justify-content:center; color:var(--pf-gold); padding:6px 0 18px; }
  .pf-bloom svg{ width:150px; height:30px; }
  .pf-bloom-bot{ padding:20px 0 6px; }
  .pf-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:var(--pf-gap); }
  .pf-item{ margin:0 14px; scroll-snap-align:center; }
  .pf-ph{ display:block; width:100%; height:var(--pf-h); min-height:320px; max-height:calc(var(--pf-h) + 200px); padding:0; border:0; cursor:zoom-in;
          border-radius:var(--pf-r); overflow:hidden; background:#efe3d6; position:relative;
          box-shadow:0 12px 30px rgba(74,13,13,.18), 0 2px 6px rgba(74,13,13,.10);
          -webkit-tap-highlight-color:transparent; contain:layout paint; }
  .pf-ph::after{ content:""; position:absolute; inset:0; border-radius:inherit; box-shadow:inset 0 0 0 1px rgba(201,162,77,.45); pointer-events:none; }
  .pf-ph img{ width:100%; height:100%; object-fit:cover; display:block; user-select:none; -webkit-user-drag:none; }
  .pf-ph:focus-visible{ outline:2px solid var(--pf-gold); outline-offset:3px; }

  /* entrance: only when JS is on, so no-JS visitors still see every photo */
  .pf-js .pf-item{ opacity:0; transform:translateY(28px); transition:opacity .8s cubic-bezier(.22,.8,.3,1), transform .8s cubic-bezier(.22,.8,.3,1); }
  .pf-js .pf-item.in{ opacity:1; transform:none; }
  @media (prefers-reduced-motion:reduce){
    .pf-js .pf-item{ opacity:1; transform:none; transition:none; }
    .pf-bg i{ transition:none; }
  }

  .pf-auto{ position:sticky; top:12px; z-index:5; display:flex; align-items:center; gap:6px; margin:0 14px 12px auto; width:max-content;
            padding:7px 13px; border:1px solid var(--pf-gold); border-radius:999px; background:rgba(255,250,243,.88);
            -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); color:#7A1515; font:600 .8rem/1 system-ui,sans-serif; cursor:pointer; }
  .pf-auto[aria-pressed="true"]{ background:#7A1515; color:#F8F1E3; }
  .pf-auto-ic{ font-size:.7rem; }

  /* lightbox (appended to <body> by the script) */
  .pf-lb{ position:fixed; inset:0; z-index:9999; display:none; background:rgba(20,6,6,.98); touch-action:none; overscroll-behavior:contain; }
  .pf-lb.open{ display:block; }
  .pf-lb-track{ position:absolute; inset:0; display:flex; will-change:transform; }
  .pf-lb-slide{ flex:0 0 100%; width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:48px 0; }
  .pf-lb-slide img{ max-width:100%; max-height:100%; object-fit:contain; user-select:none; -webkit-user-drag:none; }
  .pf-lb button{ position:absolute; z-index:2; border:0; background:rgba(255,255,255,.14); color:#fff; width:44px; height:44px; border-radius:50%; font-size:1.4rem; cursor:pointer; }
  .pf-lb-x{ top:max(10px,env(safe-area-inset-top)); right:10px; }
  .pf-lb-p{ left:8px; top:50%; margin-top:-22px; } .pf-lb-n{ right:8px; top:50%; margin-top:-22px; }
  .pf-lb-c{ position:absolute; top:max(20px,env(safe-area-inset-top)); left:0; right:0; text-align:center; color:#fff; font:500 .85rem system-ui,sans-serif; opacity:.8; pointer-events:none; }
  @media (max-width:600px){ .pf-lb-p,.pf-lb-n{ display:none; } }

  html[data-pf-snap="proximity"]{ scroll-snap-type:y proximity; }
  html[data-pf-snap="mandatory"]{ scroll-snap-type:y mandatory; }
</style>

<script>
(function () {
  var root = document.querySelector('[data-pf]');
  if (!root || root.__pf) return;
  root.__pf = true;
  var doc = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var items = [].slice.call(root.querySelectorAll('.pf-item'));
  var imgs = items.map(function (li) { return li.querySelector('img'); });
  var bgs = [].slice.call(root.querySelectorAll('.pf-bg i'));
  var q = new URLSearchParams(location.search);

  // scroll-snap choice (data attribute from the template; ?snap= overrides for testing)
  var snap = q.get('snap') || root.getAttribute('data-snap');
  if (snap === 'proximity' || snap === 'mandatory') doc.setAttribute('data-pf-snap', snap);

  doc.classList.add('pf-js');

  // --- entrance + blurred backdrop follows the photo nearest the viewport centre ---
  var activeIdx = -1, layer = 0;
  function setBg(i) {
    if (i === activeIdx || !bgs.length) return;
    activeIdx = i;
    var next = bgs[layer = 1 - layer], prev = bgs[1 - layer];
    next.style.backgroundImage = 'url("' + (imgs[i].currentSrc || imgs[i].src) + '")';
    next.classList.add('on'); prev.classList.remove('on');
  }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { rootMargin: '0px', threshold: 0.01 });
    items.forEach(function (li) { io.observe(li); });
    var mid = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) setBg(items.indexOf(e.target)); });
    }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
    items.forEach(function (li) { mid.observe(li); });
  } else {
    items.forEach(function (li) { li.classList.add('in'); });
  }

  // --- lightbox ---
  var lb, track, counter, cur = 0, lastFocus;
  function build() {
    lb = document.createElement('div');
    lb.className = 'pf-lb'; lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true');
    lb.innerHTML = '<div class="pf-lb-c"></div><button class="pf-lb-x" aria-label="Close">&times;</button>' +
      '<button class="pf-lb-p" aria-label="Previous">&#8249;</button><button class="pf-lb-n" aria-label="Next">&#8250;</button>' +
      '<div class="pf-lb-track"></div>';
    track = lb.querySelector('.pf-lb-track'); counter = lb.querySelector('.pf-lb-c');
    imgs.forEach(function (im, i) {
      var s = document.createElement('div'); s.className = 'pf-lb-slide';
      var x = document.createElement('img'); x.alt = im.alt; x.draggable = false; x.dataset.src = im.currentSrc || im.src;
      s.appendChild(x); track.appendChild(s);
    });
    document.body.appendChild(lb);
    lb.querySelector('.pf-lb-x').onclick = close;
    lb.querySelector('.pf-lb-p').onclick = function () { go(cur - 1); };
    lb.querySelector('.pf-lb-n').onclick = function () { go(cur + 1); };
    var sx = 0, sy = 0, dx = 0, drag = false, axis = '', w = 1;
    lb.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button')) return;
      drag = true; axis = ''; sx = e.clientX; sy = e.clientY; dx = 0; w = lb.clientWidth;
      track.style.transition = 'none';
    });
    lb.addEventListener('pointermove', function (e) {
      if (!drag) return;
      var mx = e.clientX - sx, my = e.clientY - sy;
      if (!axis && (Math.abs(mx) > 6 || Math.abs(my) > 6)) axis = Math.abs(mx) > Math.abs(my) ? 'x' : 'y';
      if (axis === 'x') { dx = mx; track.style.transform = 'translateX(' + (-cur * w + dx) + 'px)'; }
    });
    function end(e) {
      if (!drag) return; drag = false;
      track.style.transition = reduce ? 'none' : 'transform .3s cubic-bezier(.22,.8,.3,1)';
      var my = e.clientY - sy;
      if (axis === 'y' && Math.abs(my) > 90) return close();
      if (axis === '' && Math.abs(dx) < 6 && e.target.tagName !== 'IMG') return close();
      if (dx < -w * 0.18) go(cur + 1); else if (dx > w * 0.18) go(cur - 1); else go(cur);
    }
    lb.addEventListener('pointerup', end); lb.addEventListener('pointercancel', end);
  }
  function load(i) {
    var s = track.children[i]; if (!s) return;
    var im = s.firstChild; if (!im.src) im.src = im.dataset.src;
  }
  function go(i) {
    cur = Math.max(0, Math.min(imgs.length - 1, i));
    track.style.transform = 'translateX(' + (-cur * 100) + '%)';
    counter.textContent = (cur + 1) + ' / ' + imgs.length;
    load(cur); load(cur - 1); load(cur + 1);
  }
  function key(e) {
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowRight') go(cur + 1);
    else if (e.key === 'ArrowLeft') go(cur - 1);
  }
  function open(i) {
    stopAuto();
    if (!lb) build();
    lastFocus = document.activeElement;
    lb.classList.add('open');
    doc.style.overflow = 'hidden';
    track.style.transition = 'none'; go(i);
    document.addEventListener('keydown', key);
    lb.querySelector('.pf-lb-x').focus();
  }
  function close() {
    lb.classList.remove('open'); doc.style.overflow = '';
    document.removeEventListener('keydown', key);
    if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
  }
  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pf-open]');
    if (b) open(+b.getAttribute('data-pf-open'));
  });

  // --- auto-scroll (for screen-recording); any touch/wheel/key stops it ---
  var btn = root.querySelector('[data-pf-auto]'), raf = 0, pos = 0, last = 0, SPEED = 55; // px per second
  function tick(t) {
    var dt = Math.min(64, t - last) / 1000; last = t;
    pos += SPEED * dt;
    var y = Math.floor(pos);
    window.scrollTo(0, y);
    if (y >= doc.scrollHeight - window.innerHeight - 1) return stopAuto();
    raf = requestAnimationFrame(tick);
  }
  function startAuto() {
    if (raf) return;
    pos = window.scrollY; last = performance.now();
    btn.setAttribute('aria-pressed', 'true'); btn.querySelector('.pf-auto-tx').textContent = 'Stop';
    doc.style.scrollSnapType = 'none';
    ['touchstart', 'wheel', 'keydown', 'mousedown'].forEach(function (n) { window.addEventListener(n, onUser, { passive: true }); });
    raf = requestAnimationFrame(tick);
  }
  function stopAuto() {
    if (!raf) return;
    cancelAnimationFrame(raf); raf = 0;
    btn.setAttribute('aria-pressed', 'false'); btn.querySelector('.pf-auto-tx').textContent = 'Auto';
    doc.style.scrollSnapType = '';
    ['touchstart', 'wheel', 'keydown', 'mousedown'].forEach(function (n) { window.removeEventListener(n, onUser); });
  }
  function onUser(e) { if (e.target instanceof Node && btn.contains(e.target)) return; stopAuto(); }
  btn.addEventListener('click', function () { raf ? stopAuto() : startAuto(); });
  if (q.get('autoscroll') === '1') {
    root.scrollIntoView({ block: 'start' });
    setTimeout(startAuto, 800);
  }
})();
</script>
@endif

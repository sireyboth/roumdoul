/* Invite Kit — the parts every invitation template repeats, in one tested place.
   Loaded by <x-invite-kit> (resources/views/components/invite-kit.blade.php), which also prints the music
   <audio>, the sound button and the «now playing» pill, plus a JSON config (#ik-config: sfx URLs, youtube id).

   var kit = InviteKit({ root: document.querySelector('.xx') });   // once, in the template's own JS
   kit.sfx('tap', .3)            short sound (only after the first tap; follows the mute button)
   kit.startSong()               call on the cover's «open» (a user gesture); kit.isMuted(); kit.onMute(fn)
   kit.lock(true|false)          stop / allow page scroll while a cover is shown
   kit.words('.sel')             split text into <span class="ik-w" style="--w:n"> for word-by-word reveals
   kit.reveal('.sel', fn?)       adds .is-in when scrolled into view (rAF + getBoundingClientRect, NOT
                                 IntersectionObserver: an element that starts fully clipped never "intersects");
                                 siblings get --stagger 0,1,2…; anything scrolled past reveals too. Call after the
                                 cover opens. fn(el) runs once per revealed element.
   kit.onScroll(fn)              fn({ y, vh, velocity }) every animation frame the page scrolls
   kit.lightbox()                every <button data-ik-photo data-full="url" data-cap="…" data-group="g" data-g="i">
                                 opens a fullscreen preview zooming out of the tapped photo (FLIP), prev/next,
                                 drag/swipe, ←/→/Esc, «៣ / ៩» counter, caption; closing flies back. The markup is
                                 built here; theme it with --ik-* variables (invite-kit.css).
   kit.countdown(el, onDone?)    el[data-target=ISO date] with children [data-u=d|h|m|s]; Khmer digits; a changed
                                 digit gets .flip re-triggered
   kit.rsvp({ opts, form, note, url, onDone(status, ok) })   status buttons [data-status=yes|no] inside opts;
                                 POSTs JSON {status, note} with the CSRF header; url null (demo) = success
   kit.copy(text, okMessage?)    clipboard + toast;  kit.toast(msg)
   kit.burst(x, y, opts?)        a puff of DOM particles (hearts by default): { n, chars, colors, spread }
   kit.fly(fromEl, toEl, opts?)  shared-element flight: the cover photo flies into the hero photo (Promise)
   kit.settle(fn)                run fn once layout + first paint are done (start motions after heavy DOM work)
   kit.khDigits(v)               '15' → '១៥'
   kit.reduce                    true when the visitor prefers reduced motion
*/
(function () {
  'use strict';
  if (window.InviteKit) return;

  window.InviteKit = function (opt) {
    opt = opt || {};
    var root = opt.root || document.body;
    var config = {};
    try { config = JSON.parse((document.getElementById('ik-config') || {}).textContent || '{}'); } catch (e) { config = {}; }
    var sfxUrls = Object.assign({}, config.sfx || {}, opt.sfx || {});
    var reduce = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
    var khDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
    var kit = { reduce: reduce, khDigits: khDigits };

    /* ───────── sound ───────── */
    var muted = false, unlocked = false, pool = {}, muteFns = [];
    kit.sfx = function (name, vol) {
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
    var audio = document.getElementById('ik-audio');
    var soundBtn = document.getElementById('ik-sound');
    var now = document.getElementById('ik-now');
    var yt = null, playing = false, wanted = true, nowT = null;
    var setPlaying = function (on) {
      playing = on;
      if (soundBtn) soundBtn.classList.toggle('is-playing', on);
      if (now) {
        clearTimeout(nowT);
        now.classList.toggle('show', on);
        if (on) nowT = setTimeout(function () { now.classList.remove('show'); }, 6000);
      }
    };
    kit.startSong = function () {
      unlocked = true;
      if (muted || playing || !wanted) return;
      if (audio) {
        audio.volume = .7;
        var p = audio.play();
        setPlaying(true);
        if (p && p.catch) p.catch(function () { setPlaying(false); });
      } else if (config.youtube) {
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
    kit.isMuted = function () { return muted; };
    kit.onMute = function (fn) { muteFns.push(fn); };
    kit.setMuted = function (on) {
      muted = on;
      if (soundBtn) {
        soundBtn.classList.toggle('is-muted', on);
        soundBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        soundBtn.setAttribute('aria-label', on ? 'បើកសំឡេង' : 'បិទសំឡេង');
      }
      if (on) { wanted = false; stopSong(); } else { wanted = true; kit.startSong(); }
      // shared components with their own sounds follow the same button
      if (window.InvScratch) window.InvScratch.setMuted(on);
      if (window.InvMap) window.InvMap.setMuted(on);
      muteFns.forEach(function (fn) { fn(on); });
    };
    if (soundBtn) soundBtn.addEventListener('click', function () { kit.setMuted(!muted); if (!muted) kit.sfx('tap', .3); });
    var firstTouch = function () {
      ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.removeEventListener(t, firstTouch, true); });
      unlocked = true;
      // with a cover, the song waits for its «open» (the template calls kit.startSong there)
      if (!opt.cover) kit.startSong();
    };
    ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, firstTouch, true); });
    document.addEventListener('visibilitychange', function () {
      if (!audio) return;
      if (document.hidden) audio.pause();
      else if (playing) { var p = audio.play(); if (p && p.catch) p.catch(function () {}); }
    });
    root.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('[data-sfx="tap"]')) kit.sfx('tap', .3); });

    kit.lock = function (on) {
      document.documentElement.classList.toggle('ik-locked', on);
      if (on) { if ('scrollRestoration' in history) history.scrollRestoration = 'manual'; window.scrollTo(0, 0); }
    };

    /* ───────── scroll loop + reveals ───────── */
    var scrollFns = [], pending = [], revealFns = [], lastY = window.scrollY, vel = 0, queued = false;
    var frame = function () {
      queued = false;
      var vh = window.innerHeight, y = window.scrollY;
      vel = vel * .6 + (y - lastY) * .4;
      lastY = y;
      if (pending.length) {
        pending = pending.filter(function (el) {
          var r = el.getBoundingClientRect();
          if ((r.top < vh * .9 && r.bottom > 0) || r.bottom <= 0) {
            el.classList.add('is-in');
            revealFns.forEach(function (fn) { fn(el); });
            return false;
          }
          return true;
        });
      }
      scrollFns.forEach(function (fn) { fn({ y: y, vh: vh, velocity: vel }); });
    };
    var queue = function () { if (!queued) { queued = true; requestAnimationFrame(frame); } };
    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);
    window.addEventListener('load', queue);
    kit.onScroll = function (fn) { scrollFns.push(fn); queue(); };
    kit.reveal = function (sel, fn) {
      var els = Array.prototype.slice.call(typeof sel === 'string' ? root.querySelectorAll(sel) : sel);
      els.forEach(function (el) {
        if (el.style.getPropertyValue('--stagger')) return;
        var sibs = Array.prototype.filter.call(el.parentNode.children, function (c) { return els.indexOf(c) > -1; });
        el.style.setProperty('--stagger', Math.min(sibs.indexOf(el), 8));
      });
      if (fn) revealFns.push(fn);
      if (reduce) { els.forEach(function (el) { el.classList.add('is-in'); if (fn) fn(el); }); return; }
      pending = pending.concat(els);
      queue();
    };
    kit.words = function (sel) {
      root.querySelectorAll(sel).forEach(function (el) {
        if (el.querySelector('.ik-w')) return;
        var parts = el.textContent.split(/(\s+)/), w = 0;
        el.textContent = '';
        parts.forEach(function (p) {
          if (!p) return;
          if (/^\s+$/.test(p)) { el.appendChild(document.createTextNode(p)); return; }
          var s = document.createElement('span');
          s.className = 'ik-w'; s.textContent = p; s.style.setProperty('--w', Math.min(w++, 60));
          el.appendChild(s);
        });
      });
    };

    /* ───────── toast, copy, burst ───────── */
    var toastEl = null, toastT = null;
    kit.toast = function (msg) {
      if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'ik-toast'; toastEl.setAttribute('role', 'status'); root.appendChild(toastEl); }
      toastEl.textContent = msg;
      toastEl.classList.add('show');
      clearTimeout(toastT);
      toastT = setTimeout(function () { toastEl.classList.remove('show'); }, 2200);
    };
    kit.copy = function (text, okMsg) {
      var ok = function () { kit.toast(okMsg || 'បានចម្លង ✓'); kit.sfx('tap', .35); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(ok, function () { kit.toast(text); });
      else kit.toast(text);
    };
    kit.burst = function (x, y, o) {
      if (reduce) return;
      o = o || {};
      var n = o.n || 12, chars = o.chars || ['♥'], colors = o.colors || [getComputedStyle(root).getPropertyValue('--ik-accent').trim() || '#d65f7f', '#fff'];
      for (var i = 0; i < n; i++) {
        var s = document.createElement('span');
        s.className = 'ik-burst';
        s.textContent = chars[i % chars.length];
        var a = Math.random() * Math.PI * 2, d = (o.spread || 110) * (.5 + Math.random() * .7);
        s.style.cssText = 'left:' + x + 'px;top:' + y + 'px;--x:' + Math.cos(a) * d + 'px;--y:' + (Math.sin(a) * d - 40) + 'px;--r:' + (Math.random() * 80 - 40) + 'deg;--s:' + (12 + Math.random() * 14) + 'px;color:' + colors[i % colors.length];
        document.body.appendChild(s);
        setTimeout(function (el) { return function () { el.remove(); }; }(s), 1300);
      }
    };

    /* ───────── countdown ───────── */
    kit.countdown = function (el, onDone) {
      if (!el) return;
      var target = new Date(el.getAttribute('data-target')).getTime(), cells = {};
      el.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
      var set = function (u, v) {
        if (!cells[u]) return;
        var txt = khDigits(String(v).padStart(2, '0'));
        if (cells[u].textContent === txt) return;
        cells[u].textContent = txt;
        cells[u].classList.remove('flip'); void cells[u].offsetWidth; cells[u].classList.add('flip');
      };
      var tick = function () {
        var diff = Math.max(0, target - Date.now());
        if (!diff) { if (onDone) onDone(); return; }
        var s = Math.floor(diff / 1000);
        set('d', Math.floor(s / 86400)); set('h', Math.floor(s % 86400 / 3600)); set('m', Math.floor(s % 3600 / 60)); set('s', s % 60);
        setTimeout(tick, 1000);
      };
      tick();
    };

    /* ───────── RSVP ───────── */
    kit.rsvp = function (o) {
      if (!o || !o.opts) return;
      var chosen = null;
      o.opts.addEventListener('click', function (e) {
        var b = e.target.closest('[data-status]'); if (!b) return;
        chosen = b.getAttribute('data-status');
        o.opts.querySelectorAll('[data-status]').forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
        if (o.form) o.form.classList.add('show');
        kit.sfx('tap', .35);
        if (o.onChoose) o.onChoose(chosen, b);
      });
      if (!o.form) return;
      o.form.addEventListener('submit', function (e) {
        e.preventDefault(); if (!chosen) return;
        var btn = o.form.querySelector('[type=submit], button'); if (btn) btn.disabled = true;
        var done = function (ok) { if (btn) btn.disabled = false; if (ok) kit.sfx('chime', .3); if (o.onDone) o.onDone(chosen, ok); };
        if (!o.url) { done(true); return; } // demo page: nothing to save
        fetch(o.url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
          body: JSON.stringify({ status: chosen, note: (o.note && o.note.value) || null })
        }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
      });
    };

    /* ───────── shared-element flight (cover photo → hero) ─────────
       kit.fly(fromEl, toEl, { duration, lift, radiusFrom, radiusTo }).then(...)
       A fixed clone of fromEl's <img> flies from fromEl's box (rising toward the camera on the way) into toEl's exact
       box; fromEl is hidden (visibility) meanwhile; the promise resolves when it lands (clone fades after). Show toEl
       on resolve. Without an image / animation support it resolves at once. */
    kit.fly = function (fromEl, toEl, o) {
      o = o || {};
      return new Promise(function (resolve) {
        var img = fromEl && fromEl.querySelector('img');
        if (!img || !toEl || reduce || !document.body.animate) { resolve(); return; }
        var a = fromEl.getBoundingClientRect(), b = toEl.getBoundingClientRect();
        var clone = document.createElement('div');
        clone.className = 'ik-fly';
        clone.style.cssText = 'position:fixed;z-index:96;overflow:hidden;pointer-events:none;box-shadow:0 30px 60px -24px rgba(0,0,0,.5)';
        var ci = document.createElement('img');
        ci.src = img.currentSrc || img.src; ci.alt = '';
        ci.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
        clone.appendChild(ci);
        document.body.appendChild(clone);
        fromEl.style.visibility = 'hidden';
        var box = function (r, k, rot, rad) {
          var w = r.width * k, h = r.height * k;
          return { left: (r.left + r.width / 2 - w / 2) + 'px', top: (r.top + r.height / 2 - h / 2) + 'px', width: w + 'px', height: h + 'px', transform: 'rotate(' + rot + 'deg)', borderRadius: rad };
        };
        var rf = o.radiusFrom || getComputedStyle(fromEl).borderRadius || '0px', rt = o.radiusTo || getComputedStyle(toEl).borderRadius || '0px';
        var mid = { left: a.left, top: a.top - innerHeight * (o.lift == null ? .04 : o.lift), width: a.width, height: a.height };
        clone.animate([box(a, 1, 0, rf), Object.assign(box(mid, 1.14, -2, rt), { offset: .4 }), box(b, 1, 0, rt)],
          { duration: o.duration || 1500, easing: 'cubic-bezier(.6,0,.2,1)', fill: 'forwards' })
          .finished.then(function () {
            resolve();
            clone.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 260, fill: 'forwards' }).finished.then(function () { clone.remove(); });
          }, function () { clone.remove(); resolve(); });
      });
    };

    /* run fn after the browser has finished laying out + painting (use before starting a motion right after
       showing a big part of the page, so the first heavy frame doesn't land mid-animation) */
    kit.settle = function (fn) { requestAnimationFrame(function () { requestAnimationFrame(function () { setTimeout(function () { requestAnimationFrame(fn); }, 120); }); }); };

    /* ───────── photo preview (FLIP) ───────── */
    kit.lightbox = function () {
      var btns = root.querySelectorAll('[data-ik-photo]');
      if (!btns.length) return;
      var lb = document.createElement('div');
      lb.className = 'ik-lb'; lb.hidden = true;
      lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true'); lb.setAttribute('aria-label', 'រូបភាព');
      lb.innerHTML = '<div class="ik-lb-bg"></div><figure class="ik-lb-fig"><img alt="" draggable="false"><figcaption></figcaption></figure>'
        + '<p class="ik-lb-count" aria-live="polite"></p><button type="button" class="ik-lb-nav prev" aria-label="រូបមុន">&#8249;</button>'
        + '<button type="button" class="ik-lb-nav next" aria-label="រូបបន្ទាប់">&#8250;</button><button type="button" class="ik-lb-x" aria-label="បិទ">&times;</button>';
      root.appendChild(lb);
      var fig = lb.querySelector('figure'), img = fig.querySelector('img'), cap = fig.querySelector('figcaption');
      var count = lb.querySelector('.ik-lb-count'), prev = lb.querySelector('.prev'), next = lb.querySelector('.next'), x = lb.querySelector('.ik-lb-x');
      var groups = {};
      btns.forEach(function (b) {
        var g = b.getAttribute('data-group') || 'photos', i = +(b.getAttribute('data-g') || 0);
        groups[g] = groups[g] || [];
        if (!groups[g][i]) groups[g][i] = { src: b.getAttribute('data-full'), cap: b.getAttribute('data-cap') || '' };
        b.addEventListener('click', function () { open(b); });
      });
      Object.keys(groups).forEach(function (g) { groups[g] = groups[g].filter(Boolean); });
      var group = null, idx = 0, last = null, busy = false;
      var fill = function () {
        var it = groups[group][idx], n = groups[group].length;
        img.src = it.src; img.alt = it.cap; cap.textContent = it.cap; cap.hidden = !it.cap;
        cap.classList.toggle('ik-kh', /[ក-៿]/.test(it.cap));
        count.textContent = n > 1 ? khDigits(idx + 1) + ' / ' + khDigits(n) : '';
        prev.hidden = next.hidden = n < 2;
        [idx - 1, idx + 1].forEach(function (j) { var t = groups[group][(j + n) % n]; if (t) (new Image()).src = t.src; });
      };
      var decoded = function () { return Promise.race([img.decode ? img.decode().catch(function () {}) : Promise.resolve(), new Promise(function (r) { setTimeout(r, 400); })]); };
      var flipFrom = function (rect) {
        var f = fig.getBoundingClientRect();
        var sc = Math.max(.05, Math.min(rect.width / f.width, rect.height / f.height));
        return 'translate(' + (rect.left + rect.width / 2 - f.left - f.width / 2) + 'px,' + (rect.top + rect.height / 2 - f.top - f.height / 2) + 'px) scale(' + sc + ')';
      };
      var visible = function (el) { var r = el.getBoundingClientRect(); return r.width > 0 && r.bottom > 0 && r.top < innerHeight && getComputedStyle(el).visibility !== 'hidden'; };
      var origin = function () {
        var list = root.querySelectorAll('[data-ik-photo]');
        for (var k = 0; k < list.length; k++) {
          var b = list[k];
          if ((b.getAttribute('data-group') || 'photos') === group && groups[group][idx].src === b.getAttribute('data-full') && visible(b)) return b;
        }
        return null;
      };
      var open = function (b) {
        if (busy) return;
        group = b.getAttribute('data-group') || 'photos';
        idx = Math.max(0, groups[group].findIndex(function (t) { return t.src === b.getAttribute('data-full'); }));
        last = b; fill(); lb.hidden = false; lb.classList.remove('show');
        kit.sfx('card', .35); busy = true;
        decoded().then(function () {
          lb.classList.add('show');
          if (reduce || !fig.animate) { busy = false; return; }
          fig.animate([{ transform: flipFrom(b.getBoundingClientRect()), opacity: .6 }, { transform: 'none', opacity: 1 }], { duration: 560, easing: 'cubic-bezier(.2,.85,.25,1)' })
            .finished.then(function () { busy = false; }, function () { busy = false; });
        });
        x.focus({ preventScroll: true });
      };
      var close = function () {
        if (lb.hidden || busy) return;
        var o = origin();
        lb.classList.remove('show'); kit.sfx('tap', .25);
        var done = function () { lb.hidden = true; if (last) last.focus({ preventScroll: true }); };
        if (reduce || !fig.animate) { setTimeout(done, 250); return; }
        busy = true;
        fig.animate([{ transform: 'none', opacity: 1 }, { transform: o ? flipFrom(o.getBoundingClientRect()) : 'scale(.85)', opacity: o ? .9 : 0 }],
          { duration: o ? 460 : 300, easing: 'cubic-bezier(.5,0,.2,1)', fill: 'forwards' })
          .finished.then(function (a) { busy = false; done(); a.cancel(); }, function () { busy = false; done(); });
      };
      var go = function (dir, fromT) {
        var n = groups[group].length;
        if (n < 2 || busy) return;
        busy = true; kit.sfx('card', .3);
        var out = reduce || !fig.animate ? null : fig.animate([{ transform: fromT || 'none', opacity: 1 }, { transform: 'translateX(' + (-dir * 70) + '%) rotate(' + (-dir * 6) + 'deg)', opacity: 0 }], { duration: 240, easing: 'cubic-bezier(.5,0,.8,.4)', fill: 'forwards' });
        var swap = function () {
          idx = (idx + dir + n) % n; fill();
          decoded().then(function () {
            if (out) out.cancel();
            if (reduce || !fig.animate) { busy = false; return; }
            fig.animate([{ transform: 'translateX(' + (dir * 70) + '%) rotate(' + (dir * 6) + 'deg)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 460, easing: 'cubic-bezier(.2,1.2,.35,1)' })
              .finished.then(function () { busy = false; }, function () { busy = false; });
          });
        };
        if (out) out.finished.then(swap, swap); else swap();
      };
      prev.addEventListener('click', function () { go(-1); });
      next.addEventListener('click', function () { go(1); });
      x.addEventListener('click', close);
      lb.querySelector('.ik-lb-bg').addEventListener('click', close);
      document.addEventListener('keydown', function (e) {
        if (lb.hidden) return;
        if (e.key === 'Escape') close(); else if (e.key === 'ArrowLeft') go(-1); else if (e.key === 'ArrowRight') go(1);
      });
      var sx = null, dx = 0;
      fig.addEventListener('pointerdown', function (e) { if (busy) return; sx = e.clientX; dx = 0; fig.setPointerCapture(e.pointerId); });
      fig.addEventListener('pointermove', function (e) { if (sx === null) return; dx = e.clientX - sx; fig.style.transform = 'translateX(' + dx + 'px) rotate(' + dx * .03 + 'deg)'; });
      var up = function () {
        if (sx === null) return;
        sx = null;
        var t = fig.style.transform; fig.style.transform = '';
        if (Math.abs(dx) > 70 && groups[group].length > 1) go(dx < 0 ? 1 : -1, t);
        else if (Math.abs(dx) > 4 && fig.animate) fig.animate([{ transform: t }, { transform: 'none' }], { duration: 380, easing: 'cubic-bezier(.2,1.3,.4,1)' });
      };
      fig.addEventListener('pointerup', up);
      fig.addEventListener('pointercancel', up);
    };

    return kit;
  };
})();

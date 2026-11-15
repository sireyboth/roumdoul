/* Christmas party invitation — resources/views/invitations/templates/christmas.blade.php
   Browser-only (the server sends the page without flakes or video sources, so nothing here runs
   during rendering):
   - <Invitation>        open / back-to-cover state of the two sections
   - backgrounds         portrait or landscape video per screen shape; section 2's only loads on open
   - <SnowFall>          snowflake.svg flakes over the whole invitation height
   - live preview        the dashboard editor (?editor=1 iframe) sends form values with postMessage
   - PNG download        the editor asks for cover.png + details.png, drawn here on a canvas
   - music, RSVP */
(function () {
  'use strict';

  var root = document.getElementById('xm');
  if (!root) return;

  var cfg = {};
  try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { cfg = {}; }

  var noop = function () {};
  var reducedMq = window.matchMedia('(prefers-reduced-motion: reduce)');
  var cover = document.getElementById('xm-cover');
  var details = document.getElementById('xm-details');
  var openBtn = document.getElementById('xm-open');
  var backBtn = document.getElementById('xm-back');
  var snow = document.getElementById('xm-snow');
  var debounce = function (fn, ms) {
    var t = null;
    return function () { clearTimeout(t); t = setTimeout(fn, ms); };
  };

  /* --- backgrounds ---------------------------------------------------------------------------
     Same breakpoint as the CSS (min-aspect-ratio: 5/4). The cover video gets its source right
     away; section 2's (preload="none", no src) only when the invitation is opened. */
  var coverVideo = cover.querySelector('video[data-bg]');
  var detailsVideo = details.querySelector('video[data-bg]');
  var isLandscape = function () { return window.innerWidth >= window.innerHeight * 1.25; };

  var loadBg = function (video) {
    if (!video) return;
    var src = video.getAttribute(isLandscape() ? 'data-landscape' : 'data-portrait');
    video.muted = true;
    if (video.getAttribute('src') !== src) {
      video.preload = 'auto';
      video.src = src;
      video.setAttribute('data-loaded', '');
    }
    if (reducedMq.matches) { video.pause(); return; } // reduced motion: a still first frame
    var p = video.play();
    if (p && p.catch) p.catch(noop);
  };

  loadBg(coverVideo);

  var wasLandscape = isLandscape();
  window.addEventListener('resize', debounce(function () {
    if (isLandscape() === wasLandscape) return;
    wasLandscape = isLandscape();
    [coverVideo, detailsVideo].forEach(function (v) { if (v && v.hasAttribute('data-loaded')) loadBg(v); });
  }, 200));

  document.addEventListener('visibilitychange', function () {
    [coverVideo, detailsVideo].forEach(function (v) {
      if (!v || !v.hasAttribute('data-loaded')) return;
      if (document.hidden || reducedMq.matches || (v === detailsVideo && details.hidden)) v.pause();
      else { var p = v.play(); if (p && p.catch) p.catch(noop); }
    });
  });

  /* --- <Invitation>: cover → details ------------------------------------------------------------
     Scrolls with window.scrollTo, not scrollIntoView, so inside the dashboard's preview iframe
     only the iframe scrolls, never the dashboard page around it. */
  var behavior = function () { return reducedMq.matches ? 'auto' : 'smooth'; };
  var pageTop = function (el) { return el.getBoundingClientRect().top + window.scrollY; };

  var reveal = function (animate) {
    if (!details.hidden) return;
    details.hidden = false;
    loadBg(detailsVideo);
    details.classList.remove('is-entering');
    if (animate) {
      void details.offsetWidth; // restart the fade-up
      details.classList.add('is-entering');
    }
    openBtn.setAttribute('aria-expanded', 'true');
  };

  var collapse = function () {
    details.hidden = true;
    details.classList.remove('is-entering');
    openBtn.setAttribute('aria-expanded', 'false');
    if (detailsVideo) detailsVideo.pause();
  };

  openBtn.addEventListener('click', function () {
    reveal(true);
    window.scrollTo({ top: pageTop(details), behavior: behavior() });
  });

  var hideTimer = null;
  backBtn.addEventListener('click', function () {
    var top = pageTop(cover);
    window.scrollTo({ top: top, behavior: behavior() });
    var done = function () {
      window.removeEventListener('scrollend', done);
      clearTimeout(hideTimer);
      if (Math.abs(window.scrollY - top) < 4) {
        collapse();
        openBtn.focus({ preventScroll: true });
      }
    };
    if ('onscrollend' in window) window.addEventListener('scrollend', done);
    clearTimeout(hideTimer);
    hideTimer = setTimeout(done, 900); // no scrollend support, or already at the top
  });

  /* --- <SnowFall> --------------------------------------------------------------------------------
     - the layer is as tall as the whole invitation (both sections once opened), so it is snowing
       wherever the guest scrolls; it is rebuilt when that height changes
     - heavy: one flake per 5px of height, capped at 300, about half on phones
     - random depth per flake: small = slower and fainter, big = faster and brighter (8–28px,
       opacity 0.4–1), each with its own sway and slow spin
     - negative delays: already snowing on the first frame
     - only transform animates; pointer-events: none (CSS); paused in a hidden tab; a still
       sprinkle for prefers-reduced-motion */
  if (snow) {
    var PX_PER_FLAKE = 5, MAX_FLAKES = 300, MIN_SIZE = 8, MAX_SIZE = 28, OVERSHOOT = 40;
    var last = { height: 0, mobile: null, reduced: null };
    var rand = function (min, max) { return min + Math.random() * (max - min); };
    snow.style.setProperty('--flake-src', 'url("' + snow.getAttribute('data-src') + '")');

    var build = function (force) {
      var height = snow.clientHeight;
      var mobile = root.clientWidth < 640, reduced = reducedMq.matches;
      var unchanged = mobile === last.mobile && reduced === last.reduced && Math.abs(height - last.height) < 40;
      if (!force && snow.childElementCount && unchanged) return;
      last = { height: height, mobile: mobile, reduced: reduced };

      var count = Math.min(MAX_FLAKES, Math.round(height / PX_PER_FLAKE));
      if (mobile) count = Math.round(count / 2);
      if (reduced) count = Math.round(count / 3);

      var fall = height + OVERSHOOT * 2;
      var frag = document.createDocumentFragment();

      for (var i = 0; i < count; i++) {
        var depth = Math.random(); // 0 = far … 1 = near
        var size = MIN_SIZE + depth * (MAX_SIZE - MIN_SIZE);
        var speed = rand(22, 34) + depth * 80; // px per second
        var duration = fall / speed;

        var flake = document.createElement('span');
        flake.className = 'xm-flake';
        flake.style.left = rand(-2, 100) + '%';
        flake.style.setProperty('--fall', fall + 'px');
        flake.style.setProperty('--dur', duration.toFixed(2) + 's');
        flake.style.setProperty('--delay', (-Math.random() * duration).toFixed(2) + 's');
        if (reduced) flake.style.top = rand(0, height) + 'px';

        var sway = document.createElement('span');
        sway.className = 'xm-flake-sway';
        sway.style.setProperty('--sway', rand(6, 18 + depth * 22).toFixed(1) + 'px');
        sway.style.setProperty('--sway-dur', rand(2.5, 5.5).toFixed(2) + 's');
        sway.style.setProperty('--sway-delay', (-Math.random() * 5).toFixed(2) + 's');

        var shape = document.createElement('span');
        shape.className = 'xm-flake-shape';
        shape.style.width = shape.style.height = size.toFixed(1) + 'px';
        shape.style.opacity = (0.4 + depth * 0.6).toFixed(2);
        shape.style.setProperty('--spin-dur', rand(8, 22).toFixed(1) + 's');
        shape.style.setProperty('--spin-dir', Math.random() < 0.5 ? 'normal' : 'reverse');

        sway.appendChild(shape);
        flake.appendChild(sway);
        frag.appendChild(flake);
      }

      snow.classList.toggle('is-still', reduced);
      snow.replaceChildren(frag);
    };

    build(true);

    if ('ResizeObserver' in window) {
      new ResizeObserver(debounce(function () { build(false); }, 150)).observe(snow);
    } else {
      window.addEventListener('resize', debounce(function () { build(false); }, 200));
    }
    var onReduced = function () { build(true); };
    if (reducedMq.addEventListener) reducedMq.addEventListener('change', onReduced); else reducedMq.addListener(onReduced);
    var onVisibility = function () { snow.classList.toggle('is-paused', document.hidden); };
    document.addEventListener('visibilitychange', onVisibility);
    onVisibility();
  }

  /* --- live preview (dashboard editor) --------------------------------------------------------
     Mirrors the PHP at the top of christmas.blade.php: same defaults, same hidden lines, same
     en-GB formatting («Saturday, 19 December 2026», «6:30 PM»). */
  var all = function (sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); };
  var setText = function (name, value) { all('[data-xm="' + name + '"]').forEach(function (el) { el.textContent = value; }); };
  var showLine = function (name, on) { all('[data-xm-line="' + name + '"]').forEach(function (el) { el.hidden = !on; }); };
  var parts = function (fmt, date) {
    var out = {};
    fmt.formatToParts(date).forEach(function (p) { out[p.type] = p.value; });
    return out;
  };
  var dateFmt = new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  var timeFmt = new Intl.DateTimeFormat('en-GB', { hour: 'numeric', minute: '2-digit', hour12: true });
  var validTime = function (hi) { return /^([01]\d|2[0-3]):[0-5]\d$/.test(hi || ''); };

  var formatDate = function (ymd) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd || '');
    if (!m) return '';
    var d = new Date(+m[1], +m[2] - 1, +m[3]);
    if (d.getMonth() !== +m[2] - 1) return '';
    var p = parts(dateFmt, d);
    return p.weekday + ', ' + p.day + ' ' + p.month + ' ' + p.year;
  };
  var formatTime = function (hi) {
    if (!validTime(hi)) return '';
    var p = parts(timeFmt, new Date(2000, 0, 1, +hi.slice(0, 2), +hi.slice(3, 5)));
    return p.hour + ':' + p.minute + ' ' + String(p.dayPeriod || '').replace(/\./g, '').toUpperCase();
  };
  var splitStart = function (value) {
    value = String(value == null ? '' : value).trim();
    var d = /^(\d{4}-\d{2}-\d{2})/.exec(value), t = /(?:^|[T ])(\d{2}:\d{2})/.exec(value);
    return [d && formatDate(d[1]) ? d[1] : null, t && validTime(t[1]) ? t[1] : null];
  };

  var apply = function (fields, guest) {
    fields = fields || {};
    var f = function (k) { return String(fields[k] == null ? '' : fields[k]).trim(); };
    var locked = function (k) { return (cfg.locked || []).indexOf(k) !== -1; };

    setText('title', f('headline') || cfg.defaultTitle || '');

    guest = String(guest || '').trim().slice(0, 30);
    setText('dear', String(cfg.dear || ':name').replace(':name', guest));
    showLine('dear', guest !== '');

    setText('host', f('sender_name'));
    showLine('host', f('sender_name') !== '');

    var start = splitStart(fields.event_date);
    var end = validTime(f('end_time')) ? f('end_time') : null;
    var dateText = start[0] ? formatDate(start[0]) : '';
    var timeText = start[1] ? formatTime(start[1]) + (end ? ' – ' + formatTime(end) : '') : '';
    setText('date', dateText); showLine('date', dateText !== '');
    setText('time', timeText); showLine('time', timeText !== '');

    var address = locked('venue_address') ? '' : f('venue_address');
    var mapLink = !locked('map_url') && /^https?:\/\//i.test(f('map_url')) ? f('map_url') : '';
    if (address && /^https?:\/\/\S+$/i.test(address)) { mapLink = mapLink || address; address = ''; }
    var mapUrl = mapLink || (address ? 'https://maps.google.com/?q=' + encodeURIComponent(address) : '');
    setText('venue', f('venue_name')); showLine('venue', f('venue_name') !== '');
    setText('address', address); showLine('address', address !== '');
    all('[data-xm="map"]').forEach(function (a) { a.href = mapUrl || '#'; });
    showLine('map', mapUrl !== '');

    setText('message', f('message')); showLine('message', f('message') !== '');
    setText('dress', f('dress_code')); showLine('dress', f('dress_code') !== '');
    setText('bring', f('what_to_bring')); showLine('bring', f('what_to_bring') !== '');

    var rsvpBy = formatDate(f('rsvp_deadline'));
    setText('rsvpby', rsvpBy); showLine('rsvpby', rsvpBy !== '');

    var contact = f('contact_phone');
    setText('contact', contact); showLine('contact', contact !== '');
    all('[data-xm="contact"]').forEach(function (a) {
      if (/^\+?[\d\s().-]{6,}$/.test(contact)) a.href = 'tel:' + contact.replace(/[^\d+]/g, '');
      else a.removeAttribute('href');
    });

    var rsvpOn = [true, 1, '1', 'true'].indexOf(fields.rsvp_enabled) !== -1 && !locked('rsvp_enabled');
    setText('rsvpq', f('rsvp_message') || cfg.rsvpQuestion || '');
    showLine('rsvp', rsvpOn);

    // a heading whose lines are all empty hides with them
    all('[data-xm-group]').forEach(function (g) { g.hidden = !g.querySelector('[data-xm-line]:not([hidden])'); });
  };

  /* --- PNG download (dashboard editor) -----------------------------------------------------------
     Each section is drawn on its own canvas from what is on screen right now, bottom to top like
     the page: background video frame (cover-fit) → cover PNG (contain) → snow → panels/buttons →
     text, word by word at its laid-out position in the page's own fonts. */
  var visible = function (el) {
    return el.getClientRects().length > 0 && !el.closest('[data-capture-skip]') && getComputedStyle(el).visibility !== 'hidden';
  };

  var waitForVideo = function (v) {
    return new Promise(function (resolve) {
      if (!v || v.readyState >= 2) return resolve();
      var t = setTimeout(resolve, 15000); // then draw without it (plain cream)
      v.addEventListener('loadeddata', function () { clearTimeout(t); resolve(); }, { once: true });
    });
  };

  var tintedFlake = function () {
    return new Promise(function (resolve) {
      var img = new Image();
      img.onload = function () {
        var c = document.createElement('canvas');
        c.width = c.height = 96;
        var x = c.getContext('2d');
        x.drawImage(img, 0, 0, 96, 96);
        x.globalCompositeOperation = 'source-in';
        x.fillStyle = getComputedStyle(root).getPropertyValue('--xm-flake').trim() || '#9fb5c6';
        x.fillRect(0, 0, 96, 96);
        resolve(c);
      };
      img.onerror = function () { resolve(null); };
      img.src = snow ? snow.getAttribute('data-src') : '';
    });
  };

  var roundRect = function (ctx, x, y, w, h, r) {
    r = Math.max(0, Math.min(r, w / 2, h / 2));
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  };

  var drawFit = function (ctx, src, sw, sh, box, mode) {
    if (!sw || !sh) return;
    var s = mode === 'cover' ? Math.max(box.width / sw, box.height / sh) : Math.min(box.width / sw, box.height / sh);
    var dw = sw * s, dh = sh * s;
    ctx.save();
    ctx.beginPath();
    ctx.rect(box.left, box.top, box.width, box.height);
    ctx.clip();
    ctx.drawImage(src, box.left + (box.width - dw) / 2, box.top + (box.height - dh) / 2, dw, dh);
    ctx.restore();
  };

  var transparent = function (c) { return !c || c === 'transparent' || /rgba\([^)]*,\s*0\)$/.test(c); };

  var drawBox = function (ctx, el) {
    var cs = getComputedStyle(el), b = el.getBoundingClientRect();
    var radius = parseFloat(cs.borderTopLeftRadius) || 0;
    roundRect(ctx, b.left, b.top, b.width, b.height, radius);
    if (!transparent(cs.backgroundColor)) { ctx.fillStyle = cs.backgroundColor; ctx.fill(); }
    var bw = parseFloat(cs.borderTopWidth) || 0;
    if (bw > 0 && cs.borderTopStyle !== 'none' && !transparent(cs.borderTopColor)) {
      roundRect(ctx, b.left + bw / 2, b.top + bw / 2, b.width - bw, b.height - bw, radius - bw / 2);
      ctx.lineWidth = bw;
      ctx.strokeStyle = cs.borderTopColor;
      ctx.stroke();
    }
  };

  var drawText = function (ctx, section) {
    var walker = document.createTreeWalker(section, NodeFilter.SHOW_TEXT);
    var range = document.createRange();
    var node;
    while ((node = walker.nextNode())) {
      var el = node.parentElement;
      if (!el || !node.nodeValue.trim() || el.closest('video, script, style, form') || !visible(el)) continue;
      var cs = getComputedStyle(el);
      ctx.font = [cs.fontStyle, cs.fontWeight, cs.fontSize, cs.fontFamily].join(' ');
      ctx.fillStyle = cs.color;
      ctx.textAlign = 'left';
      ctx.textBaseline = 'middle';
      if ('letterSpacing' in ctx) ctx.letterSpacing = cs.letterSpacing === 'normal' ? '0px' : cs.letterSpacing;
      var shadow = /(rgba?\([^)]*\)|#[0-9a-f]+)\s+(-?[\d.]+)px\s+(-?[\d.]+)px\s+([\d.]+)px/i.exec(cs.textShadow || '');
      ctx.shadowColor = shadow ? shadow[1] : 'transparent';
      ctx.shadowOffsetX = shadow ? +shadow[2] : 0;
      ctx.shadowOffsetY = shadow ? +shadow[3] : 0;
      ctx.shadowBlur = shadow ? +shadow[4] : 0;
      var upper = cs.textTransform === 'uppercase';

      var re = /\S+/g, m;
      while ((m = re.exec(node.nodeValue))) {
        range.setStart(node, m.index);
        range.setEnd(node, m.index + m[0].length);
        var rects = range.getClientRects();
        if (rects.length === 1) {
          ctx.fillText(upper ? m[0].toUpperCase() : m[0], rects[0].left, rects[0].top + rects[0].height / 2);
          continue;
        }
        // a long word wrapped across lines: place it character by character
        for (var i = 0; i < m[0].length; i++) {
          range.setStart(node, m.index + i);
          range.setEnd(node, m.index + i + 1);
          var r = range.getBoundingClientRect();
          if (r.width) ctx.fillText(upper ? m[0][i].toUpperCase() : m[0][i], r.left, r.top + r.height / 2);
        }
      }
    }
    ctx.shadowColor = 'transparent';
  };

  var renderSection = function (section, flake) {
    var r = section.getBoundingClientRect();
    var scale = Math.min(3, Math.max(2, window.devicePixelRatio || 1));
    var canvas = document.createElement('canvas');
    canvas.width = Math.round(r.width * scale);
    canvas.height = Math.round(r.height * scale);
    var ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);
    ctx.translate(-r.left, -r.top);
    ctx.beginPath();
    ctx.rect(r.left, r.top, r.width, r.height);
    ctx.clip();

    ctx.fillStyle = getComputedStyle(root).backgroundColor;
    ctx.fillRect(r.left, r.top, r.width, r.height);

    var video = section.querySelector('video[data-bg]');
    if (video && video.readyState >= 2) drawFit(ctx, video, video.videoWidth, video.videoHeight, video.getBoundingClientRect(), 'cover');

    section.querySelectorAll('.xm-cover-art').forEach(function (img) {
      if (img.complete && img.naturalWidth) drawFit(ctx, img, img.naturalWidth, img.naturalHeight, img.getBoundingClientRect(), 'contain');
    });

    if (flake && snow) {
      snow.querySelectorAll('.xm-flake-shape').forEach(function (el) {
        var b = el.getBoundingClientRect();
        if (b.bottom < r.top || b.top > r.bottom || b.right < r.left || b.left > r.right) return;
        var size = el.offsetWidth;
        ctx.globalAlpha = parseFloat(el.style.opacity) || 1;
        ctx.drawImage(flake, b.left + b.width / 2 - size / 2, b.top + b.height / 2 - size / 2, size, size);
      });
      ctx.globalAlpha = 1;
    }

    section.querySelectorAll('[data-capture-box]').forEach(function (el) { if (visible(el)) drawBox(ctx, el); });
    drawText(ctx, section);

    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (blob) { if (blob) resolve(blob); else reject(new Error('empty canvas')); }, 'image/png');
    });
  };

  var capture = function () {
    var wasHidden = details.hidden, y = window.scrollY;
    reveal(false);
    var settle = new Promise(function (r) { setTimeout(r, 400); }); // snow re-spreads over section 2
    return Promise.all([waitForVideo(coverVideo), waitForVideo(detailsVideo), document.fonts ? document.fonts.ready : null, tintedFlake(), settle])
      .then(function (res) {
        return Promise.all([renderSection(cover, res[3]), renderSection(details, res[3])]);
      })
      .then(function (blobs) {
        return [{ name: 'cover.png', blob: blobs[0] }, { name: 'details.png', blob: blobs[1] }];
      })
      .finally(function () {
        if (wasHidden) collapse();
        window.scrollTo(0, y);
      });
  };

  if (cfg.editor && window.parent !== window) {
    var origin = window.location.origin;
    window.addEventListener('message', function (e) {
      if (e.origin !== origin || e.source !== window.parent || !e.data) return;
      if (e.data.type === 'xm:update') apply(e.data.fields, e.data.guest);
      if (e.data.type === 'xm:capture') {
        capture()
          .then(function (files) { window.parent.postMessage({ type: 'xm:captured', files: files }, origin); })
          .catch(function () { window.parent.postMessage({ type: 'xm:capture-failed' }, origin); });
      }
    });
    window.parent.postMessage({ type: 'xm:ready' }, origin);
  }

  /* --- music: an uploaded song (<audio preload="none">, nothing downloads until it plays) or a
     YouTube iframe primed muted; the guest's first tap anywhere is the gesture that lets it play.
     Not in the editor preview — the dashboard shouldn't start singing while you type. --- */
  var yt = document.getElementById('xm-yt'), audio = document.getElementById('xm-audio'), musicBtn = document.getElementById('xm-music'), playing = false;
  var ytCmd = function (fn) { if (yt && yt.contentWindow) yt.contentWindow.postMessage(JSON.stringify({ event: 'command', func: fn, args: [] }), '*'); };
  var setPlaying = function (on) {
    playing = on;
    musicBtn.classList.toggle('playing', on);
    musicBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
  };
  var play = function () {
    if (audio) { var p = audio.play(); if (p) p.catch(function () { setPlaying(false); }); } else { ytCmd('unMute'); ytCmd('playVideo'); }
    setPlaying(true);
  };
  var pause = function () {
    if (audio) audio.pause(); else ytCmd('pauseVideo');
    setPlaying(false);
  };
  if ((yt || audio) && musicBtn) {
    musicBtn.addEventListener('click', function () { if (playing) pause(); else play(); });
    if (!cfg.editor) {
      var firstTap = function (e) {
        document.removeEventListener('pointerdown', firstTap, true);
        if (musicBtn.contains(e.target)) return; // the button handles its own tap
        play();
      };
      document.addEventListener('pointerdown', firstTap, true);
    }
  }

  /* --- RSVP: posts status yes|no + an optional note to the invitation's RSVP endpoint --- */
  var rsvp = document.getElementById('xm-rsvp');
  if (rsvp) {
    var rsvpUrl = rsvp.getAttribute('data-url') || null; // empty on demo/preview pages: nothing to save
    var form = rsvp.querySelector('.xm-rsvp-form'), note = form.querySelector('input');
    var thanks = rsvp.querySelector('.xm-rsvp-thanks'), error = rsvp.querySelector('.xm-rsvp-error');
    var buttons = rsvp.querySelectorAll('[data-status]');
    var chosen = null;

    var send = function () {
      error.hidden = true;
      var done = function (ok) {
        if (ok) { form.hidden = true; thanks.hidden = false; } else { error.hidden = false; }
      };
      if (!rsvpUrl) { done(true); return; }
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    };

    rsvp.addEventListener('click', function (e) {
      var b = e.target.closest('[data-status]');
      if (!b) return;
      chosen = b.getAttribute('data-status');
      buttons.forEach(function (o) { o.setAttribute('aria-pressed', o === b ? 'true' : 'false'); });
      thanks.hidden = true;
      form.hidden = false;
      note.focus({ preventScroll: true });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (chosen) send();
    });
  }
})();

/* Kathina ceremony template (resources/views/invitations/templates/kak-thern.blade.php) */
(function () {
  var root = document.documentElement, body = document.body;
  root.classList.add('kt-js');
  body.classList.add('kt-doc');
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* --- butterflies: the clip is packed side by side, colour on the left half and its alpha mask on the right
         half, joined back into a transparent canvas in WebGL (as in the wedding template). The canvas fills the
         column and the clip is tiled across it at TILE x the column width, so the butterflies stay small; each
         row of tiles is shifted sideways so the repeat doesn't line up, and only a random DENSITY share of the
         tiles is drawn, so there are fewer of them. Without WebGL the layer is left out. --- */
  var TILE = 0.6, DENSITY = 0.4;
  var VERT = 'attribute vec2 p;varying vec2 uv;void main(){uv=vec2(p.x*.5+.5,.5-p.y*.5);gl_Position=vec4(p,0.,1.);}';
  var FRAG = 'precision mediump float;varying vec2 uv;uniform sampler2D t;uniform vec2 rep;void main(){'
    + 'vec2 q=uv*rep;q.x+=floor(q.y)*.37;vec2 cell=floor(q);q=fract(q);'
    + 'if(fract(sin(dot(cell,vec2(12.9898,78.233)))*43758.5453)>' + DENSITY.toFixed(2) + '){gl_FragColor=vec4(0.);return;}'
    + 'vec3 c=texture2D(t,vec2(q.x*.5,q.y)).rgb;float a=texture2D(t,vec2(.5+q.x*.5,q.y)).r;gl_FragColor=vec4(c*a,a);}';

  function keyVideo(video) {
    var canvas = document.createElement('canvas');
    var gl = canvas.getContext('webgl', { premultipliedAlpha: true, alpha: true, antialias: false, depth: false, stencil: false });
    if (!gl) return;
    var sh = function (type, src) { var s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    var prog = gl.createProgram();
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, VERT));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, FRAG));
    gl.linkProgram(prog);
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return;
    gl.useProgram(prog);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    gl.bindTexture(gl.TEXTURE_2D, gl.createTexture());
    [gl.TEXTURE_WRAP_S, gl.TEXTURE_WRAP_T].forEach(function (k) { gl.texParameteri(gl.TEXTURE_2D, k, gl.CLAMP_TO_EDGE); });
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.clearColor(0, 0, 0, 0);
    var repLoc = gl.getUniformLocation(prog, 'rep');

    video.parentNode.insertBefore(canvas, video);
    video.parentNode.classList.add('keyed');   // CSS shows the layer and parks the source video as a 2px "on screen" dot

    // match the canvas to its box (capped at 1.5x density) and work out how many tiles fit across and down
    var sized = false;
    var resize = function () {
      if (!video.videoWidth) return;
      var w = canvas.clientWidth, h = canvas.clientHeight, dpr = Math.min(window.devicePixelRatio || 1, 1.5);
      canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
      gl.viewport(0, 0, canvas.width, canvas.height);
      var tileW = w * TILE, tileH = tileW * video.videoHeight / (video.videoWidth / 2);
      gl.uniform2f(repLoc, w / tileW, h / tileH);
      sized = true;
    };
    addEventListener('resize', resize);

    var hasVfc = 'requestVideoFrameCallback' in HTMLVideoElement.prototype, gen = 0;
    var draw = function (g) {
      if (g !== gen) return;   // a newer chain took over (tab hidden and shown again)
      if (video.readyState >= 2 && video.videoWidth) {
        if (!sized) resize();
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, video);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
      }
      next(g);
    };
    // redraw only when the video has a new frame, not on every screen refresh
    var next = function (g) {
      if (document.hidden) return;
      var f = function () { draw(g); };
      if (hasVfc) video.requestVideoFrameCallback(f); else requestAnimationFrame(f);
    };
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) video.pause();
      else { video.play().catch(function () {}); next(++gen); }
    });
    next(gen);
  }

  var rand = function (a, b) { return a + Math.random() * (b - a); };

  if (!reduced) {
    document.querySelectorAll('.kt-fx video[data-key]').forEach(function (v) {
      keyVideo(v);
      var p = v.play(); if (p) p.catch(function () {});
    });

    /* --- yellow flowers drifting down over every section; the first few start mid-fall so the screen isn't empty --- */
    var layer = document.createElement('div');
    layer.className = 'kt-petals';
    layer.setAttribute('aria-hidden', 'true');
    var n = window.innerWidth < 480 ? 12 : 16;
    for (var i = 0; i < n; i++) {
      var petal = document.createElement('i'), t = rand(11, 19);
      petal.className = 'kt-petal';
      petal.style.cssText = '--x:' + rand(0, 94).toFixed(1) + '%;--t:' + t.toFixed(1) + 's;--d:' + (i < n / 2 ? -rand(0, t) : rand(0, 10)).toFixed(1)
        + 's;--dx:' + rand(14, 46).toFixed(0) + 'px;--s:' + rand(13, 22).toFixed(1) + 'px;--f:' + rand(2.6, 4.8).toFixed(1) + 's';
      petal.appendChild(document.createElement('b'));
      layer.appendChild(petal);
    }
    document.querySelector('.kt').appendChild(layer);
  }

  /* --- the title headline (often one unspaced Khmer word) and the one-line hero date can't wrap:
         shrink each until it fits the column --- */
  document.querySelectorAll('.kt-fit').forEach(function (el) {
    // measure the widest rendered line: centred text spills out on both sides, which scrollWidth doesn't report.
    // A line can be several rects (e.g. the date's coloured weekday span), so take each line's full left-to-right span.
    var range = document.createRange();
    range.selectNodeContents(el);
    var fit = function () {
      el.style.fontSize = '';
      var w = el.clientWidth, size = parseFloat(getComputedStyle(el).fontSize), widest = 0, lines = {};
      [].forEach.call(range.getClientRects(), function (r) {
        var k = Math.round(r.top + r.height / 2), l = lines[k] || (lines[k] = { a: r.left, b: r.right });
        l.a = Math.min(l.a, r.left); l.b = Math.max(l.b, r.right);
      });
      Object.keys(lines).forEach(function (k) { widest = Math.max(widest, lines[k].b - lines[k].a); });
      if (widest > w) el.style.fontSize = Math.max(size * w / widest - 0.5, 14) + 'px';
    };
    fit();
    addEventListener('resize', fit);
    // refit once Moul arrives — fonts.ready can settle before a lazily loaded webfont starts downloading
    if (document.fonts) {
      if (document.fonts.ready) document.fonts.ready.then(fit);
      if (document.fonts.load) document.fonts.load('1em Moul', el.textContent).then(fit, function () {});
      if (document.fonts.addEventListener) document.fonts.addEventListener('loadingdone', fit);
    }
  });

  /* --- scroll reveal (held back until the intro has gone, so the hero animates in view) --- */
  var startReveal = function () {
    var revs = document.querySelectorAll('.kt-rev');
    if ('IntersectionObserver' in window && !reduced) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
      }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
      revs.forEach(function (el) { io.observe(el); });
    } else {
      revs.forEach(function (el) { el.classList.add('in'); });
    }
  };

  /* --- intro: plays once over the page, then fades out. Skipped on tap, on reduced motion, if autoplay is
         refused (e.g. iOS low-power mode), or if it hasn't started within a few seconds. --- */
  var intro = document.getElementById('kt-intro');
  if (!intro || reduced) {
    if (intro) intro.remove();
    startReveal();
  } else {
    var iv = intro.querySelector('video'), gone = false, stall;
    var finish = function () {
      if (gone) return;
      gone = true;
      clearTimeout(stall);
      root.classList.remove('kt-intro-on');
      intro.classList.add('out');
      startReveal();
      setTimeout(function () { iv.pause(); intro.remove(); }, 1000);
    };
    root.classList.add('kt-intro-on');
    iv.src = iv.getAttribute(matchMedia('(min-width:900px) and (min-aspect-ratio:5/4)').matches ? 'data-land' : 'data-portrait');
    iv.addEventListener('ended', finish);
    iv.addEventListener('error', finish);
    iv.addEventListener('playing', function () { clearTimeout(stall); stall = setTimeout(finish, (iv.duration || 9) * 1000 + 2000); });
    intro.addEventListener('click', finish);
    stall = setTimeout(finish, 5000);
    var ip = iv.play(); if (ip) ip.catch(finish);
  }

  /* --- countdown (uses the invitation's real event date), in Khmer digits --- */
  var cd = document.querySelector('.kt-count');
  if (cd) {
    var target = new Date(cd.getAttribute('data-target')).getTime(), els = {};
    cd.querySelectorAll('[data-u]').forEach(function (b) { els[b.getAttribute('data-u')] = b; });
    var kh = function (n) { return String(n).padStart(2, '0').replace(/\d/g, function (d) { return '០១២៣៤៥៦៧៨៩'[d]; }); };
    var tick = function () {
      var diff = Math.max(0, target - Date.now());
      els.d.textContent = kh(Math.floor(diff / 86400000));
      els.h.textContent = kh(Math.floor(diff / 3600000) % 24);
      els.m.textContent = kh(Math.floor(diff / 60000) % 60);
      els.s.textContent = kh(Math.floor(diff / 1000) % 60);
    };
    tick(); setInterval(tick, 1000);
  }

  /* --- music: an uploaded song (<audio preload="none">, so nothing downloads until it plays) or a YouTube
         iframe primed muted; with no open cover, the guest's first tap anywhere is the user gesture that
         lets it play --- */
  var yt = document.getElementById('kt-yt'), audio = document.getElementById('kt-audio'), musicBtn = document.getElementById('kt-music'), playing = false;
  var ytCmd = function (fn) { if (yt && yt.contentWindow) yt.contentWindow.postMessage(JSON.stringify({ event: 'command', func: fn, args: [] }), '*'); };
  var setPlaying = function (on) {
    playing = on;
    musicBtn.classList.toggle('playing', on);
    musicBtn.classList.toggle('paused', !on);
  };
  var play = function () {
    if (audio) { var p = audio.play(); if (p) p.catch(function () { setPlaying(false); }); }
    else { ytCmd('unMute'); ytCmd('playVideo'); }
    setPlaying(true);
  };
  var pause = function () {
    if (audio) audio.pause(); else ytCmd('pauseVideo');
    setPlaying(false);
  };
  if ((yt || audio) && musicBtn) {
    musicBtn.addEventListener('click', function () { if (playing) pause(); else play(); });
    var firstTap = function (e) {
      document.removeEventListener('pointerdown', firstTap, true);
      if (musicBtn.contains(e.target)) return;   // the button handles its own tap
      play();
    };
    document.addEventListener('pointerdown', firstTap, true);
  }

  /* --- RSVP: posts to the invitation RSVP endpoint (status yes|no + optional note) --- */
  var opts = document.getElementById('kt-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var form = document.getElementById('kt-rsvp-form'), note = document.getElementById('kt-rsvp-note'), thanks = document.getElementById('kt-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.kt-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.kt-opt').forEach(function (o) { o.classList.toggle('chosen', o === b); });
      form.classList.add('show'); thanks.classList.remove('show');
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var done = function (ok) {
        thanks.textContent = !ok ? 'សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សាធុ! យើងខ្ញុំរង់ចាំទទួលស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ។' : 'សូមអរគុណដែលបានជូនដំណឹង ។ សូមអនុមោទនាផលបុណ្យជាមួយយើងខ្ញុំ ។');
        thanks.classList.add('show'); if (ok) form.classList.remove('show');
      };
      if (!rsvpUrl) { done(true); return; }   // demo preview: no recipient, nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }

  /* --- gallery viewer: a tap opens the photo full screen (the link still opens it in a tab without JS);
         swipe / arrow keys / buttons move between photos, the backdrop, x or Esc closes --- */
  var photos = [].slice.call(document.querySelectorAll('.kt-gal a.kt-ph'));
  if (photos.length) {
    var lb, lbImg, lbCount, at = 0, lastFocus;
    var build = function () {
      lb = document.createElement('div');
      lb.className = 'kt-lb';
      lb.setAttribute('role', 'dialog');
      lb.setAttribute('aria-modal', 'true');
      lb.innerHTML = '<img alt=""><button type="button" class="kt-lb-x" aria-label="Close">&times;</button>'
        + (photos.length > 1 ? '<button type="button" class="kt-lb-prev" aria-label="Previous">&#8249;</button><button type="button" class="kt-lb-next" aria-label="Next">&#8250;</button>' : '')
        + '<p class="kt-lb-count"></p>';
      lbImg = lb.querySelector('img');
      lbCount = lb.querySelector('.kt-lb-count');
      lb.addEventListener('click', function (e) {
        if (e.target.closest('.kt-lb-prev')) go(-1);
        else if (e.target.closest('.kt-lb-next')) go(1);
        else if (e.target !== lbImg) close();
      });
      var x0 = null;
      lb.addEventListener('pointerdown', function (e) { x0 = e.clientX; });
      lb.addEventListener('pointerup', function (e) {
        if (x0 !== null && Math.abs(e.clientX - x0) > 50) go(e.clientX < x0 ? 1 : -1);
        x0 = null;
      });
      document.querySelector('.kt').appendChild(lb);
    };
    var show = function (i, from) {
      at = (i + photos.length) % photos.length;
      var src = photos[at].getAttribute('href');
      lbCount.textContent = photos.length > 1 ? (at + 1) + ' / ' + photos.length : '';
      if (!from) { lbImg.src = src; lbImg.alt = photos[at].querySelector('img').alt; requestAnimationFrame(function () { lbImg.classList.add('show'); }); return; }
      // slide the old photo out, then the new one in from the other side
      lbImg.classList.remove('show');
      lbImg.classList.add(from > 0 ? 'go-left' : 'go-right');
      setTimeout(function () {
        lbImg.classList.remove('go-left', 'go-right');
        lbImg.style.transition = 'none';
        lbImg.classList.add(from > 0 ? 'go-right' : 'go-left');
        lbImg.src = src; lbImg.alt = photos[at].querySelector('img').alt;
        void lbImg.offsetWidth;
        lbImg.style.transition = '';
        lbImg.classList.remove('go-left', 'go-right');
        lbImg.classList.add('show');
      }, reduced ? 0 : 220);
    };
    var go = function (step) { if (photos.length > 1) show(at + step, step); };
    var onKey = function (e) {
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') go(-1);
      else if (e.key === 'ArrowRight') go(1);
    };
    var open = function (i) {
      if (!lb) build();
      lastFocus = document.activeElement;
      root.classList.add('kt-lb-on');
      lb.style.display = '';
      show(i);
      requestAnimationFrame(function () { lb.classList.add('open'); });
      document.addEventListener('keydown', onKey);
      lb.querySelector('.kt-lb-x').focus({ preventScroll: true });
    };
    var close = function () {
      lb.classList.remove('open');
      lbImg.classList.remove('show');
      root.classList.remove('kt-lb-on');
      document.removeEventListener('keydown', onKey);
      setTimeout(function () { if (!lb.classList.contains('open')) lb.style.display = 'none'; }, reduced ? 0 : 350);
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    photos.forEach(function (a, i) {
      a.addEventListener('click', function (e) { e.preventDefault(); open(i); });
    });
  }

  /* --- donation card: copy the account number --- */
  document.querySelectorAll('.kt-qr-copy').forEach(function (b) {
    var label = b.textContent;
    b.addEventListener('click', function () {
      var text = b.getAttribute('data-copy');
      var ok = function () {
        b.textContent = b.getAttribute('data-done'); b.classList.add('done');
        setTimeout(function () { b.textContent = label; b.classList.remove('done'); }, 2200);
      };
      if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(ok, function () {}); return; }
      var t = document.createElement('textarea'); t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
      document.body.appendChild(t); t.select();
      try { if (document.execCommand('copy')) ok(); } catch (e) {}
      t.remove();
    });
  });
})();

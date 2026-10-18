/* Khmer Traditional Wedding template (resources/views/invitations/templates/khmer-traditional-wedding.blade.php) */
/* --- layout picker: desktop (wide, landscape screens) gets the 16:9 renders, phones the portrait ones.
       Runs as soon as the file loads (the videos are already in the page) so the right files start downloading
       before GSAP arrives; swaps them again when the screen turns. Keep the query in step with the CSS. --- */
(function () {
  document.documentElement.classList.add('kw-js');
  document.body.classList.add('kw-body', 'kw-locked');
  var mq = window.matchMedia('(min-width: 900px) and (min-aspect-ratio: 5/4)'), vids = document.querySelectorAll('video[data-land]');
  function pick() {
    var land = mq.matches;
    document.documentElement.classList.toggle('kw-land', land);
    vids.forEach(function (v) {
      if (!v.isConnected || v.hasAttribute('data-lock')) return;
      var src = land ? v.dataset.land : v.dataset.src, poster = land ? v.dataset.landPoster : v.dataset.poster;
      if (poster) v.poster = poster;
      if (v.getAttribute('src') === src) return;
      var wasPlaying = !!v.getAttribute('src') && !v.paused;
      v.src = src;
      if (wasPlaying) v.play().catch(function () {});
    });
    document.dispatchEvent(new Event('kw-layout'));
  }
  pick();
  if (mq.addEventListener) mq.addEventListener('change', pick); else mq.addListener(pick);
})();

/* --- template (waits for GSAP, which loads right after this file) --- */
function kwInit() {
  var root = document.documentElement, body = document.body;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var rand = function (a, b) { return a + Math.random() * (b - a); };

  /* --- gold dust: generated so each field gets its own random drift --- */
  if (!reduced) {
    document.querySelectorAll('.kw-dustfield').forEach(function (field) {
      var n = +field.getAttribute('data-dust') || 12, rise = field.getAttribute('data-rise') || '-260px';
      for (var i = 0; i < n; i++) {
        var d = document.createElement('i'), t = rand(8, 16);
        d.className = 'kw-dust';
        d.style.cssText = 'left:' + rand(2, 98) + '%;bottom:' + rand(-5, 45) + '%;--s:' + rand(2, 5).toFixed(1) + 'px;--t:' + t.toFixed(1) + 's;--d:-' + rand(0, t).toFixed(1)
          + 's;--dx:' + rand(-40, 40).toFixed(0) + 'px;--o:' + rand(.45, .95).toFixed(2) + ';--rise:' + rise;
        field.appendChild(d);
      }
    });
  }

  /* --- falling roumdul petals over the whole page, once the invitation is open --- */
  function spawnPetals() {
    if (reduced) return;
    var layer = document.createElement('div');
    layer.className = 'kw-petals';
    layer.setAttribute('aria-hidden', 'true');
    var n = window.innerWidth < 480 ? 9 : 13;
    for (var i = 0; i < n; i++) {
      var p = document.createElement('i'), t = rand(14, 24);
      p.className = 'kw-petal';
      p.style.cssText = '--x:' + rand(0, 96) + '%;--t:' + t.toFixed(1) + 's;--d:' + (i < 3 ? -rand(2, 8) : rand(0, 14)).toFixed(1)
        + 's;--dx:' + rand(-60, 60).toFixed(0) + 'px;--s:' + rand(7, 12).toFixed(1) + 'px;--f:' + rand(4, 7.5).toFixed(1) + 's';
      p.appendChild(document.createElement('b'));
      layer.appendChild(p);
    }
    document.querySelector('.kw').appendChild(layer);
  }

  /* --- pause ambient loops in sections that are off screen --- */
  if ('IntersectionObserver' in window) {
    var idleIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { e.target.classList.toggle('kw-idle', !e.isIntersecting); });
    }, { rootMargin: '120px 0px' });
    document.querySelectorAll('.kw-live').forEach(function (el) { idleIo.observe(el); });
  }

  /* --- section reveal on scroll (started after the opening, so the hero plays in view) --- */
  function startReveal() {
    var revs = document.querySelectorAll('.kw-rev');
    if ('IntersectionObserver' in window && !reduced) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
      }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
      revs.forEach(function (el) { io.observe(el); });
    } else {
      revs.forEach(function (el) { el.classList.add('in'); });
    }
  }

  /* --- music (YouTube iframe is primed muted; the open tap is the user gesture that lets it play) --- */
  var yt = document.getElementById('kw-yt'), musicBtn = document.getElementById('kw-music'), playing = false;
  function ytCmd(fn) {
    if (yt && yt.contentWindow) yt.contentWindow.postMessage(JSON.stringify({ event: 'command', func: fn, args: [] }), '*');
  }
  function setPlaying(on) {
    playing = on;
    if (!musicBtn) return;
    musicBtn.classList.toggle('playing', on);
    musicBtn.classList.toggle('paused', !on);
  }
  if (musicBtn) {
    musicBtn.classList.add('show');
    musicBtn.addEventListener('click', function () {
      if (playing) { ytCmd('pauseVideo'); setPlaying(false); }
      else { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    });
  }

  /* --- rendered background + butterfly videos (started by the open tap, which also satisfies autoplay rules) --- */
  var bgVideo = document.getElementById('kw-bg-video'), flyVideo = document.getElementById('kw-fly-video'), flyCanvas = document.getElementById('kw-fly-canvas');
  var swingVideo = document.getElementById('kw-swing-video');
  var stageOn = false, flyOn = false, scheduleFly = function () {};

  // performance test switches, e.g. ?kwtest=nofx,nofly  (nofx: freeze the template's CSS/SVG animations, nofly: no butterflies)
  var kwTest = (new URLSearchParams(location.search).get('kwtest') || '').split(',');
  if (kwTest.indexOf('nofx') > -1) body.classList.add('kw-nofx');

  /* --- GSAP: a handful of moments, each plays once (transforms + opacity only, so it stays light
         next to the videos). Without GSAP or with reduced motion everything is simply shown. --- */
  var G = window.gsap, ST = window.ScrollTrigger;
  var useGsap = !!(G && ST) && !reduced && kwTest.indexOf('nofx') < 0;
  if (useGsap) { G.registerPlugin(ST); root.classList.add('kw-gsap'); }

  function startMoments() {
    if (!useGsap) return;
    var once = function (el, start) { return { trigger: el, start: start || 'top 80%', once: true }; };
    var q = function (sel, ctx) { return (ctx || document).querySelector(sel); };
    var qa = function (sel, ctx) { return G.utils.toArray((ctx || document).querySelectorAll(sel)); };

    // hero: the name kbach fades up, then each name is written in under its label
    var names = q('.kw-hero-names');
    if (names) {
      var hero = G.timeline({ scrollTrigger: once(names, 'top 85%') })
        .fromTo(names, { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 1, ease: 'power3.out' });
      qa('.kw-names', names).forEach(function (ln, i) {
        hero.fromTo(ln, { clipPath: 'inset(-20% 100% -20% -10%)' },
          { clipPath: 'inset(-20% -10% -20% -10%)', duration: 1.3, ease: 'power2.inOut', clearProps: 'clipPath' }, i ? '-=.6' : '-=.3');
      });
    }

    // love verse: the heartbeat line draws itself into a heart, a roumdul opens inside it
    var verse = q('#kw-verse');
    if (verse) {
      var strokes = qa('.kw-heartline path', verse), bl = q('.kw-heartline .bl', verse), len = strokes[0].getTotalLength();
      G.set(strokes, { strokeDasharray: len, strokeDashoffset: len });
      G.set(bl, { scale: 0, transformOrigin: '50% 50%' });
      G.timeline({ scrollTrigger: once(verse, 'top 72%') })
        .to(strokes, { strokeDashoffset: 0, duration: 2.6, ease: 'power1.inOut' })
        .to(bl, { scale: 1, rotation: 120, duration: 1, ease: 'back.out(2)' }, '-=1.1')
        .fromTo(qa('.kw-gs', verse), { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 1.1, stagger: .32, ease: 'power2.out' }, '-=1.9');
    }

    // couple: the photo opens like a curtain lifting, settles, then their names rise in
    // with the roumdul blooming between them
    var duo = q('#kw-couple .kw-duo');
    if (duo) {
      var people = qa('.kw-duo-person', duo), heart = q('.kw-duo-heart', duo);
      G.set(duo, { opacity: 1 });
      var lift = G.timeline({ scrollTrigger: once(duo, 'top 82%') })
        .fromTo(duo, { clipPath: 'inset(50% 0% 50% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.4, ease: 'power3.inOut', clearProps: 'clipPath' })
        .fromTo(q('.kw-duo-img', duo), { scale: 1.25 }, { scale: 1, duration: 2.4, ease: 'power2.out' }, 0)
        .fromTo(people, { opacity: 0, y: 22 }, { opacity: 1, y: 0, duration: 1, stagger: .25, ease: 'power3.out' }, '-=1.3');
      if (heart) lift.fromTo(heart, { opacity: 0, scale: 0, rotation: -140 }, { opacity: 1, scale: 1, rotation: 0, duration: 1, ease: 'back.out(1.8)' }, '-=.9');
    }

    // story: a red thread follows the reader down the timeline, lighting each chapter
    var tl = q('.kw-tl');
    if (tl) {
      G.fromTo(q('.kw-tl-fill', tl), { scaleY: 0 }, { scaleY: 1, ease: 'none', scrollTrigger: { trigger: tl, start: 'top 65%', end: 'bottom 65%', scrub: .6 } });
      qa('.kw-tl-item', tl).forEach(function (it) {
        ST.create({ trigger: it, start: 'top 65%', onEnter: function () { it.classList.add('lit'); }, onLeaveBack: function () { it.classList.remove('lit'); } });
      });
    }

    // gallery (framed): prints are dealt onto the table, each photo settling inside its frame
    var figs = qa('.kw-gal figure');
    if (figs.length) {
      G.set(figs, { y: 50, rotation: function (i) { return i % 2 ? 8 : -8; } });
      G.set(qa('.kw-gal img'), { scale: 1.3 });
      ST.batch(figs, { start: 'top 90%', once: true, onEnter: function (batch) {
        G.to(batch, { opacity: 1, y: 0, rotation: 0, duration: 1.1, stagger: .14, ease: 'power3.out' });
        G.to(batch.map(function (f) { return f.querySelector('img'); }), { scale: 1, duration: 1.8, stagger: .14, ease: 'power2.out', clearProps: 'transform' });
      } });
    }

    // gallery (feed): each photo unveils from alternating sides as a light sheen crosses it, then
    // drifts in parallax inside its frame and swells slightly as it passes the middle of the screen
    var gal = q('#kw-gallery');
    var feed = gal && qa('.pf-item', gal);
    if (feed && feed.length) {
      gal.classList.add('kw-gfeed');
      qa('.pf-bloom svg', gal).forEach(function (b) {
        G.fromTo(b, { opacity: 0, scale: .3, rotation: -90 }, { opacity: 1, scale: 1, rotation: 0, duration: 1.2, ease: 'back.out(1.6)', scrollTrigger: once(b, 'top 92%') });
      });
      feed.forEach(function (item, i) {
        var ph = q('.pf-ph', item), img = q('img', item);
        var sheen = document.createElement('i'); sheen.className = 'kw-glint'; ph.appendChild(sheen);
        G.set(sheen, { xPercent: -130 });
        var from = i % 2 ? 'inset(0% 0% 0% 100% round 16px)' : 'inset(0% 100% 0% 0% round 16px)';
        G.set(img, { scale: 1.45 });
        G.timeline({ scrollTrigger: once(item, 'top 88%') })
          .fromTo(item, { y: 70, rotation: i % 2 ? 3 : -3 }, { y: 0, rotation: 0, duration: 1.4, ease: 'power3.out' })
          .fromTo(ph, { clipPath: from }, { clipPath: 'inset(0% 0% 0% 0% round 16px)', duration: 1.3, ease: 'power3.inOut', clearProps: 'clipPath' }, 0)
          .to(img, { scale: 1.16, duration: 2, ease: 'power2.out' }, 0)
          .fromTo(sheen, { xPercent: -130 }, { xPercent: 130, duration: 1.3, ease: 'power2.inOut' }, '-=1');
        G.timeline({ scrollTrigger: { trigger: item, start: 'top bottom', end: 'bottom top', scrub: .6 } })
          .fromTo(img, { yPercent: -6 }, { yPercent: 6, duration: 2, ease: 'none' }, 0)
          .fromTo(ph, { scale: .92 }, { scale: 1, duration: 1, ease: 'power1.out' }, 0)
          .to(ph, { scale: .92, duration: 1, ease: 'power1.in' }, 1);
      });
    }

    // closing: the wax seal presses down, a ripple runs out, then the thank-you is written
    var fin = q('#kw-finale');
    if (fin) {
      var seal = q('.kw-seal', fin), ring = q('.kw-seal-ring', fin);
      G.timeline({ scrollTrigger: once(fin, 'top 70%') })
        .fromTo(seal, { opacity: 0, scale: 2.3, rotation: -28 }, { opacity: 1, scale: 1, rotation: 0, duration: .6, ease: 'power4.in' })
        .to(seal, { scaleX: 1.08, scaleY: .92, duration: .09, yoyo: true, repeat: 1, ease: 'power1.out' })
        .fromTo(ring, { opacity: .8, scale: .95 }, { opacity: 0, scale: 1.9, duration: 1.2, ease: 'power2.out' }, '<')
        .fromTo(qa('.kw-gs:not(.kw-seal)', fin), { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 1, stagger: .18, ease: 'power3.out' }, '-=.9');
    }

    // lazy images change the page height; keep the trigger points honest
    var refreshT;
    qa('.kw-page img').forEach(function (img) {
      if (!img.complete) img.addEventListener('load', function () { clearTimeout(refreshT); refreshT = setTimeout(function () { ST.refresh(); }, 200); });
    });
    ST.refresh();
  }

  // a little burst of hearts and roumdul when a guest says yes
  function heartBurst(from) {
    if (!useGsap || !from) return;
    var r = from.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    for (var i = 0; i < 16; i++) {
      var h = document.createElement('span');
      h.className = 'kw-burst';
      h.setAttribute('aria-hidden', 'true');
      h.textContent = i % 3 ? '♥' : '✿';
      h.style.cssText = 'left:' + cx + 'px;top:' + cy + 'px;color:' + (i % 3 ? (i % 2 ? '#b3262a' : '#d9535a') : '#C9A24D');
      document.body.appendChild(h);
      G.fromTo(h, { x: 0, y: 0, scale: .4, opacity: 1 }, {
        x: rand(-150, 150), y: rand(-260, -70), scale: rand(.8, 1.7), rotation: rand(-70, 70), opacity: 0,
        duration: rand(1.4, 2.3), ease: 'power2.out', onComplete: h.remove.bind(h)
      });
    }
  }

  var toTop = document.getElementById('kw-totop');
  if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); });

  var dropFly = function () { flyCanvas.parentNode.remove(); flyVideo.remove(); flyVideo = null; };

  // butterflies: left half of each frame is colour, right half the alpha mask; a WebGL quad joins them
  (function initFly() {
    var gl = !reduced && kwTest.indexOf('nofly') < 0 && flyCanvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false, preserveDrawingBuffer: false });
    if (!gl) { dropFly(); return; }
    var sh = function (type, src) { var s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    var prog = gl.createProgram();
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, 'attribute vec2 p; varying vec2 uv; void main(){ uv = vec2(p.x*.5+.5, .5-p.y*.5); gl_Position = vec4(p,0.,1.); }'));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, 'precision mediump float; varying vec2 uv; uniform sampler2D t;'
      + 'void main(){ vec3 c = texture2D(t, vec2(uv.x*.5, uv.y)).rgb; float a = texture2D(t, vec2(.5+uv.x*.5, uv.y)).r; gl_FragColor = vec4(c*a, a); }'));
    gl.linkProgram(prog); gl.useProgram(prog);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc); gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    gl.bindTexture(gl.TEXTURE_2D, gl.createTexture());
    [gl.TEXTURE_WRAP_S, gl.TEXTURE_WRAP_T].forEach(function (k) { gl.texParameteri(gl.TEXTURE_2D, k, gl.CLAMP_TO_EDGE); });
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.clearColor(0, 0, 0, 0);

    var hasVfc = 'requestVideoFrameCallback' in HTMLVideoElement.prototype, texReady = false, gen = 0;
    var draw = function (g) {
      if (g !== gen || !flyVideo) return;
      if (flyVideo.readyState >= 2) {
        var w = flyVideo.videoWidth / 2, h = flyVideo.videoHeight;
        if (flyCanvas.width !== w || flyCanvas.height !== h) { flyCanvas.width = w; flyCanvas.height = h; gl.viewport(0, 0, w, h); texReady = false; }
        // allocate the texture once, then only upload new pixels
        if (!texReady) { gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, flyVideo); texReady = true; }
        else gl.texSubImage2D(gl.TEXTURE_2D, 0, 0, 0, gl.RGBA, gl.UNSIGNED_BYTE, flyVideo);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
      }
      next(g);
    };
    // redraw only when the video has a new frame (30/s), not on every screen refresh
    var next = function (g) {
      if (!flyOn || document.hidden) return;
      var f = function () { draw(g); };
      if (hasVfc) flyVideo.requestVideoFrameCallback(f); else requestAnimationFrame(f);
    };
    // each call starts a fresh chain and retires the previous one (no doubled or lost chain after a source swap)
    scheduleFly = function () { if (flyVideo) next(++gen); };
  })();

  // swing garland: packed like the butterflies (left half colour, right half alpha mask), joined in WebGL so the
  // flowers keep their real edges over every section. Redraws only on new video frames.
  (function initSwing() {
    if (!swingVideo) return;
    var wrap = document.getElementById('kw-swing'), canvas = document.getElementById('kw-swing-canvas');
    var gl = canvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false, preserveDrawingBuffer: false });
    if (!gl) { wrap.remove(); swingVideo.remove(); swingVideo = null; return; }
    var sh = function (type, src) { var s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    var prog = gl.createProgram();
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, 'attribute vec2 p; varying vec2 uv; void main(){ uv = vec2(p.x*.5+.5, .5-p.y*.5); gl_Position = vec4(p,0.,1.); }'));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, 'precision mediump float; varying vec2 uv; uniform sampler2D t;'
      + 'void main(){ vec3 c = texture2D(t, vec2(uv.x*.5, uv.y)).rgb; float a = texture2D(t, vec2(.5+uv.x*.5, uv.y)).r; gl_FragColor = vec4(c*a, a); }'));
    gl.linkProgram(prog); gl.useProgram(prog);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc); gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    gl.bindTexture(gl.TEXTURE_2D, gl.createTexture());
    [gl.TEXTURE_WRAP_S, gl.TEXTURE_WRAP_T].forEach(function (k) { gl.texParameteri(gl.TEXTURE_2D, k, gl.CLAMP_TO_EDGE); });
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.clearColor(0, 0, 0, 0);

    var hasVfc = 'requestVideoFrameCallback' in HTMLVideoElement.prototype, texReady = false, gen = 0;
    var draw = function () {
      if (swingVideo.readyState < 2) return;
      var w = swingVideo.videoWidth / 2, h = swingVideo.videoHeight;
      if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; gl.viewport(0, 0, w, h); texReady = false; }
      if (!texReady) { gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, swingVideo); texReady = true; }
      else gl.texSubImage2D(gl.TEXTURE_2D, 0, 0, 0, gl.RGBA, gl.UNSIGNED_BYTE, swingVideo);
      gl.clear(gl.COLOR_BUFFER_BIT);
      gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
    };
    var loop = function (g) {
      if (g !== gen) return;
      draw();
      if (swingVideo.paused || document.hidden) return;
      var f = function () { loop(g); };
      if (hasVfc) swingVideo.requestVideoFrameCallback(f); else requestAnimationFrame(f);
    };
    // first frame shows even when it never plays (reduced motion); playing keeps the loop going
    // (a new 'playing', e.g. after the source swaps on rotation, retires the previous loop)
    swingVideo.addEventListener('loadeddata', draw);
    swingVideo.addEventListener('playing', function () { loop(++gen); });
    if (swingVideo.readyState >= 2) draw();
  })();

  var playStage = function () {
    if (!reduced) bgVideo.play().catch(function () {});
    if (!reduced && swingVideo) swingVideo.play().catch(function () {});
    if (flyVideo) flyVideo.play().catch(function () {});
  };
  function startStage() {
    var wasFlying = flyOn;
    stageOn = flyOn = true;
    body.classList.add('kw-staged');
    bgVideo.currentTime = 0;
    if (flyVideo) flyVideo.currentTime = 0;
    playStage();
    if (!wasFlying) scheduleFly();
    // keep the butterflies in step with their shadows in the background video
    if (flyVideo) setInterval(function () {
      if (bgVideo.paused || flyVideo.paused || flyVideo.readyState < 2) return;
      var d = Math.abs(flyVideo.currentTime - bgVideo.currentTime);
      if (d > .12 && d < bgVideo.duration - .12) flyVideo.currentTime = bgVideo.currentTime;
    }, 1000);
  }
  // the screen turned and the layout picker swapped the renders: restart the butterfly draw chain on the new source
  document.addEventListener('kw-layout', function () { if (flyOn) scheduleFly(); });
  // nothing runs while the tab is in the background
  document.addEventListener('visibilitychange', function () {
    if (!stageOn && !flyOn) return;
    if (document.hidden) { bgVideo.pause(); if (swingVideo) swingVideo.pause(); if (flyVideo) flyVideo.pause(); }
    else { if (stageOn) playStage(); else flyVideo.play().catch(function () {}); scheduleFly(); }
  });

  // butterflies over the cover from the start (muted, so no tap needed)
  if (flyVideo) {
    flyOn = true;
    body.classList.add('kw-covering');
    flyVideo.play().catch(function () {});
    scheduleFly();
  }

  /* --- cover: the open tap plays the cover video (card -> roumdul bloom -> the background's first frame),
         the text fades as the bloom grows, then the page takes over under it --- */
  var gate = document.getElementById('kw-gate'), coverVideo = document.getElementById('kw-cover-video'), opened = false, revealed = false;
  function reveal() {
    if (revealed) return;
    revealed = true;
    if (!stageOn) startStage();
    gate.classList.add('blooming', 'open');
    body.classList.remove('kw-locked');
    window.scrollTo(0, 0);
    setTimeout(startReveal, reduced ? 0 : 500);
    setTimeout(startMoments, reduced ? 0 : 400);
    setTimeout(spawnPetals, 900);
    setTimeout(function () { gate.style.display = 'none'; coverVideo.pause(); body.classList.remove('kw-covering'); }, reduced ? 0 : 1000);
  }
  document.getElementById('kw-open').addEventListener('click', function () {
    if (opened) return;
    opened = true;
    coverVideo.setAttribute('data-lock', '');
    if (yt) { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    if (reduced) { reveal(); return; }
    coverVideo.addEventListener('timeupdate', function () {
      var t = coverVideo.currentTime;
      if (t > 2.3) gate.classList.add('blooming');   // the roumdul starts covering the card
      if (t > 4.9 && !stageOn) startStage();         // cover is on the background's first frame; start it underneath
      if (t > 5.5) reveal();
    });
    coverVideo.addEventListener('ended', reveal);
    coverVideo.addEventListener('error', reveal);
    coverVideo.currentTime = 0;
    var p = coverVideo.play();
    if (p) p.catch(reveal);
    setTimeout(reveal, 10000);   // a stalled download never keeps the guest on the cover
  });

  /* --- countdown (uses the invitation's real event date) --- */
  var cd = document.querySelector('.kw-count');
  if (cd) {
    var target = new Date(cd.getAttribute('data-target')).getTime();
    var els = {};
    cd.querySelectorAll('[data-u]').forEach(function (b) { els[b.getAttribute('data-u')] = b; });
    var pad = function (n) { return String(n).padStart(2, '0'); };
    // each number drops softly into place when it changes
    var set = function (u, v) {
      var el = els[u], txt = pad(v);
      if (el.textContent === txt) return;
      el.textContent = txt;
      if (useGsap && !document.hidden) G.fromTo(el, { yPercent: -35, opacity: .2 }, { yPercent: 0, opacity: 1, duration: .45, ease: 'power2.out', overwrite: true });
    };
    var tick = function () {
      var diff = Math.max(0, target - Date.now());
      set('d', Math.floor(diff / 86400000));
      set('h', Math.floor(diff / 3600000) % 24);
      set('m', Math.floor(diff / 60000) % 60);
      set('s', Math.floor(diff / 1000) % 60);
    };
    tick(); setInterval(tick, 1000);
  }

  /* --- RSVP: posts to the existing invitation RSVP endpoint (status yes|no + optional note) --- */
  var opts = document.getElementById('kw-rsvp-opts');
  if (opts) {
    var rsvpUrl = opts.getAttribute('data-url') || null;
    var form = document.getElementById('kw-rsvp-form'), note = document.getElementById('kw-rsvp-note'), thanks = document.getElementById('kw-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.kw-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.kw-opt').forEach(function (o) { o.classList.toggle('chosen', o === b); });
      form.classList.add('show'); thanks.classList.remove('show');
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var done = function (ok) {
        thanks.textContent = !ok ? 'សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។ ក្តីនឹករលឹក និងពរជ័យរបស់លោកអ្នក គឺមានន័យណាស់សម្រាប់យើង។');
        if (ok && chosen === 'yes') heartBurst(form.querySelector('button'));
        thanks.classList.add('show'); if (ok) form.classList.remove('show' );
      };
      if (!rsvpUrl) { done(true); return; }   // demo preview: no recipient, nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', kwInit); else kwInit();

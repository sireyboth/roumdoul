/* Shared overlay for "packed" transparent videos (public/images/invitations/assets/animals/*.mp4):
   colour on the left half, its alpha mask on the right half. Joined back into a transparent canvas in WebGL.

   Usage in a template:
     <div class="my-overlay" data-packed-video data-tile=".8" data-density=".5" aria-hidden="true">
       <video src="{{ asset('images/invitations/assets/animals/overlay-butterfly-1.mp4') }}" muted loop playsinline autoplay preload="auto"></video>
     </div>
     <script src="{{ asset('js/invitations/shared/packed-video.js') }}"></script>
   Position/size the wrapper with the template's own CSS (e.g. position:fixed; inset:0; pointer-events:none).

   data-tile    tile width as a share of the wrapper width (capped at data-max), default 1 = one copy across
   data-max     largest tile width in px, default 560 (raise it for landscape clips on wide screens)
   data-density share of tiles drawn (0–1), default 1
   data-offset  sideways shift of each tile row (share of a tile), default .37 so the repeat doesn't line up;
                0 keeps the tile seams at the wrapper's edges (best with data-tile="1" on phones)
   data-fade    soft fade at each tile's edges (share of the tile), default .07 — animals crossing a seam fade
                out instead of being cut off, and the clip's own edge pixels never show
   Without WebGL, or with prefers-reduced-motion, the wrapper is hidden. Pauses while the tab is hidden. */
(function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var VERT = 'attribute vec2 p;varying vec2 uv;void main(){uv=vec2(p.x*.5+.5,.5-p.y*.5);gl_Position=vec4(p,0.,1.);}';
  var frag = function (density, offset, fade) {
    var f = Math.max(0.001, fade).toFixed(3);
    return 'precision mediump float;varying vec2 uv;uniform sampler2D t;uniform vec2 rep;void main(){'
      + 'vec2 q=uv*rep;q.x+=floor(q.y)*' + offset.toFixed(2) + ';vec2 cell=floor(q);q=fract(q);'
      + 'if(fract(sin(dot(cell,vec2(12.9898,78.233)))*43758.5453)>' + density.toFixed(2) + '){gl_FragColor=vec4(0.);return;}'
      // keep each half's samples off the middle line so colour and mask never bleed into each other
      + 'float x=clamp(q.x,.004,.996)*.5;'
      + 'vec3 c=texture2D(t,vec2(x,q.y)).rgb;float a=texture2D(t,vec2(.5+x,q.y)).r;'
      + 'a*=smoothstep(0.,' + f + ',q.x)*smoothstep(1.,1.-' + f + ',q.x)*smoothstep(0.,' + f + ',q.y)*smoothstep(1.,1.-' + f + ',q.y);'
      + 'gl_FragColor=vec4(c*a,a);}';
  };

  function setup(wrap) {
    var video = wrap.querySelector('video');
    if (!video) return;
    var hide = function () { wrap.style.display = 'none'; video.removeAttribute('src'); video.load(); };
    if (reduced) return hide();

    var tile = parseFloat(wrap.dataset.tile) || 1;
    var maxW = parseFloat(wrap.dataset.max) || 560;
    var fade = wrap.dataset.fade === undefined ? .07 : (parseFloat(wrap.dataset.fade) || 0);
    var offset = wrap.dataset.offset === undefined ? .37 : (parseFloat(wrap.dataset.offset) || 0);
    var density = Math.max(0, Math.min(1, wrap.dataset.density === undefined ? 1 : parseFloat(wrap.dataset.density)));
    var canvas = document.createElement('canvas');
    var gl = canvas.getContext('webgl', { premultipliedAlpha: true, alpha: true, antialias: false, depth: false, stencil: false });
    if (!gl) return hide();
    var sh = function (type, src) { var s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    var prog = gl.createProgram();
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, VERT));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, frag(density, offset, fade)));
    gl.linkProgram(prog);
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return hide();
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

    canvas.style.cssText = 'display:block;width:100%;height:100%';
    // the source video stays a near-invisible 2px dot "on screen" so iOS keeps decoding it
    video.style.cssText = 'position:absolute;left:0;top:0;width:2px;height:2px;opacity:.01;pointer-events:none';
    video.muted = true;
    video.setAttribute('disablepictureinpicture', '');
    wrap.insertBefore(canvas, video);

    var sized = false;
    var resize = function () {
      if (!video.videoWidth) return;
      var w = canvas.clientWidth, h = canvas.clientHeight, dpr = Math.min(window.devicePixelRatio || 1, 1.5);
      canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
      gl.viewport(0, 0, canvas.width, canvas.height);
      var tileW = Math.min(w, maxW) * tile, tileH = tileW * video.videoHeight / (video.videoWidth / 2);
      gl.uniform2f(repLoc, w / tileW, h / tileH);
      sized = true;
    };
    addEventListener('resize', resize);

    var hasVfc = 'requestVideoFrameCallback' in HTMLVideoElement.prototype, gen = 0;
    var draw = function (g) {
      if (g !== gen) return;
      if (video.readyState >= 2 && video.videoWidth) {
        if (!sized) resize();
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, video);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
      }
      next(g);
    };
    // redraw only when the video has a new frame
    var next = function (g) {
      if (document.hidden) return;
      var f = function () { draw(g); };
      if (hasVfc) video.requestVideoFrameCallback(f); else requestAnimationFrame(f);
    };
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) video.pause();
      else { video.play().catch(function () {}); next(++gen); }
    });
    var p = video.play(); if (p) p.catch(function () {});
    next(gen);
  }

  document.querySelectorAll('[data-packed-video]').forEach(setup);
})();

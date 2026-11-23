/* Memory Hall — the 3D walk-through wedding hall (template: resources/views/invitations/templates/wedding/memory-hall.blade.php).
   Vite entry (bundles three.js). Everything is built from #mh-data (WeddingData, prepared in the Blade file):

   · outside: a night courtyard, stone facade, lanterns, the grand double door with the couple's monogram
   · inside: one long hall (marble floor, red aisle carpet, ivory panelled walls, coffered barrel vault, chandeliers),
     split into wings by doors that open as the guest walks up to them; each stop of #mh-data.stops gets its own 3D
     pieces (framed photos, easels, a guestbook, a gift box, the floral arch…) at its own place along the hall (z < 0)
   · camera: first person on a rail down the aisle (z), free look (yaw/pitch); the gaze turns by itself to the
     photo or sign being passed unless the guest looked around in the last few seconds
   · the stop panel (.mh-stop) of the place you stand at is shown in the glass sheet
   Light budget: 4 point lights travel with the guest to the nearest chandeliers (fixed count = no shader rebuilds);
   everything else glows with baked canvas light (sprites, wall pools). Candles, flames, crystals, petals are instanced. */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

(function () {
  const root = document.getElementById('mh');
  if (!root || !window.InviteKit) return;
  const data = JSON.parse(document.getElementById('mh-data').textContent || '{}');
  const params = new URLSearchParams(location.search);
  const coverEl = document.getElementById('mh-cover');
  const kit = window.InviteKit({ root, cover: !!coverEl });
  const $ = (id) => document.getElementById(id);
  const sheet = $('mh-sheet');
  const stopsEl = [...root.querySelectorAll('.mh-stop')];
  const chips = [...root.querySelectorAll('.mh-chip')];
  const stops = data.stops || [];

  const canGL = (() => { try { const c = document.createElement('canvas'); return !!(c.getContext('webgl2') || c.getContext('webgl')); } catch (e) { return false; } })();
  let flat = !canGL || params.get('flat') === '1' || (kit.reduce && params.get('flat') !== '0');
  let inside = !coverEl;

  /* ───────────────────────── shared page behaviour (3D and flat) ───────────────────────── */
  kit.lightbox();
  kit.countdown($('mh-count'));
  root.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => kit.copy(b.getAttribute('data-copy'), 'បានចម្លងលេខគណនី ✓')));
  root.querySelectorAll('[data-swatch]').forEach((b) => b.addEventListener('click', () => {
    kit.sfx('pop', .3); b.classList.remove('pop'); void b.offsetWidth; b.classList.add('pop');
    setTimeout(() => b.classList.remove('pop'), 600);
  }));
  const mapBox = $('mh-map');
  if (mapBox) mapBox.querySelector('[data-map]').addEventListener('click', () => {
    kit.sfx('paper', .3);
    mapBox.innerHTML = '<iframe src="' + mapBox.getAttribute('data-src') + '" loading="lazy" title="ផែនទី"></iframe>';
  });
  const love = $('mh-love');
  if (love) love.addEventListener('click', () => {
    kit.sfx('chime', .35);
    const r = love.getBoundingClientRect();
    kit.burst(r.left + r.width / 2, r.top, { n: 22 });
    if (hall) hall.petalBurst(60);
  });
  const thanks = $('mh-rsvp-thanks');
  kit.rsvp({
    opts: $('mh-rsvp-opts'), form: $('mh-rsvp-form'), note: $('mh-rsvp-note'), url: data.rsvpUrl || null,
    onDone(status, ok) {
      thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត' : (status === 'yes' ? 'បានចុះហត្ថលេខាក្នុងសៀវភៅភ្ញៀវ ♥ សូមអរគុណ!' : 'សូមអរគុណដែលបានជូនដំណឹង។');
      if (ok) { const r = thanks.getBoundingClientRect(); kit.burst(r.left + r.width / 2, r.top); kit.sfx('chime', .3); if (hall) hall.signBook(); }
    },
  });

  /* ───────────────────────── 3D hall ───────────────────────── */
  let hall = null, hallP = null;
  // the signs are painted on canvases: wait (max 3 s) for their fonts, Khmer ones with a Khmer sample so the right subset loads
  const fontsReady = () => Promise.race([
    Promise.all([
      ['600 40px "Cormorant Garamond"', 'Aa'], ['700 40px "Cormorant Garamond"', 'Aa'], ['italic 500 40px "Cormorant Garamond"', 'Aa'],
      ['400 40px "Great Vibes"', 'Aa'], ['400 40px "Moulpali"', 'ក'], ['400 40px "Noto Serif Khmer"', 'ក'],
      ['600 40px "Noto Serif Khmer"', 'ក'], ['700 40px "Noto Serif Khmer"', 'ក'],
    ].map(([f, t]) => (document.fonts ? document.fonts.load(f, t) : Promise.resolve()).catch(() => {}))),
    new Promise((r) => setTimeout(r, 3000)),
  ]);
  const ensureHall = () => hallP || (hallP = fontsReady().then(() => (hall = buildHall())));
  const flatBtn = $('mh-flat');
  const tourBtn = $('mh-tour');
  if (!canGL) flatBtn.hidden = true;

  const setFlat = (on) => {
    flat = on;
    root.classList.toggle('is-flat', on);
    flatBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    flatBtn.setAttribute('aria-label', on ? 'ចូលសាល 3D' : 'មើលជាទំព័រ');
    window.scrollTo(0, 0);
    if (on) { if (hall) hall.pause(); sheet.classList.remove('has-stop'); }
    else ensureHall().then((h) => { h.resume(); if (inside && !h.hasArrived()) h.arrived(); });
  };
  flatBtn.addEventListener('click', () => { kit.sfx('tap', .3); setFlat(!flat); });
  if (flat) root.classList.add('is-flat');
  else ensureHall().then((h) => { if (!coverEl) h.arrived(); });

  /* cover → open */
  const enter = () => {
    inside = true;
    root.classList.add('is-open', 'is-inside');
    kit.lock(false);
  };
  if (coverEl) {
    kit.lock(true);
    $('mh-open').addEventListener('click', () => {
      kit.startSong();
      kit.sfx('reveal', .35);
      coverEl.classList.add('is-gone');
      if (flat) { setTimeout(() => { coverEl.hidden = true; enter(); }, 700); return; }
      ensureHall().then((h) => h.openEntrance()).then(() => { coverEl.hidden = true; enter(); hall.arrived(); });
    });
  }

  /* panel grip: collapse / expand */
  $('mh-grip').addEventListener('click', () => {
    kit.sfx('tap', .2);
    const min = sheet.classList.toggle('is-min');
    $('mh-grip').setAttribute('aria-expanded', min ? 'false' : 'true');
    if (hall) hall.layoutOffset();
  });

  /* ═════════════════════════════════════════════════════════════════════════════════════════ */
  function buildHall() {
    const canvas = $('mh-canvas');
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, powerPreference: 'high-performance' });
    let pixelRatio = Math.min(window.devicePixelRatio || 1, 1.8);
    renderer.setPixelRatio(pixelRatio);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    const maxAniso = Math.min(8, renderer.capabilities.getMaxAnisotropy());

    const scene = new THREE.Scene();
    const FOG_IN = new THREE.Color('#140a06'), FOG_OUT = new THREE.Color('#0b0b1d');
    scene.fog = new THREE.Fog(FOG_OUT.clone(), 9, 46);
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), .04).texture;
    pmrem.dispose();

    const camera = new THREE.PerspectiveCamera(60, 1, .05, 140);
    camera.rotation.order = 'YXZ';

    /* ── dimensions ── */
    const W = 5.2, HW = W / 2, WALL_H = 3.5, R = HW, EYE = 1.62, DOOR_W = 2.1, DOOR_H = 2.75, DOOR_R = DOOR_W / 2;
    const ACCENT = new THREE.Color(data.accent || '#c9a24d');
    const accentHex = '#' + ACCENT.getHexString();

    /* ── canvas helpers ── */
    const tex = (cv, repeat) => {
      const t = new THREE.CanvasTexture(cv);
      t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = maxAniso;
      if (repeat) { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(repeat[0], repeat[1]); }
      return t;
    };
    const paint = (w, h, fn) => { const c = document.createElement('canvas'); c.width = w; c.height = h; fn(c.getContext('2d'), w, h); return c; };
    let seed = 11;
    const rnd = () => { seed = (seed * 16807) % 2147483647; return seed / 2147483647; };
    const isKh = (s) => /[ក-៿]/.test(s || '');
    const SERIF = (px, wt = 600, s = '') => `${wt} ${px}px ${isKh(s) ? '"Noto Serif Khmer"' : '"Cormorant Garamond", "Noto Serif Khmer"'}, serif`;
    const SCRIPT = (px, s = '') => isKh(s) ? `400 ${Math.round(px * .62)}px "Moulpali", "Noto Serif Khmer", serif` : `400 ${px}px "Great Vibes", "Moulpali", cursive`;
    const seg = (typeof Intl !== 'undefined' && Intl.Segmenter) ? new Intl.Segmenter('km', { granularity: 'word' }) : null;
    const wrap = (ctx, text, maxW) => {
      const lines = [];
      String(text || '').split(/\n/).forEach((para) => {
        const parts = seg ? [...seg.segment(para)].map((s) => s.segment) : para.split(/(\s+)/);
        let line = '';
        parts.forEach((p) => {
          const test = line + p;
          if (ctx.measureText(test).width > maxW && line.trim()) { lines.push(line.trim()); line = p.trimStart(); }
          else line = test;
        });
        if (line.trim()) lines.push(line.trim());
      });
      return lines;
    };
    const textBlock = (ctx, text, x, y, maxW, lh, maxLines) => {
      let lines = wrap(ctx, text, maxW);
      if (maxLines && lines.length > maxLines) { lines = lines.slice(0, maxLines); lines[maxLines - 1] = lines[maxLines - 1].replace(/.{0,2}$/, '…'); }
      lines.forEach((l, i) => ctx.fillText(l, x, y + i * lh));
      return y + lines.length * lh;
    };
    const goldFill = (ctx, y0, y1) => {
      const g = ctx.createLinearGradient(0, y0, 0, y1);
      g.addColorStop(0, '#fff0c4'); g.addColorStop(.45, accentHex); g.addColorStop(.55, '#a77d34'); g.addColorStop(1, '#f6d998');
      return g;
    };
    const inkGold = (ctx, y0, y1) => {
      const g = ctx.createLinearGradient(0, y0, 0, y1);
      g.addColorStop(0, '#9c7230'); g.addColorStop(.5, '#6e4c1a'); g.addColorStop(1, '#a57b35');
      return g;
    };
    const noise = (ctx, w, h, n, a, col) => {
      for (let i = 0; i < n; i++) { ctx.fillStyle = `rgba(${col},${a * rnd()})`; ctx.fillRect(rnd() * w, rnd() * h, 1 + rnd() * 2, 1 + rnd() * 2); }
    };
    const paper = (ctx, w, h) => {
      const g = ctx.createRadialGradient(w / 2, h * .45, 10, w / 2, h / 2, Math.max(w, h) * .75);
      g.addColorStop(0, '#fdf6e6'); g.addColorStop(1, '#e6d2ac');
      ctx.fillStyle = g; ctx.fillRect(0, 0, w, h);
      noise(ctx, w, h, w * h / 60, .08, '120,80,30');
    };
    const ornament = (ctx, cx, y, wd, col) => {
      ctx.save(); ctx.strokeStyle = col; ctx.fillStyle = col; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.moveTo(cx - wd / 2, y); ctx.lineTo(cx - 14, y); ctx.moveTo(cx + 14, y); ctx.lineTo(cx + wd / 2, y); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(cx, y - 8); ctx.lineTo(cx + 8, y); ctx.lineTo(cx, y + 8); ctx.lineTo(cx - 8, y); ctx.closePath(); ctx.fill();
      [-1, 1].forEach((s) => { ctx.beginPath(); ctx.arc(cx + s * (wd / 2 + 6), y, 3.5, 0, 7); ctx.fill(); });
      ctx.restore();
    };
    const glowCanvas = paint(128, 128, (c) => {
      const g = c.createRadialGradient(64, 64, 0, 64, 64, 64);
      g.addColorStop(0, 'rgba(255,255,255,1)'); g.addColorStop(.25, 'rgba(255,236,200,.55)'); g.addColorStop(1, 'rgba(255,220,170,0)');
      c.fillStyle = g; c.fillRect(0, 0, 128, 128);
    });
    const glowTex = tex(glowCanvas);

    /* ── surfaces ── */
    const wallTex = tex(paint(512, 768, (c, w, h) => {
      const g = c.createLinearGradient(0, 0, 0, h);
      g.addColorStop(0, '#b89c74'); g.addColorStop(.14, '#e4d0ad'); g.addColorStop(.62, '#e8d6b4'); g.addColorStop(.64, '#8a6a40'); g.addColorStop(.66, '#4a2e1c'); g.addColorStop(1, '#2e1a10');
      c.fillStyle = g; c.fillRect(0, 0, w, h);
      // damask
      c.globalAlpha = .07; c.fillStyle = '#8a6a36';
      for (let y = 30; y < h * .6; y += 64) for (let x = (y / 64 % 2) * 32 + 16; x < w; x += 64) {
        c.beginPath(); c.ellipse(x, y, 9, 15, 0, 0, 7); c.fill();
        c.beginPath(); c.ellipse(x - 12, y + 6, 4, 8, -.6, 0, 7); c.ellipse(x + 12, y + 6, 4, 8, .6, 0, 7); c.fill();
      }
      c.globalAlpha = 1;
      // wainscot panels
      const top = h * .68, bot = h * .95;
      c.strokeStyle = 'rgba(110,80,40,.45)'; c.lineWidth = 3; c.strokeRect(30, top, w - 60, bot - top - 10);
      c.strokeStyle = 'rgba(232,190,120,.35)'; c.lineWidth = 2; c.strokeRect(34, top + 4, w - 68, bot - top - 18);
      c.fillStyle = '#5a3c22'; c.fillRect(0, h * .965, w, h * .035);
      c.fillStyle = goldFill(c, h * .632, h * .65); c.fillRect(0, h * .632, w, h * .012);
      noise(c, w, h, 9000, .05, '90,60,20');
    }), [1, 1]);
    const floorTex = tex(paint(1024, 1024, (c, w) => {
      const t = w / 2;
      [[0, 0, '#ece2d0'], [t, t, '#ece2d0'], [t, 0, '#b49572'], [0, t, '#b49572']].forEach(([x, y, col]) => {
        c.fillStyle = col; c.fillRect(x, y, t, t);
        c.strokeStyle = col === '#ece2d0' ? 'rgba(150,130,110,.35)' : 'rgba(255,245,225,.35)'; c.lineWidth = 1.4;
        for (let v = 0; v < 7; v++) {
          c.beginPath(); let px = x + rnd() * t, py = y + rnd() * t; c.moveTo(px, py);
          for (let s = 0; s < 8; s++) { px += (rnd() - .3) * 70; py += (rnd() - .5) * 70; c.lineTo(Math.min(x + t, Math.max(x, px)), Math.min(y + t, Math.max(y, py))); }
          c.stroke();
        }
      });
      noise(c, w, w, 20000, .06, '80,60,40');
      c.strokeStyle = 'rgba(90,70,50,.5)'; c.lineWidth = 3;
      c.strokeRect(0, 0, w, w); c.beginPath(); c.moveTo(t, 0); c.lineTo(t, w); c.moveTo(0, t); c.lineTo(w, t); c.stroke();
    }), [1, 1]);
    const carpetTex = tex(paint(256, 512, (c, w, h) => {
      c.fillStyle = '#6e1220'; c.fillRect(0, 0, w, h);
      noise(c, w, h, 14000, .18, '20,0,5');
      noise(c, w, h, 5000, .1, '190,60,70');
      c.fillStyle = goldFill(c, 0, h);
      [[10, 8], [26, 3], [w - 18, 8], [w - 29, 3]].forEach(([x, ww]) => c.fillRect(x, 0, ww, h));
      c.globalAlpha = .22; c.strokeStyle = '#e2b866'; c.lineWidth = 3;
      for (let y = 64; y < h; y += 128) { c.beginPath(); c.moveTo(w / 2, y - 34); c.lineTo(w / 2 + 34, y); c.lineTo(w / 2, y + 34); c.lineTo(w / 2 - 34, y); c.closePath(); c.stroke(); }
      c.globalAlpha = 1;
    }), [1, 1]);
    const vaultTex = tex(paint(512, 512, (c, w) => {
      c.fillStyle = '#d9c39c'; c.fillRect(0, 0, w, w);
      const n = 4, s = w / n;
      for (let i = 0; i < n; i++) for (let j = 0; j < n; j++) {
        const x = i * s + 10, y = j * s + 10, z = s - 20;
        const g = c.createLinearGradient(x, y, x + z, y + z); g.addColorStop(0, '#a88a5c'); g.addColorStop(1, '#efdfbf');
        c.fillStyle = g; c.fillRect(x, y, z, z);
        c.fillStyle = '#dcc7a2'; c.fillRect(x + 12, y + 12, z - 24, z - 24);
        c.fillStyle = goldFill(c, y + z / 2 - 10, y + z / 2 + 10); c.beginPath(); c.arc(x + z / 2, y + z / 2, 9, 0, 7); c.fill();
      }
      noise(c, w, w, 6000, .06, '90,60,20');
    }), [1, 1]);
    const stoneTex = tex(paint(512, 512, (c, w) => {
      c.fillStyle = '#6d6158'; c.fillRect(0, 0, w, w);
      const rows = 8, rh = w / rows;
      for (let r = 0; r < rows; r++) for (let k = -1; k < 4; k++) {
        const bw = w / 3, x = k * bw + (r % 2) * bw / 2;
        const l = 52 + rnd() * 14;
        c.fillStyle = `hsl(32, 12%, ${l}%)`; c.fillRect(x + 3, r * rh + 3, bw - 6, rh - 6);
      }
      noise(c, w, w, 16000, .12, '30,20,10');
    }), [1, 1]);

    const M = {
      wall: new THREE.MeshLambertMaterial({ map: wallTex }),
      floor: new THREE.MeshPhongMaterial({ map: floorTex, shininess: 90, specular: new THREE.Color('#5d4c35') }),
      carpet: new THREE.MeshLambertMaterial({ map: carpetTex }),
      vault: new THREE.MeshLambertMaterial({ map: vaultTex, side: THREE.BackSide }),
      gold: new THREE.MeshStandardMaterial({ color: ACCENT.clone().lerp(new THREE.Color('#e6c27a'), .5), metalness: 1, roughness: .28 }),
      darkGold: new THREE.MeshStandardMaterial({ color: '#8a6527', metalness: 1, roughness: .4 }),
      ivory: new THREE.MeshStandardMaterial({ color: '#efe4cf', roughness: .6 }),
      wood: new THREE.MeshStandardMaterial({ color: '#4a2c19', roughness: .45, metalness: .1 }),
      stone: new THREE.MeshLambertMaterial({ map: stoneTex }),
      candle: new THREE.MeshLambertMaterial({ color: '#fff6e4', emissive: '#6b4a20' }),
      crystal: new THREE.MeshStandardMaterial({ color: '#ffffff', metalness: 1, roughness: .02 }),
      leaf: new THREE.MeshLambertMaterial({ color: '#4d6b3a' }),
    };
    const add = (o, p) => { (p || scene).add(o); return o; };
    const mesh = (geo, mat, x = 0, y = 0, z = 0, p) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); return add(m, p); };

    /* ── items (things to look at / tap) ── */
    const items = [];      // { x, y, z, obj, key?, stop, kind }
    const picks = [];      // meshes the raycaster may hit
    const lights = [];     // chandelier positions
    const glows = [];      // [x, y, z, size, alpha]
    const doors = [];

    const placeOnWall = (obj, side, z, y) => {
      obj.position.set(side * (HW - .03), y, z);
      obj.rotation.y = side < 0 ? Math.PI / 2 : -Math.PI / 2;
      return add(obj);
    };
    const wallPool = (side, z, y, w, h) => {      // warm light pool painted on the wall round a frame
      const m = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ map: glowTex, transparent: true, opacity: .5, blending: THREE.AdditiveBlending, depthWrite: false, color: '#ffc27a' }));
      placeOnWall(m, side, z, y); m.position.x -= side * .01; return m;
    };

    /* a gilded frame (bevelled extruded moulding, one mesh) with a mat and a picture; size follows the image */
    const frameGeo = (w, h, b) => {
      const s = new THREE.Shape(); s.moveTo(-w / 2 - b, -h / 2 - b); s.lineTo(w / 2 + b, -h / 2 - b); s.lineTo(w / 2 + b, h / 2 + b); s.lineTo(-w / 2 - b, h / 2 + b); s.closePath();
      const hole = new THREE.Path(); hole.moveTo(-w / 2, -h / 2); hole.lineTo(-w / 2, h / 2); hole.lineTo(w / 2, h / 2); hole.lineTo(w / 2, -h / 2); hole.closePath();
      s.holes.push(hole);
      return new THREE.ExtrudeGeometry(s, { depth: .035, bevelEnabled: true, bevelThickness: .025, bevelSize: .028, bevelSegments: 2, curveSegments: 1 });
    };
    const makeFrame = (o) => {
      const g = new THREE.Group();
      const mould = new THREE.Mesh(undefined, M.gold);
      const mat = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), new THREE.MeshLambertMaterial({ color: '#f4ecdc' }));
      const pic = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), new THREE.MeshBasicMaterial({ color: '#3a2a20' }));
      mat.position.z = .012; pic.position.z = .016;
      g.add(mould, mat, pic);
      const lamp = new THREE.Mesh(new THREE.CylinderGeometry(.025, .025, 1, 10), M.darkGold);
      lamp.rotation.z = Math.PI / 2; g.add(lamp);
      g.userData = { pic };
      g.setSize = (aspect) => {
        let w = o.maxW, h = w / aspect;
        if (h > o.maxH) { h = o.maxH; w = h * aspect; }
        const pad = o.mat ?? .09, b = o.border ?? .07;
        if (mould.geometry) mould.geometry.dispose();
        mould.geometry = frameGeo(w + pad * 2, h + pad * 2, b);
        mat.scale.set(w + pad * 2, h + pad * 2, 1); pic.scale.set(w, h, 1);
        lamp.scale.y = Math.max(.3, w * .55); lamp.position.set(0, h / 2 + pad + b + .1, .16);
        g.userData.size = [w, h];
      };
      g.setSize(o.aspect || .78);
      return g;
    };
    const loader = new THREE.TextureLoader(); loader.setCrossOrigin('anonymous');
    const photoFrame = (src, o) => {
      const f = makeFrame(o);
      const pic = f.userData.pic;
      pic.userData = { kind: 'photo', key: o.key, stop: o.stop };
      picks.push(pic);
      if (src) loader.load(src, (t) => {
        t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = maxAniso;
        const a = t.image.width / t.image.height || .78;
        f.setSize(a);
        pic.material.dispose(); pic.material = new THREE.MeshBasicMaterial({ map: t, color: '#e9e2d6' });
        pic.material.opacity = 0; pic.material.transparent = true;
        fades.push({ m: pic.material, t: 0 });
      }, undefined, () => {   // no CORS / broken link: a painted placeholder (the HTML preview still opens the real photo)
        pic.material.dispose();
        pic.material = new THREE.MeshBasicMaterial({ map: tex(paint(256, 320, (c, w, h) => { paper(c, w, h); c.fillStyle = '#8a6a3a'; c.font = SCRIPT(60); c.textAlign = 'center'; c.fillText(data.initials || '♡', w / 2, h / 2 + 20); })) });
      });
      return f;
    };
    const fades = [];
    const canvasFrame = (cv, o) => {
      const f = makeFrame({ maxW: o.maxW, maxH: o.maxH, aspect: cv.width / cv.height, mat: o.mat ?? .05, border: o.border });
      const pic = f.userData.pic;
      pic.material.dispose(); pic.material = new THREE.MeshLambertMaterial({ map: tex(cv), emissive: '#2a1d10' });
      pic.userData = { kind: 'stop', stop: o.stop };
      picks.push(pic);
      return f;
    };
    const plaque = (text, sub) => {
      const cv = paint(512, 128, (c, w, h) => {
        c.fillStyle = goldFill(c, 0, h); c.fillRect(0, 0, w, h);
        c.strokeStyle = 'rgba(90,60,20,.6)'; c.lineWidth = 4; c.strokeRect(8, 8, w - 16, h - 16);
        c.fillStyle = '#3d2610'; c.textAlign = 'center';
        c.font = SERIF(sub ? 40 : 44, 600, text); c.fillText(text, w / 2, sub ? 62 : 78, w - 40);
        if (sub) { c.font = SERIF(26, 500, sub); c.fillText(sub, w / 2, 104, w - 40); }
      });
      return new THREE.Mesh(new THREE.PlaneGeometry(.62, .155), new THREE.MeshStandardMaterial({ map: tex(cv), metalness: .6, roughness: .35 }));
    };
    const easel = (cv, x, z, rotY, stop, bw = 1.05) => {
      const g = new THREE.Group();
      const bh = bw * cv.height / cv.width;
      const legGeo = new THREE.CylinderGeometry(.022, .03, 2.15, 8);
      [[-.42, .08, -.16], [.42, .08, .16], [0, -.38, 0]].forEach(([lx, lz, rz], i) => {
        const l = mesh(legGeo, M.wood, lx * (bw / 1.05), 1.05, i === 2 ? -.4 : -.04, g);
        l.rotation.z = rz; l.rotation.x = i === 2 ? .3 : -.1;
      });
      mesh(new THREE.BoxGeometry(bw + .12, .05, .12), M.wood, 0, .95, .14, g);
      const board = makeFrame({ maxW: bw, maxH: bh, aspect: bw / bh, mat: .03, border: .045 });
      board.position.set(0, .97 + bh / 2 + .1, .1); board.rotation.x = -.1; g.add(board);
      const pic = board.userData.pic;
      pic.material.dispose(); pic.material = new THREE.MeshLambertMaterial({ map: tex(cv), emissive: '#2a1d10' });
      pic.userData = { kind: 'stop', stop }; picks.push(pic);
      g.position.set(x, 0, z); g.rotation.y = rotY;
      add(g);
      return g;
    };

    /* ── sign canvases ── */
    const signCanvas = (title, en, body, opt = {}) => paint(720, opt.h || 940, (c, w, h) => {
      paper(c, w, h);
      c.strokeStyle = goldFill(c, 0, h); c.lineWidth = 6; c.strokeRect(26, 26, w - 52, h - 52);
      c.lineWidth = 1.5; c.strokeRect(40, 40, w - 80, h - 80);
      c.textAlign = 'center'; c.fillStyle = '#9a7434';
      c.font = SERIF(30, 600); if (en) c.fillText(en.toUpperCase().split('').join(' '), w / 2, 112);
      c.fillStyle = '#3a2416'; c.font = SERIF(isKh(title) ? 58 : 70, 700, title); c.fillText(title, w / 2, 190, w - 110);
      ornament(c, w / 2, 232, 300, '#a77d34');
      let y = 300;
      if (body) y = body(c, w, h, y);
    });
    const monogramCanvas = (s = 512) => paint(s, s, (c, w) => {
      const g = c.createRadialGradient(w / 2, w / 2, 10, w / 2, w / 2, w * .7);
      g.addColorStop(0, '#5a1424'); g.addColorStop(1, '#22060c');
      c.fillStyle = g; c.fillRect(0, 0, w, w);
      c.strokeStyle = goldFill(c, 0, w); c.lineWidth = 8; c.beginPath(); c.arc(w / 2, w / 2, w * .42, 0, 7); c.stroke();
      c.lineWidth = 2; c.beginPath(); c.arc(w / 2, w / 2, w * .38, 0, 7); c.stroke();
      c.fillStyle = goldFill(c, w * .3, w * .7); c.textAlign = 'center'; c.textBaseline = 'middle';
      c.font = SCRIPT(w * .3, data.initials); c.fillText(data.initials || '♡', w / 2, w / 2 + 8, w * .7);
    });

    /* ═════════════ build: outside ═════════════ */
    const sky = mesh(new THREE.SphereGeometry(90, 32, 16), new THREE.MeshBasicMaterial({ side: THREE.BackSide, fog: false, map: tex(paint(64, 512, (c, w, h) => {
      const g = c.createLinearGradient(0, 0, 0, h);
      g.addColorStop(0, '#04040c'); g.addColorStop(.38, '#0d0b24'); g.addColorStop(.5, '#2a1a35'); g.addColorStop(.56, '#120c18'); g.addColorStop(1, '#050308');
      c.fillStyle = g; c.fillRect(0, 0, w, h);
    })) }), 0, 0, 10);
    const starGeo = new THREE.BufferGeometry(), sp = [];
    for (let i = 0; i < 700; i++) {
      const th = rnd() * Math.PI * 2, ph = rnd() * 1.2;
      sp.push(Math.cos(th) * Math.sin(ph) * 80, Math.cos(ph) * 80 + 4, Math.sin(th) * Math.sin(ph) * 80 + 10);
    }
    starGeo.setAttribute('position', new THREE.Float32BufferAttribute(sp, 3));
    const stars = add(new THREE.Points(starGeo, new THREE.PointsMaterial({ map: glowTex, size: .5, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, color: '#fff1d6', fog: false })));
    const ground = mesh(new THREE.PlaneGeometry(60, 40), new THREE.MeshPhongMaterial({ map: tex(stoneTex.image, [14, 10]), shininess: 30, color: '#bba98f' }), 0, 0, 20);
    ground.rotation.x = -Math.PI / 2;

    // facade with the doorway cut out
    const doorwayPath = (p, x0 = 0) => {
      p.moveTo(x0 - DOOR_R, 0); p.lineTo(x0 - DOOR_R, DOOR_H); p.absarc(x0, DOOR_H, DOOR_R, Math.PI, 0, true); p.lineTo(x0 + DOOR_R, 0); p.closePath();
    };
    const facadeShape = new THREE.Shape();
    facadeShape.moveTo(-16, 0); facadeShape.lineTo(16, 0); facadeShape.lineTo(16, 11); facadeShape.lineTo(-16, 11); facadeShape.closePath();
    const fh = new THREE.Path(); doorwayPath(fh); facadeShape.holes.push(fh);
    const facadeGeo = new THREE.ShapeGeometry(facadeShape, 24);
    const uv = facadeGeo.attributes.uv; for (let i = 0; i < uv.count; i++) uv.setXY(i, uv.getX(i) / 2.4, uv.getY(i) / 2.4);
    mesh(facadeGeo, M.stone, 0, 0, .1);
    // door surround: pilasters + arch moulding + cornice
    const archTube = (z, r, thick, mat) => {
      const t = mesh(new THREE.TorusGeometry(r, thick, 10, 40, Math.PI), mat, 0, DOOR_H, z);
      [-1, 1].forEach((s) => mesh(new THREE.BoxGeometry(thick * 2.2, DOOR_H, thick * 2.2), mat, s * r, DOOR_H / 2, z));
      return t;
    };
    archTube(.16, DOOR_R + .12, .09, M.gold);
    [-1, 1].forEach((s) => {
      mesh(new THREE.BoxGeometry(.5, 4.6, .3), M.ivory, s * (DOOR_R + .75), 2.3, .25);
      mesh(new THREE.BoxGeometry(.66, .2, .42), M.gold, s * (DOOR_R + .75), 4.65, .25);
      mesh(new THREE.BoxGeometry(.66, .2, .42), M.ivory, s * (DOOR_R + .75), .1, .25);
      // lanterns
      const lx = s * (DOOR_R + 1.75);
      mesh(new THREE.BoxGeometry(.26, .42, .26), M.darkGold, lx, 2.5, .35);
      mesh(new THREE.BoxGeometry(.2, .32, .2), new THREE.MeshBasicMaterial({ color: '#ffd38a' }), lx, 2.5, .35);
      glows.push([lx, 2.5, .45, 1.4, .9]);
      // topiary in a pot
      mesh(new THREE.CylinderGeometry(.32, .24, .55, 16), M.ivory, s * (DOOR_R + 2.9), .28, 1.2);
      mesh(new THREE.SphereGeometry(.52, 16, 12), M.leaf, s * (DOOR_R + 2.9), 1.0, 1.2);
      mesh(new THREE.SphereGeometry(.36, 16, 12), M.leaf, s * (DOOR_R + 2.9), 1.6, 1.2);
    });
    mesh(new THREE.BoxGeometry(5.6, .26, .5), M.ivory, 0, 4.86, .25);
    mesh(new THREE.BoxGeometry(5.9, .12, .6), M.gold, 0, 5.03, .25);
    const medal = mesh(new THREE.CircleGeometry(.48, 40), new THREE.MeshStandardMaterial({ map: tex(monogramCanvas()), roughness: .5, metalness: .3 }), 0, 4.25, .2);
    mesh(new THREE.TorusGeometry(.5, .04, 8, 40), M.gold, 0, 4.25, .2);
    // steps
    mesh(new THREE.BoxGeometry(4.2, .12, 1.0), M.ivory, 0, .06, .7);
    mesh(new THREE.BoxGeometry(5, .06, 1.6), M.ivory, 0, .03, 1.0);
    // a strip of carpet to the door + petals
    const outCarpet = mesh(new THREE.PlaneGeometry(1.5, 9), M.carpet, 0, .13, 5.5); outCarpet.rotation.x = -Math.PI / 2;
    outCarpet.material = M.carpet.clone(); outCarpet.material.map = tex(carpetTex.image, [1, 3]);
    const outLights = [-1, 1].map((s) => { const l = add(new THREE.PointLight('#ffb86b', 6, 9, 1.6)); l.position.set(s * (DOOR_R + 1.75), 2.5, .9); return l; });

    /* ═════════════ doors ═════════════ */
    const leafCanvas = (dark) => paint(256, 640, (c, w, h) => {
      const base = dark ? ['#3b2113', '#5a341d'] : ['#efe5d1', '#fbf4e4'];
      const g = c.createLinearGradient(0, 0, w, 0); g.addColorStop(0, base[0]); g.addColorStop(.5, base[1]); g.addColorStop(1, base[0]);
      c.fillStyle = g; c.fillRect(0, 0, w, h);
      if (dark) noise(c, w, h, 9000, .2, '20,10,5');
      const panel = (x, y, pw, ph) => {
        c.strokeStyle = goldFill(c, y, y + ph); c.lineWidth = 7; c.strokeRect(x, y, pw, ph);
        c.strokeStyle = dark ? 'rgba(0,0,0,.45)' : 'rgba(120,90,50,.35)'; c.lineWidth = 3; c.strokeRect(x + 12, y + 12, pw - 24, ph - 24);
      };
      panel(26, 30, w - 52, h * .44); panel(26, h * .52, w - 52, h * .42);
      c.fillStyle = goldFill(c, h * .3, h * .36); c.beginPath(); c.arc(w / 2, h * .25, 28, 0, 7); c.fill();
    });
    const leafTex = { dark: tex(leafCanvas(true)), light: tex(leafCanvas(false)) };
    const lightPlane = (z, w, h, op) => {
      const m = mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ map: glowTex, transparent: true, opacity: op, blending: THREE.AdditiveBlending, depthWrite: false, color: '#ffd9a0', fog: false }), 0, h / 2 - .2, z);
      return m;
    };
    const buildDoor = (z, wing, grand) => {
      // partition (only inside doors; the entrance uses the facade + this inner face)
      const ps = new THREE.Shape();
      ps.moveTo(-HW, 0); ps.lineTo(HW, 0); ps.lineTo(HW, WALL_H); ps.absarc(0, WALL_H, R, 0, Math.PI, false); ps.lineTo(-HW, 0);
      const ph = new THREE.Path(); doorwayPath(ph); ps.holes.push(ph);
      const pg = new THREE.ShapeGeometry(ps, 24);
      const puv = pg.attributes.uv; for (let i = 0; i < puv.count; i++) puv.setXY(i, (puv.getX(i) + HW) / 2.6, puv.getY(i) / 5.1);
      const front = mesh(pg, M.wall, 0, 0, z + .01);
      const back = mesh(pg, M.wall, 0, 0, z - .12); back.rotation.y = Math.PI;
      if (grand) front.visible = false;
      archTube(z + .06, DOOR_R + .08, .06, M.gold).position.z = z + .06;
      archTube(z - .17, DOOR_R + .08, .06, M.gold).position.z = z - .17;
      // transom: glowing glass fan above the leaves
      const fan = mesh(new THREE.CircleGeometry(DOOR_R, 32, 0, Math.PI), new THREE.MeshBasicMaterial({ color: '#ffe3b0', transparent: true, opacity: .55 }), 0, DOOR_H, z - .05);
      for (let k = 1; k < 6; k++) { const bar = mesh(new THREE.BoxGeometry(.02, DOOR_R, .02), M.gold, 0, DOOR_H, z - .03); bar.geometry.translate(0, DOOR_R / 2, 0); bar.rotation.z = Math.PI / 2 - k * Math.PI / 6; }
      // lintel sign with the wing name (inside doors)
      if (!grand && wing) {
        const cv = paint(640, 140, (c, w, h) => {
          c.textAlign = 'center'; c.fillStyle = inkGold(c, 20, 120);
          c.font = SERIF(54, 700, wing.kh); c.fillText(wing.kh, w / 2, 78, w - 40);
          c.font = SERIF(26, 600); c.fillText((wing.en || '').toUpperCase().split('').join(' '), w / 2, 124, w - 40);
        });
        mesh(new THREE.PlaneGeometry(2.6, .57), new THREE.MeshBasicMaterial({ map: tex(cv), transparent: true }), 0, WALL_H + 1.32, z + .03);
      }
      const leaves = [-1, 1].map((s) => {
        const pivot = new THREE.Group(); pivot.position.set(s * DOOR_R, 0, z - .06);
        const lw = DOOR_R - .012;
        const mats = [M.wood, M.wood, M.wood, M.wood, new THREE.MeshStandardMaterial({ map: grand ? leafTex.dark : leafTex.light, roughness: .5, metalness: .15 })];
        mats.push(mats[4]);
        const leaf = new THREE.Mesh(new THREE.BoxGeometry(lw, DOOR_H, .08), mats);
        leaf.position.set(-s * lw / 2, DOOR_H / 2, 0);
        leaf.userData = { kind: 'door' };
        pivot.add(leaf); picks.push(leaf);
        // handles
        [.07, -.07].forEach((hz) => mesh(new THREE.SphereGeometry(.045, 12, 8), M.gold, -s * (lw - .14), DOOR_H * .48, hz, pivot));
        mesh(new THREE.TorusGeometry(.09, .012, 8, 20), M.gold, -s * (lw - .14), DOOR_H * .44, .06, pivot);
        add(pivot);
        return pivot;
      });
      const light = lightPlane(z - .4, DOOR_W * 2.6, DOOR_H * 1.9, grand ? .25 : .12);
      const seam = mesh(new THREE.PlaneGeometry(.05, DOOR_H), new THREE.MeshBasicMaterial({ color: '#fff0cc', transparent: true, opacity: grand ? .9 : .5, fog: false }), 0, DOOR_H / 2, z - .11);
      const d = { z, wing, grand, leaves, light, seam, open: 0, target: 0, t: 0 };
      leaves.forEach((p) => p.children[0].userData.door = d);
      doors.push(d);
      return d;
    };

    /* ═════════════ build: the hall, stop by stop ═════════════ */
    let z = -1.4;
    let wing = null;
    const stopZ = [];
    const sideItems = { '-1': [], '1': [] };
    const frameOnWall = (f, side, fz, y, stopIdx, key, pool = true) => {
      placeOnWall(f, side, fz, y);
      const [w, h] = f.userData.size || [1, 1.3];
      if (pool) wallPool(side, fz, y + .1, w * 2.4, h * 2);
      items.push({ x: side * (HW - .03), y, z: fz, obj: f, key, stop: stopIdx, kind: key ? 'photo' : 'sign' });
      if (sideItems[side]) sideItems[side].push(fz);
      return f;
    };
    const entrance = buildDoor(0, null, true);

    const builders = {
      invite(i, z0) {
        if (data.cover) frameOnWall(photoFrame(data.cover, { maxW: 1.45, maxH: 1.9, key: 'cover', stop: i }), -1, z0 - 2.3, 1.85, i, 'cover');
        const cv = signCanvas('សូមស្វាគមន៍', 'Welcome', (c, w, h, y) => {
          c.fillStyle = '#3a2416'; c.font = SERIF(30, 500); c.fillText('to the wedding of', w / 2, y);
          c.fillStyle = inkGold(c, y + 20, y + 140); c.font = SCRIPT(92, data.names); c.fillText(data.names || '', w / 2, y + 120, w - 100);
          c.fillStyle = '#3a2416'; c.font = SERIF(30, 600, data.dateKh || ''); if (data.dateKh) c.fillText(data.dateKh, w / 2, y + 200, w - 100);
          ornament(c, w / 2, y + 250, 200, '#a77d34');
          c.font = SERIF(28, 500, 'សូម'); c.fillStyle = '#7a5a30'; c.fillText('សូមគោរពអញ្ជើញ', w / 2, y + 320);
          c.fillStyle = '#3a2416'; c.font = SERIF(isKh(data.guest) ? 40 : 48, 700, data.guest); textBlock(c, data.guest, w / 2, y + 384, w - 120, 56, 2);
          return y;
        });
        const e = easel(cv, 1.45, z0 - 2.2, -.5, i);
        items.push({ x: 1.45, y: 1.9, z: z0 - 2.2, obj: e, stop: i, kind: 'sign' });
        return 4.8;
      },
      couple(i, z0) {
        const zc = z0 - 2.1;
        [['groomPhoto', -1, 'groom', data.groom, 'កូនកំលោះ'], ['bridePhoto', 1, 'bride', data.bride, 'កូនក្រមុំ']].forEach(([k, side, key, name, role]) => {
          if (!data[k]) return;
          const f = frameOnWall(photoFrame(data[k], { maxW: 1.25, maxH: 1.65, key, stop: i }), side, zc, 1.85, i, key);
          const p = plaque(name || '', role); placeOnWall(p, side, zc, .78); p.position.x -= side * .01;
        });
        return 4.2;
      },
      message(i, z0) {
        const cv = paint(900, 700, (c, w, h) => {
          paper(c, w, h);
          c.textAlign = 'center'; c.fillStyle = '#9a7434'; c.font = SCRIPT(90); c.fillText('“', w / 2, 120);
          c.fillStyle = '#3a2416'; c.font = isKh(data.message) ? '400 34px "Noto Serif Khmer", serif' : 'italic 500 42px "Cormorant Garamond", "Noto Serif Khmer", serif';
          const end = textBlock(c, data.message, w / 2, 190, w - 160, isKh(data.message) ? 62 : 54, 7);
          ornament(c, w / 2, Math.min(h - 60, end + 10), 220, '#a77d34');
        });
        frameOnWall(canvasFrame(cv, { maxW: 1.55, maxH: 1.25, stop: i }), -1, z0 - 2.0, 1.8, i);
        const mono = canvasFrame(monogramCanvas(), { maxW: .8, maxH: .8, stop: i, border: .06 });
        frameOnWall(mono, 1, z0 - 2.0, 1.95, i);
        return 4.0;
      },
      date(i, z0) {
        const big = paint(600, 820, (c, w, h) => {
          paper(c, w, h);
          c.textAlign = 'center'; c.fillStyle = '#9a7434'; c.font = SERIF(30, 600); c.fillText('S A V E   T H E   D A T E', w / 2, 90);
          c.fillStyle = '#3a2416'; c.font = SERIF(40, 600, data.weekday || ''); if (data.weekday) c.fillText(data.weekday, w / 2, 170);
          c.fillStyle = inkGold(c, 200, 440); c.font = '700 260px "Cormorant Garamond", "Noto Serif Khmer", serif'; c.fillText(data.day || '', w / 2, 430);
          c.fillStyle = '#3a2416'; c.font = SERIF(54, 700, data.month || ''); c.fillText((data.month || '') + ' ' + (data.year || ''), w / 2, 530, w - 60);
          ornament(c, w / 2, 580, 240, '#a77d34');
          c.font = SERIF(32, 500, data.timeKh || ''); c.fillStyle = '#7a5a30'; textBlock(c, data.timeKh || '', w / 2, 650, w - 80, 46, 2);
          if (data.khmerDate) { c.font = SERIF(26, 400, data.khmerDate); textBlock(c, data.khmerDate, w / 2, 744, w - 80, 38, 2); }
        });
        frameOnWall(canvasFrame(big, { maxW: 1.2, maxH: 1.65, stop: i }), -1, z0 - 2.2, 1.85, i);
        if (data.calendar) {
          const cal = paint(700, 720, (c, w, h) => {
            paper(c, w, h);
            c.textAlign = 'center'; c.fillStyle = '#3a2416'; c.font = SERIF(46, 700, data.calendar.title); c.fillText(data.calendar.title, w / 2, 90);
            ornament(c, w / 2, 125, 200, '#a77d34');
            const cw = (w - 100) / 7;
            c.font = SERIF(26, 600, 'អា'); c.fillStyle = '#9a7434';
            data.calendar.heads.forEach((d, k) => c.fillText(d, 50 + cw * k + cw / 2, 185));
            c.font = '600 34px "Cormorant Garamond", "Noto Serif Khmer", serif';
            data.calendar.cells.forEach((v, k) => {
              const x = 50 + cw * (k % 7) + cw / 2, y = 250 + Math.floor(k / 7) * 76;
              if (v === data.calendar.day) {
                c.save(); c.translate(x, y - 12); c.fillStyle = '#9b1b30'; c.beginPath();
                c.moveTo(0, 24); c.bezierCurveTo(-46, -6, -26, -44, 0, -20); c.bezierCurveTo(26, -44, 46, -6, 0, 24); c.fill(); c.restore();
                c.fillStyle = '#fff4df';
              } else c.fillStyle = '#3a2416';
              if (v) c.fillText(v, x, y);
            });
          });
          frameOnWall(canvasFrame(cal, { maxW: 1.3, maxH: 1.35, stop: i }), 1, z0 - 2.2, 1.85, i);
        }
        return 4.6;
      },
      story(i, z0) {
        const list = data.story || [];
        list.forEach((ch, k) => {
          const cv = paint(600, 760, (c, w, h) => {
            paper(c, w, h);
            c.textAlign = 'center'; c.fillStyle = '#9a7434'; c.font = SERIF(34, 600); c.fillText('C H A P T E R  ' + (k + 1), w / 2, 90);
            c.fillStyle = '#3a2416'; c.font = SERIF(isKh(ch.place) ? 44 : 54, 700, ch.place); let y = textBlock(c, ch.place, w / 2, 170, w - 90, 62, 2);
            if (ch.when) { c.fillStyle = '#9b1b30'; c.font = SERIF(30, 600, ch.when); c.fillText(ch.when, w / 2, y + 12); y += 40; }
            ornament(c, w / 2, y + 20, 180, '#a77d34');
            c.fillStyle = '#4b3424'; c.font = isKh(ch.text) ? '400 27px "Noto Serif Khmer", serif' : 'italic 500 34px "Cormorant Garamond", serif';
            textBlock(c, ch.text, w / 2, y + 86, w - 100, isKh(ch.text) ? 50 : 44, 7);
          });
          frameOnWall(canvasFrame(cv, { maxW: 1.05, maxH: 1.35, stop: i }), k % 2 ? 1 : -1, z0 - 1.9 - k * 2.3, 1.8, i);
        });
        return 1.9 + list.length * 2.3;
      },
      schedule(i, z0) {
        if (data.scheduleImage) {
          frameOnWall(photoFrame(data.scheduleImage, { maxW: 1.4, maxH: 1.8, key: 'sched', stop: i }), -1, z0 - 2.2, 1.85, i, 'sched');
        } else {
          const rows = data.schedule || [];
          const cv = signCanvas('កម្មវិធី', 'Programme', (c, w, h, y) => {
            c.textAlign = 'left';
            rows.slice(0, 9).forEach((r, k) => {
              const ry = y + 20 + k * 66;
              c.fillStyle = '#9b1b30'; c.font = SERIF(28, 700, r.time); c.fillText(r.time || '', 70, ry, 190);
              c.fillStyle = '#3a2416'; c.font = SERIF(28, 500, r.label); c.fillText(r.label || '', 270, ry, w - 330);
              c.strokeStyle = 'rgba(150,110,60,.3)'; c.setLineDash([4, 6]); c.beginPath(); c.moveTo(70, ry + 22); c.lineTo(w - 70, ry + 22); c.stroke(); c.setLineDash([]);
            });
            return y;
          }, { h: Math.max(800, 380 + rows.length * 66) });
          const e = easel(cv, -1.45, z0 - 2.2, .5, i, 1.0);
          items.push({ x: -1.45, y: 1.9, z: z0 - 2.2, obj: e, stop: i, kind: 'sign' });
        }
        return 4.2;
      },
      gallery(i, z0) {
        const g = data.gallery || [];
        g.forEach((p, k) => {
          const side = k % 2 ? 1 : -1, fz = z0 - 1.8 - k * 1.95;
          frameOnWall(photoFrame(p.src, { maxW: 1.3, maxH: 1.55, key: 'gallery-' + k, stop: i }), side, fz, 1.85, i, 'gallery-' + k);
          if (p.cap) { const pl = plaque(p.cap); placeOnWall(pl, side, fz, .82); pl.position.x -= side * .01; }
        });
        return 1.8 + g.length * 1.95 + .4;
      },
      venue(i, z0) {
        const cv = signCanvas('ទីតាំង', 'Venue', (c, w, h, y) => {
          c.save(); c.translate(w / 2, y + 90); c.fillStyle = '#9b1b30';
          c.beginPath(); c.arc(0, -20, 46, Math.PI, 0); c.quadraticCurveTo(46, 20, 0, 70); c.quadraticCurveTo(-46, 20, -46, -20); c.fill();
          c.fillStyle = '#fdf6e6'; c.beginPath(); c.arc(0, -20, 18, 0, 7); c.fill(); c.restore();
          c.fillStyle = '#3a2416'; c.font = SERIF(isKh(data.venueName) ? 40 : 50, 700, data.venueName || ''); textBlock(c, data.venueName || '', w / 2, y + 250, w - 110, 58, 3);
          c.fillStyle = '#7a5a30'; c.font = SERIF(26, 500, 'ចុច'); c.fillText('ចុចដើម្បីមើលផែនទី', w / 2, h - 80);
          return y;
        });
        const e = easel(cv, -1.45, z0 - 2.0, .5, i);
        items.push({ x: -1.45, y: 1.9, z: z0 - 2.0, obj: e, stop: i, kind: 'sign' });
        return 3.8;
      },
      notes(i, z0) {
        const cols = data.dress || [];
        const cv = signCanvas('ពណ៌សម្លៀកបំពាក់', 'Dress code', (c, w, h, y) => {
          const n = Math.max(1, cols.length), cw = Math.min(150, (w - 120) / Math.min(3, n));
          cols.forEach((col, k) => {
            const row = Math.floor(k / 3), inRow = Math.min(3, n - row * 3), x = w / 2 + (k % 3 - (inRow - 1) / 2) * cw, cy = y + 70 + row * 190;
            c.fillStyle = 'rgba(0,0,0,.18)'; c.beginPath(); c.arc(x + 4, cy + 6, 52, 0, 7); c.fill();
            c.fillStyle = col.hex; c.beginPath(); c.arc(x, cy, 52, 0, 7); c.fill();
            const gl = c.createRadialGradient(x - 18, cy - 20, 2, x, cy, 52); gl.addColorStop(0, 'rgba(255,255,255,.55)'); gl.addColorStop(.5, 'rgba(255,255,255,0)');
            c.fillStyle = gl; c.fill();
            c.strokeStyle = goldFill(c, cy - 52, cy + 52); c.lineWidth = 5; c.stroke();
            c.fillStyle = '#3a2416'; c.font = SERIF(24, 600, col.name); c.fillText(col.name, x, cy + 90, cw - 6);
          });
          return y;
        }, { h: 760 });
        frameOnWall(canvasFrame(cv, { maxW: 1.1, maxH: 1.25, stop: i }), 1, z0 - 2.0, 1.8, i);
        return 3.8;
      },
      rsvp(i, z0) {
        // lectern + the open guestbook
        const g = new THREE.Group();
        mesh(new THREE.CylinderGeometry(.08, .1, 1.0, 12), M.wood, 0, .5, 0, g);
        mesh(new THREE.CylinderGeometry(.32, .36, .06, 20), M.wood, 0, .03, 0, g);
        const top = mesh(new THREE.BoxGeometry(.8, .05, .58), M.wood, 0, 1.05, 0, g); top.rotation.x = .32;
        const pageCv = paint(800, 540, (c, w, h) => {
          paper(c, w, h);
          c.fillStyle = 'rgba(120,80,30,.25)'; c.fillRect(w / 2 - 3, 0, 6, h);
          c.textAlign = 'center'; c.fillStyle = '#9a7434'; c.font = SERIF(26, 600); c.fillText('G U E S T  B O O K', w / 4, 80);
          c.fillStyle = '#3a2416'; c.font = SERIF(40, 700, 'សៀវភៅភ្ញៀវ'); c.fillText('សៀវភៅភ្ញៀវ', w / 4, 150);
          c.fillStyle = inkGold(c, 180, 260); c.font = SCRIPT(64, data.names); c.fillText(data.names || '', w / 4, 250, w / 2 - 60);
          c.strokeStyle = 'rgba(120,90,50,.35)'; c.lineWidth = 2;
          for (let k = 0; k < 7; k++) { c.beginPath(); c.moveTo(w / 2 + 40, 120 + k * 56); c.lineTo(w - 40, 120 + k * 56); c.stroke(); }
        });
        const bookTex = tex(pageCv);
        const book = mesh(new THREE.PlaneGeometry(.74, .5), new THREE.MeshLambertMaterial({ map: bookTex, emissive: '#2a1d10' }), 0, 1.09, .01, g);
        book.rotation.x = -Math.PI / 2 + .32; book.userData = { kind: 'stop', stop: i }; picks.push(book);
        const quill = mesh(new THREE.ConeGeometry(.02, .36, 8), M.ivory, .3, 1.16, -.05, g); quill.rotation.set(.3, 0, -.9);
        g.position.set(1.35, 0, z0 - 2.0); g.rotation.y = -.55; add(g);
        glows.push([1.35, 1.6, z0 - 2.0, 1.2, .35]);
        items.push({ x: 1.35, y: 1.1, z: z0 - 2.0, obj: g, stop: i, kind: 'sign' });
        hallApi.signBook = () => {
          const c = pageCv.getContext('2d'); c.fillStyle = '#2a3a8a'; c.font = SCRIPT(58, data.guest); c.textAlign = 'center';
          c.fillText(data.guest || '♥', pageCv.width * .75, 160, pageCv.width / 2 - 80); bookTex.needsUpdate = true;
        };
        return 3.8;
      },
      gift(i, z0) {
        const g = new THREE.Group();
        mesh(new THREE.CylinderGeometry(.34, .4, 1.0, 24), M.ivory, 0, .5, 0, g);
        mesh(new THREE.TorusGeometry(.36, .02, 8, 32), M.gold, 0, .98, 0, g).rotation.x = Math.PI / 2;
        const box = new THREE.Group(); box.position.y = 1.24; g.add(box);
        const boxMat = new THREE.MeshStandardMaterial({ color: ACCENT.clone().lerp(new THREE.Color('#7a1424'), .65), roughness: .45, metalness: .2 });
        mesh(new THREE.BoxGeometry(.42, .42, .42), boxMat, 0, 0, 0, box);
        mesh(new THREE.BoxGeometry(.46, .06, .46), boxMat, 0, .23, 0, box);
        mesh(new THREE.BoxGeometry(.07, .5, .47), M.gold, 0, .02, 0, box);
        mesh(new THREE.BoxGeometry(.47, .5, .07), M.gold, 0, .02, 0, box);
        [-1, 1].forEach((s) => { const b = mesh(new THREE.TorusGeometry(.09, .025, 8, 20), M.gold, s * .08, .3, 0, box); b.rotation.set(0, Math.PI / 2, s * .6); });
        box.children.forEach((m) => { m.userData = { kind: 'gift', stop: i }; picks.push(m); });
        g.position.set(-1.35, 0, z0 - 2.0); add(g);
        glows.push([-1.35, 1.3, z0 - 2.0, 1.4, .4]);
        spinners.push(box);
        items.push({ x: -1.35, y: 1.2, z: z0 - 2.0, obj: g, stop: i, kind: 'sign' });
        return 3.8;
      },
      contacts(i, z0) {
        const cv = signCanvas('ទំនាក់ទំនង', 'Contact', (c, w, h, y) => {
          c.save(); c.translate(w / 2, y + 70); c.strokeStyle = '#9b1b30'; c.lineWidth = 10; c.lineCap = 'round';
          c.beginPath(); c.arc(0, 0, 44, Math.PI * .15, Math.PI * .85, false); c.stroke(); c.restore();
          c.fillStyle = '#3a2416'; c.font = SERIF(34, 500, 'សូម'); textBlock(c, 'មានចម្ងល់ សូមទាក់ទងមកយើងខ្ញុំ', w / 2, y + 200, w - 120, 50, 3);
          return y;
        }, { h: 700 });
        const e = easel(cv, 1.45, z0 - 1.9, -.5, i, .95);
        items.push({ x: 1.45, y: 1.8, z: z0 - 1.9, obj: e, stop: i, kind: 'sign' });
        return 3.6;
      },
      letters(i, z0) {
        (data.letters || []).forEach((l, k) => {
          const cv = paint(700, 760, (c, w, h) => {
            paper(c, w, h);
            c.textAlign = 'center'; c.fillStyle = '#7a5320'; c.font = SERIF(46, 700, l.kh); c.fillText(l.kh, w / 2, 110, w - 80);
            ornament(c, w / 2, 150, 200, '#a77d34');
            c.fillStyle = '#3a2416'; c.font = isKh(l.text) ? '400 28px "Noto Serif Khmer", serif' : 'italic 500 34px "Cormorant Garamond", serif';
            textBlock(c, l.text, w / 2, 220, w - 110, isKh(l.text) ? 52 : 44, 9);
          });
          frameOnWall(canvasFrame(cv, { maxW: 1.1, maxH: 1.2, stop: i }), k % 2 ? 1 : -1, z0 - 1.8 - k * 2.2, 1.8, i);
        });
        return 1.8 + (data.letters || []).length * 2.2;
      },
      finale(i, z0) {
        const az = z0 - 3.6, endZ = z0 - 7.2;
        // floral arch: blossoms + leaves (two instanced meshes) along a half circle
        const flowerGeo = new THREE.IcosahedronGeometry(.085, 1), leafGeo = new THREE.SphereGeometry(.07, 6, 4);
        leafGeo.scale(1, .35, 1.8);
        const N = 300, NL = 180;
        const fl = new THREE.InstancedMesh(flowerGeo, new THREE.MeshLambertMaterial(), N);
        const lv = new THREE.InstancedMesh(leafGeo, M.leaf, NL);
        const tmp = new THREE.Object3D(), cols = ['#f7e9e4', '#f3c8cf', '#ffffff', '#e7a3b1', '#f6ddc4', '#c9475f'];
        const archR = 1.85, archY = 1.6;
        const archPt = (t, j) => {
          // t ∈ [0,1] along legs + top curve
          const total = archY * 2 + Math.PI * archR;
          let d = t * total, x, y;
          if (d < archY) { x = -archR; y = d; } else if (d < archY + Math.PI * archR) { const a = Math.PI - (d - archY) / archR; x = Math.cos(a) * archR; y = archY + Math.sin(a) * archR; } else { x = archR; y = archY - (d - archY - Math.PI * archR); }
          return [x + (rnd() - .5) * j, y + (rnd() - .5) * j];
        };
        for (let k = 0; k < N; k++) {
          const t = Math.pow(rnd(), 1) ; const [x, y] = archPt(t, .32);
          const dense = y > archY ? 1 : .7;
          tmp.position.set(x, y, az + (rnd() - .5) * .35); tmp.rotation.set(rnd() * 3, rnd() * 3, rnd() * 3);
          const s = (.7 + rnd() * .9) * dense; tmp.scale.set(s, s, s); tmp.updateMatrix();
          fl.setMatrixAt(k, tmp.matrix); fl.setColorAt(k, new THREE.Color(cols[Math.floor(rnd() * cols.length)]));
        }
        for (let k = 0; k < NL; k++) {
          const [x, y] = archPt(rnd(), .45);
          tmp.position.set(x, y, az + (rnd() - .5) * .45); tmp.rotation.set(rnd() * 3, rnd() * 3, rnd() * 3); tmp.scale.setScalar(.8 + rnd() * .8); tmp.updateMatrix();
          lv.setMatrixAt(k, tmp.matrix);
        }
        add(fl); add(lv);
        glows.push([0, archY + archR, az, 3.2, .35]);
        // the closing picture on the end wall + the closing line
        if (data.background) frameOnWall(photoFrame(data.background, { maxW: 2.0, maxH: 2.25, key: 'finale', stop: i }), 0, 0, 0, i, 'finale', false);
        const fin = items[items.length - 1];
        if (fin && fin.key === 'finale') {
          fin.obj.rotation.y = 0; fin.obj.position.set(0, 2.0, endZ + .05); fin.x = 0; fin.z = endZ + .05; fin.y = 2.0;
          const pool = new THREE.Mesh(new THREE.PlaneGeometry(4.4, 4.4), new THREE.MeshBasicMaterial({ map: glowTex, transparent: true, opacity: .35, blending: THREE.AdditiveBlending, depthWrite: false, color: '#ffcf96' }));
          pool.position.set(0, 2.1, endZ + .02); add(pool);
        }
        const cl = paint(1024, 260, (c, w, h) => {
          c.textAlign = 'center'; c.fillStyle = inkGold(c, 20, 200);
          c.font = SCRIPT(120, data.closing); c.fillText(data.closing || '', w / 2, 150, w - 60);
          c.font = SERIF(34, 600, data.names); c.fillText(data.names || '', w / 2, 230, w - 60);
        });
        mesh(new THREE.PlaneGeometry(3.6, .92), new THREE.MeshBasicMaterial({ map: tex(cl), transparent: true }), 0, 3.95, endZ + .06);
        // candle stands
        [-1, 1].forEach((s) => {
          [[1.5, 1.25], [1.95, .9]].forEach(([x, h]) => {
            mesh(new THREE.CylinderGeometry(.06, .12, h, 12), M.gold, s * x, h / 2, az - 1.4);
            candleSpots.push([s * x, h + .14, az - 1.4, .3]);
          });
        });
        // the end wall itself
        const ew = new THREE.Shape(); ew.moveTo(-HW, 0); ew.lineTo(HW, 0); ew.lineTo(HW, WALL_H); ew.absarc(0, WALL_H, R, 0, Math.PI, false); ew.lineTo(-HW, 0);
        const eg = new THREE.ShapeGeometry(ew, 24); const euv = eg.attributes.uv; for (let k = 0; k < euv.count; k++) euv.setXY(k, (euv.getX(k) + HW) / 2.6, euv.getY(k) / 5.1);
        mesh(eg, M.wall, 0, 0, endZ);
        finaleView = { z: az + 4.1 };
        return 7.2;
      },
    };
    const hallApi = { signBook() {} };
    const spinners = [];
    const candleSpots = [];
    let finaleView = null;

    stops.forEach((s, i) => {
      if (s.wing !== wing) {
        if (wing !== null) { buildDoor(z - .9, data.wings[s.wing], false); z -= 2.2; }
        wing = s.wing;
      }
      const z0 = z;
      const len = (builders[s.id] || (() => 3))(i, z0);
      stopZ.push({ z0, z1: z0 - len, view: z0 + .4 });
      z = z0 - len;
    });
    const zEnd = z;
    const L = -zEnd + .2;
    if (finaleView) stopZ[stopZ.length - 1].view = finaleView.z;

    /* shell: walls, floor, carpet, vault, cornices */
    const wallGeo = new THREE.PlaneGeometry(L, WALL_H);
    [-1, 1].forEach((s) => {
      const m = mesh(wallGeo, M.wall, s * HW, WALL_H / 2, -L / 2 + .1);
      m.rotation.y = s < 0 ? Math.PI / 2 : -Math.PI / 2;
      m.material = M.wall.clone(); m.material.map = wallTex.clone(); m.material.map.repeat.set(L / 2.6, 1); m.material.map.needsUpdate = true;
      mesh(new THREE.BoxGeometry(.16, .14, L), M.gold, s * (HW - .06), WALL_H, -L / 2 + .1);
      mesh(new THREE.BoxGeometry(.1, .1, L), M.ivory, s * (HW - .04), WALL_H - .16, -L / 2 + .1);
    });
    const floor = mesh(new THREE.PlaneGeometry(W, L), M.floor, 0, 0, -L / 2 + .1);
    floor.rotation.x = -Math.PI / 2; floor.material.map.repeat.set(W / 1.6, L / 1.6);
    floor.userData = { kind: 'floor' }; picks.push(floor);
    const carpet = mesh(new THREE.PlaneGeometry(1.6, L), M.carpet, 0, .006, -L / 2 + .1);
    carpet.rotation.x = -Math.PI / 2; carpet.material.map.repeat.set(1, L / 3.2);
    carpet.userData = { kind: 'floor' }; picks.push(carpet);
    const vault = mesh(new THREE.CylinderGeometry(R, R, L, 32, 1, true, -Math.PI / 2, Math.PI), M.vault, 0, WALL_H, -L / 2 + .1);
    vault.rotation.x = -Math.PI / 2; vault.material.map.repeat.set(3, L / 1.5);
    // vault ribs every few metres
    for (let rz = -2; rz > zEnd + 1; rz -= 4.2) mesh(new THREE.TorusGeometry(R - .03, .05, 6, 32, Math.PI), M.gold, 0, WALL_H, rz);

    /* pilasters + sconces where no frame hangs */
    for (let pz = -3; pz > zEnd + 1.5; pz -= 2.1) {
      [-1, 1].forEach((s) => {
        if (sideItems[s].some((fz) => Math.abs(fz - pz) < 1.05) || doors.some((d) => Math.abs(d.z - pz) < 1.3)) return;
        mesh(new THREE.BoxGeometry(.1, WALL_H, .34), M.ivory, s * (HW - .05), WALL_H / 2, pz);
        mesh(new THREE.BoxGeometry(.16, .18, .46), M.gold, s * (HW - .08), WALL_H - .3, pz);
        mesh(new THREE.BoxGeometry(.06, .3, .05), M.gold, s * (HW - .14), 2.25, pz);
        candleSpots.push([s * (HW - .22), 2.5, pz, .26]);
        glows.push([s * (HW - .12), 2.5, pz, .9, .3]);
      });
    }

    /* chandeliers: one per ~6 m; the meshes are instanced, 4 point lights follow the guest */
    const chandZ = [];
    for (let cz = -2.6; cz > zEnd + 2; cz -= 6.2) chandZ.push(cz);
    const armGeo = new THREE.TorusGeometry(.55, .022, 6, 40);
    chandZ.forEach((cz) => {
      mesh(new THREE.CylinderGeometry(.015, .015, 1.8, 6), M.darkGold, 0, WALL_H + R - .9, cz);
      mesh(new THREE.SphereGeometry(.12, 12, 8), M.gold, 0, 4.35, cz);
      const ring = mesh(armGeo, M.gold, 0, 4.18, cz); ring.rotation.x = Math.PI / 2;
      const ring2 = mesh(new THREE.TorusGeometry(.3, .015, 6, 30), M.gold, 0, 4.0, cz); ring2.rotation.x = Math.PI / 2;
      for (let k = 0; k < 8; k++) { const a = k / 8 * Math.PI * 2; candleSpots.push([Math.cos(a) * .55, 4.3, cz + Math.sin(a) * .55, .2]); }
      glows.push([0, 4.2, cz, 3.4, .5]);
      lights.push(new THREE.Vector3(0, 3.9, cz));
    });
    // instanced candles + crystal drops
    const candles = new THREE.InstancedMesh(new THREE.CylinderGeometry(.022, .022, .12, 8), M.candle, candleSpots.length);
    const tmpO = new THREE.Object3D();
    candleSpots.forEach(([x, y, cz], k) => { tmpO.position.set(x, y - .1, cz); tmpO.rotation.set(0, 0, 0); tmpO.scale.set(1, 1, 1); tmpO.updateMatrix(); candles.setMatrixAt(k, tmpO.matrix); });
    add(candles);
    const dropN = chandZ.length * 16;
    const drops = new THREE.InstancedMesh(new THREE.OctahedronGeometry(.035, 0), M.crystal, dropN);
    chandZ.forEach((cz, ci) => { for (let k = 0; k < 16; k++) { const a = k / 16 * Math.PI * 2, r = k % 2 ? .3 : .55; tmpO.position.set(Math.cos(a) * r, (k % 2 ? 3.9 : 4.06), cz + Math.sin(a) * r); tmpO.scale.set(1, 1.8, 1); tmpO.updateMatrix(); drops.setMatrixAt(ci * 16 + k, tmpO.matrix); } });
    add(drops);
    // flames (points) + soft glows (points)
    const pointsOf = (arr, size, color, op) => {
      const geo = new THREE.BufferGeometry(); const pos = [], sz = [];
      arr.forEach((a) => { pos.push(a[0], a[1], a[2]); sz.push(a[3]); });
      geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
      const mat = new THREE.PointsMaterial({ map: glowTex, size, transparent: true, opacity: op, depthWrite: false, blending: THREE.AdditiveBlending, color });
      return add(new THREE.Points(geo, mat));
    };
    const flames = pointsOf(candleSpots, .22, '#ffd08a', 1);
    // glows have per-item sizes: one Points object per size bucket keeps it to 3 draws
    [[0, 1.3], [1.3, 2.5], [2.5, 9]].forEach(([lo, hi]) => {
      const set = glows.filter((g) => g[3] >= lo && g[3] < hi);
      if (set.length) pointsOf(set, set.reduce((a, g) => a + g[3], 0) / set.length, '#ffc98a', .45);
    });

    /* lights */
    add(new THREE.HemisphereLight('#ffd9a8', '#2a160c', .34));
    const amb = add(new THREE.AmbientLight('#ffcf99', .1));
    const pls = [0, 1, 2, 3].map(() => add(new THREE.PointLight('#ffb466', 26, 12, 1.55)));
    const keyLight = add(new THREE.PointLight('#ffd9a0', 0, 7, 1.5)); // the light pouring out through the entrance

    /* petals: falling (instanced) + resting on the floor */
    const petalTex = tex(paint(64, 64, (c) => {
      const g = c.createRadialGradient(28, 26, 2, 32, 32, 30); g.addColorStop(0, '#ffe3e6'); g.addColorStop(.7, '#e98da0'); g.addColorStop(1, 'rgba(200,80,100,0)');
      c.fillStyle = g; c.beginPath(); c.ellipse(32, 32, 20, 28, .3, 0, 7); c.fill();
    }));
    const petalMat = new THREE.MeshLambertMaterial({ map: petalTex, transparent: true, side: THREE.DoubleSide, depthWrite: false, alphaTest: .05 });
    const PN = 90;
    const petals = new THREE.InstancedMesh(new THREE.PlaneGeometry(.06, .07), petalMat, PN);
    const pState = [];
    for (let k = 0; k < PN; k++) pState.push({ x: (rnd() - .5) * W, y: rnd() * 4.5, z: -rnd() * 14, r: rnd() * 6, s: .4 + rnd() * .6, v: .25 + rnd() * .3, w: rnd() * 6 });
    add(petals);
    const FN = 260;
    const floorPetals = new THREE.InstancedMesh(new THREE.PlaneGeometry(.06, .07), petalMat, FN);
    for (let k = 0; k < FN; k++) {
      const edge = rnd() < .75 ? (rnd() < .5 ? -1 : 1) * (.7 + rnd() * .35) : (rnd() - .5) * 1.3;
      const pz = k < 40 ? 1 + rnd() * 6 : zEnd + rnd() * (-zEnd);
      tmpO.position.set(edge, .012, pz); tmpO.rotation.set(-Math.PI / 2, 0, rnd() * 6); tmpO.scale.setScalar(.8 + rnd() * .6); tmpO.updateMatrix();
      floorPetals.setMatrixAt(k, tmpO.matrix);
    }
    add(floorPetals);
    // gold dust around the guest
    const DN = 260, dustGeo = new THREE.BufferGeometry(), dpos = new Float32Array(DN * 3);
    for (let k = 0; k < DN; k++) { dpos[k * 3] = (rnd() - .5) * W; dpos[k * 3 + 1] = rnd() * 4.5; dpos[k * 3 + 2] = -rnd() * 14; }
    dustGeo.setAttribute('position', new THREE.BufferAttribute(dpos, 3));
    const dust = add(new THREE.Points(dustGeo, new THREE.PointsMaterial({ map: glowTex, size: .05, transparent: true, opacity: .8, depthWrite: false, blending: THREE.AdditiveBlending, color: '#ffd9a0' })));
    let burst = 0;

    /* ═════════════ camera + controls ═════════════ */
    const zMax = -1.0, zMin = stopZ.length ? stopZ[stopZ.length - 1].view : zEnd + 4;
    const cam = { z: 7.4, tz: 7.4, x: 0, yaw: 0, tyaw: 0, pitch: .05, tpitch: .05, manual: 0, dist: 0, step: 0, fov: 52, tfov: 52 };
    const clampZ = (v) => Math.max(zMin, Math.min(zMax, v));
    let controls = false, tour = false, tourPause = 0, tourStop = -1;
    let active = -1, lastActiveT = 0;
    const now = () => performance.now();

    const setActive = (i) => {
      if (i === active) return;
      active = i;
      stopsEl.forEach((el, k) => el.classList.toggle('is-active', k === i));
      chips.forEach((c, k) => c.classList.toggle('is-on', k === i));
      sheet.classList.toggle('has-stop', i >= 0);
      const compact = i >= 0 && stopsEl[i].classList.contains('compact');
      sheet.classList.toggle('is-compact', compact);
      sheet.classList.remove('is-min'); sheet.scrollTop = 0;
      if (i >= 0) {
        const c = chips[i]; const box = c.parentElement;
        box.scrollTo({ left: c.offsetLeft - box.clientWidth / 2 + c.offsetWidth / 2, behavior: 'smooth' });
        if (now() - lastActiveT > 600) kit.sfx('chime', .12);
        lastActiveT = now();
      }
      $('mh-prev').disabled = i <= 0; $('mh-next').disabled = i >= stops.length - 1;
      layoutOffset();
    };
    const goStop = (i) => {
      i = Math.max(0, Math.min(stops.length - 1, i));
      cam.tz = clampZ(stopZ[i].view); cam.manual = 0;
      cam.fast = Math.abs(cam.tz - cam.z) > 8;
      kit.sfx('tap', .22);
    };
    const findItem = (key) => items.find((it) => it.key === key);
    const lookAtYaw = (it, fromZ) => Math.atan2(-(it.x - cam.x), -(it.z - fromZ));
    let arrive = null;
    const walkTo = (tz, yaw) => new Promise((res) => {
      cam.tz = clampZ(tz);
      if (yaw != null) { cam.tyaw = yaw; cam.tpitch = 0; cam.manual = now() + 7000; }
      arrive = res;
    });
    const projRect = (obj) => {
      const pic = obj.userData.pic || obj;
      pic.geometry.computeBoundingBox();
      const bb = pic.geometry.boundingBox, v = new THREE.Vector3(); let x0 = 1e9, y0 = 1e9, x1 = -1e9, y1 = -1e9;
      const rect = canvas.getBoundingClientRect();
      [[bb.min.x, bb.min.y], [bb.max.x, bb.min.y], [bb.min.x, bb.max.y], [bb.max.x, bb.max.y]].forEach(([px, py]) => {
        v.set(px, py, 0).applyMatrix4(pic.matrixWorld).project(camera);
        const sx = rect.left + (v.x + 1) / 2 * rect.width, sy = rect.top + (1 - v.y) / 2 * rect.height;
        x0 = Math.min(x0, sx); y0 = Math.min(y0, sy); x1 = Math.max(x1, sx); y1 = Math.max(y1, sy);
      });
      return { left: x0, top: y0, width: x1 - x0, height: y1 - y0 };
    };
    const openPhoto = (key) => {
      const it = findItem(key);
      const btn = root.querySelector('#mh-pbtns [data-key="' + key + '"]');
      if (!it || !btn) return;
      stopTour();
      const standZ = it.x === 0 ? it.z + 3.2 : it.z + 1.75;
      kit.sfx('step', .2);
      walkTo(standZ, it.x === 0 ? 0 : lookAtYaw(it, clampZ(standZ))).then(() => {
        it.obj.updateMatrixWorld(true);
        const r = projRect(it.obj);
        root.querySelectorAll('#mh-pbtns button').forEach((b) => { b.style.width = b.style.height = '0px'; });
        Object.assign(btn.style, { left: r.left + 'px', top: r.top + 'px', width: r.width + 'px', height: r.height + 'px' });
        btn.click();
      });
    };

    /* gaze: the guest's head turns to what they pass, unless they looked around recently */
    const gazeItem = () => {
      let best = null, bd = 1e9;
      items.forEach((it) => {
        const d = cam.z - it.z;
        if (d < .6 || d > 5.2 || it.x === 0) return;
        const s = Math.abs(d - 2.3);
        if (s < bd) { bd = s; best = it; }
      });
      return best;
    };

    /* raycasting */
    const ray = new THREE.Raycaster(), ndc = new THREE.Vector2();
    const hit = (cx, cy) => {
      const r = canvas.getBoundingClientRect();
      ndc.set((cx - r.left) / r.width * 2 - 1, -((cy - r.top) / r.height) * 2 + 1);
      ray.setFromCamera(ndc, camera);
      const h = ray.intersectObjects(picks, false)[0];
      return h && h.distance < 16 ? h : null;
    };
    const onTap = (cx, cy) => {
      const h = hit(cx, cy);
      if (!h) return;
      const u = h.object.userData;
      if (u.kind === 'photo') openPhoto(u.key);
      else if (u.kind === 'stop') { stopTour(); goStop(u.stop); sheet.classList.remove('is-min'); }
      else if (u.kind === 'door') { if (u.door) openDoor(u.door); }
      else if (u.kind === 'gift') { kit.sfx('chime', .3); kit.burst(cx, cy, { n: 18 }); petalBurst(30); goStop(u.stop); }
      else if (u.kind === 'floor') { stopTour(); cam.tz = clampZ(h.point.z + .4); kit.sfx('step', .2); }
    };

    /* pointer: vertical drag walks, horizontal drag looks, a short tap picks */
    let pd = null;
    canvas.addEventListener('pointerdown', (e) => {
      if (!controls) return;
      pd = { x: e.clientX, y: e.clientY, t: now(), mode: null, yaw: cam.tyaw, pitch: cam.tpitch, tz: cam.tz, lastY: e.clientY, lastT: now(), vy: 0 };
      canvas.setPointerCapture(e.pointerId); canvas.classList.add('is-drag');
    });
    canvas.addEventListener('pointermove', (e) => {
      if (!pd) {
        if (controls && e.pointerType === 'mouse') { const h = hit(e.clientX, e.clientY); canvas.classList.toggle('is-point', !!h && h.object.userData.kind !== 'floor'); }
        return;
      }
      const dx = e.clientX - pd.x, dy = e.clientY - pd.y;
      if (!pd.mode && Math.hypot(dx, dy) > 8) { pd.mode = Math.abs(dx) > Math.abs(dy) * 1.1 ? 'look' : 'walk'; stopTour(); }
      if (pd.mode === 'look') {
        cam.tyaw = Math.max(-1.5, Math.min(1.5, pd.yaw + dx * .0055));
        cam.tpitch = Math.max(-.5, Math.min(.5, pd.pitch + dy * .003));
        cam.manual = now() + 5000;
      } else if (pd.mode === 'walk') {
        cam.tz = clampZ(pd.tz + dy * .024);
        const t = now(); pd.vy = (e.clientY - pd.lastY) / Math.max(1, t - pd.lastT); pd.lastY = e.clientY; pd.lastT = t;
      }
    });
    const up = (e) => {
      if (!pd) return;
      canvas.classList.remove('is-drag');
      if (!pd.mode && now() - pd.t < 400) onTap(e.clientX, e.clientY);
      else if (pd.mode === 'walk') cam.tz = clampZ(cam.tz + pd.vy * 6);
      pd = null;
    };
    canvas.addEventListener('pointerup', up);
    canvas.addEventListener('pointercancel', () => { pd = null; canvas.classList.remove('is-drag'); });
    canvas.addEventListener('wheel', (e) => {
      if (!controls) return;
      e.preventDefault(); stopTour();
      cam.tz = clampZ(cam.tz + e.deltaY * (e.deltaMode ? .5 : .006));
    }, { passive: false });
    const keys = {};
    const lbOpen = () => !!root.querySelector('.ik-lb:not([hidden])');
    window.addEventListener('keydown', (e) => {
      if (!controls || flat || lbOpen() || /INPUT|TEXTAREA/.test((e.target || {}).tagName || '')) return;
      const k = e.key.toLowerCase();
      if (['arrowup', 'arrowdown', 'arrowleft', 'arrowright', 'w', 'a', 's', 'd'].includes(k)) { keys[k] = true; stopTour(); e.preventDefault(); }
      if (k === 'pagedown' || k === ' ') { e.preventDefault(); goStop(active + 1); }
      if (k === 'pageup') { e.preventDefault(); goStop(active - 1); }
    });
    window.addEventListener('keyup', (e) => { keys[e.key.toLowerCase()] = false; });

    /* nav */
    $('mh-prev').addEventListener('click', () => { stopTour(); goStop(active - 1); });
    $('mh-next').addEventListener('click', () => { stopTour(); goStop(active + 1); });
    chips.forEach((c) => c.addEventListener('click', () => { stopTour(); goStop(+c.getAttribute('data-go')); }));
    const again = $('mh-again'); if (again) again.addEventListener('click', () => { goStop(0); });
    // panel thumbnails walk to the photo on the wall first (3D only)
    sheet.addEventListener('click', (e) => {
      if (flat) return;
      const b = e.target.closest('[data-key]');
      if (!b || !findItem(b.getAttribute('data-key'))) return;
      e.stopPropagation(); e.preventDefault();
      openPhoto(b.getAttribute('data-key'));
    }, true);
    // footer «back to top» = walk back to the entrance
    root.addEventListener('click', (e) => { if (!flat && e.target.closest('[data-inv-top]')) { e.stopPropagation(); e.preventDefault(); goStop(0); } }, true);

    /* auto tour */
    function stopTour() { if (!tour) return; tour = false; tourBtn.setAttribute('aria-pressed', 'false'); }
    tourBtn.addEventListener('click', () => {
      tour = !tour; tourBtn.setAttribute('aria-pressed', tour ? 'true' : 'false'); kit.sfx('tap', .25);
      if (tour) { tourStop = Math.max(-1, active - 1); tourPause = 0; cam.manual = 0; if (active >= stops.length - 1) { cam.tz = stopZ[0].view; tourStop = -1; } }
    });

    /* doors */
    const wingCard = $('mh-wing');
    function openDoor(d) {
      if (d.target) return;
      d.target = 1; d.t = 0;
      kit.sfx('whoosh', .35);
      if (!d.grand && d.wing) {
        wingCard.querySelector('small').textContent = d.wing.en || '';
        wingCard.querySelector('b').textContent = d.wing.kh || '';
        wingCard.classList.remove('show'); void wingCard.offsetWidth; wingCard.classList.add('show');
        setTimeout(() => kit.sfx('chime', .18), 500);
      }
    }
    const ease = (t) => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;

    /* sheet → move the picture's centre into the free part of the screen */
    const off = { x: 0, y: 0, tx: 0, ty: 0 };
    function layoutOffset() {
      const w = canvas.clientWidth;
      if (!sheet.classList.contains('has-stop') || !inside) { off.tx = 0; off.ty = 0; return; }
      if (w >= 900) { off.tx = -(sheet.offsetWidth + 22) / 2; off.ty = 0; }
      else { off.tx = 0; off.ty = Math.min(sheet.offsetHeight, canvas.clientHeight * .5) / 2; }
    }

    /* resize */
    const resize = () => {
      const w = canvas.clientWidth || innerWidth, h = canvas.clientHeight || innerHeight;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      cam.baseFov = w / h < .8 ? 68 : (w / h < 1.3 ? 62 : 55);
      if (inside) cam.tfov = cam.baseFov;
      camera.updateProjectionMatrix();
      layoutOffset();
    };
    window.addEventListener('resize', resize);
    resize();

    /* ═════════════ opening the entrance ═════════════ */
    let opening = null;
    const openEntrance = () => new Promise((res) => {
      opening = { t: 0, start: now(), res, flashed: false };
      kit.lock(true);
    });
    const flash = $('mh-flash');

    /* ═════════════ the loop ═════════════ */
    let last = now(), running = true, raf = 0, frames = 0, slow = 0;
    const lerp = (a, b, k) => a + (b - a) * k;
    const fogCol = new THREE.Color();
    const pdummy = new THREE.Object3D();
    const tick = () => {
      if (!running) return;
      raf = requestAnimationFrame(tick);
      const t = now(), dt = Math.min(.1, (t - last) / 1000); last = t;
      if (lbOpen()) return;                       // the photo preview covers the hall: don't burn frames behind it
      const time = t / 1000;

      /* opening sequence: approach → doors swing → walk through the light */
      if (opening) {
        const o = opening; o.t = (t - o.start) / 1000;
        cam.tyaw = 0; cam.tpitch = 0;
        if (o.t < .9) cam.tz = lerp(7.4, 4.2, ease(Math.min(1, o.t / .9)));
        if (o.t > .55 && !entrance.target) { openDoor(entrance); entrance.speed = .75; }
        if (o.t > 1.1) {
          const k = ease(Math.min(1, (o.t - 1.1) / 2.6));
          cam.z = cam.tz = lerp(4.2, stopZ.length ? stopZ[0].view : -2, k);
          cam.tfov = lerp(52, cam.baseFov, k);
        }
        const fk = o.t < 1.6 ? Math.max(0, (o.t - .8) / .8) : Math.max(0, 1 - (o.t - 1.6) / 1.1);
        flash.style.opacity = (fk * .55).toFixed(3);
        keyLight.intensity = Math.min(1, o.t / 1.4) * 30;
        if (o.t > 1.3 && !o.flashed) { o.flashed = true; petalBurst(70); kit.sfx('chime', .25); }
        if (o.t > 3.8) { opening = null; flash.style.opacity = 0; o.res(); }
      }

      /* tour */
      if (tour) {
        if (tourPause > 0) { tourPause -= dt; if (tourPause <= 0 && tourStop >= stops.length - 1) stopTour(); }
        else {
          const next = Math.min(stops.length - 1, tourStop + 1);
          cam.tz = clampZ(Math.max(stopZ[next].view, cam.tz - 1.25 * dt));
          if (Math.abs(cam.z - stopZ[next].view) < .08) { tourStop = next; tourPause = stops[next].id === 'gallery' ? 1.2 : 4; }
          if (stops[next].id === 'gallery' && tourStop < next) {/* keep walking through the gallery slowly */}
        }
      }

      /* keys */
      if (keys.arrowup || keys.w) cam.tz = clampZ(cam.tz - 2.4 * dt);
      if (keys.arrowdown || keys.s) cam.tz = clampZ(cam.tz + 2.4 * dt);
      if (keys.arrowleft || keys.a) { cam.tyaw = Math.min(1.5, cam.tyaw + 1.6 * dt); cam.manual = t + 5000; }
      if (keys.arrowright || keys.d) { cam.tyaw = Math.max(-1.5, cam.tyaw - 1.6 * dt); cam.manual = t + 5000; }

      /* move */
      const prevZ = cam.z;
      if (!opening || opening.t <= 1.1) {
        const vmax = cam.fast ? 15 : 6;
        const v = Math.max(-vmax, Math.min(vmax, (cam.tz - cam.z) * (cam.fast ? 2.4 : 3.2)));
        if (cam.fast && Math.abs(cam.tz - cam.z) < .5) cam.fast = false;
        cam.z += v * dt;
      }
      const speed = Math.abs(cam.z - prevZ) / Math.max(dt, 1e-4);
      cam.dist += Math.abs(cam.z - prevZ);
      if (controls && speed > .35 && cam.dist - cam.step > .75) { cam.step = cam.dist; kit.sfx('step', .12); }
      if (arrive && Math.abs(cam.z - cam.tz) < .04 && Math.abs(cam.yaw - cam.tyaw) < .03) { const a = arrive; arrive = null; a(); }

      /* gaze */
      if (controls && t > cam.manual && !arrive) {
        const it = gazeItem();
        const k = canvas.clientWidth / canvas.clientHeight < 1 ? .82 : .45;
        cam.tyaw = it ? lookAtYaw(it, cam.z) * k : 0;
        cam.tpitch = it ? Math.atan2(it.y - EYE, Math.max(1, cam.z - it.z)) * .4 : (active === stops.length - 1 ? .13 : 0);
      }
      if (!controls && !opening && !inside) { // idle outside: breathe + sway
        cam.tyaw = Math.sin(time * .25) * .05 + pointer.x * .08;
        cam.tpitch = .06 + Math.sin(time * .32) * .015 - pointer.y * .04;
      }
      const ky = 1 - Math.exp(-dt * 2.6);
      cam.yaw += (cam.tyaw - cam.yaw) * ky; cam.pitch += (cam.tpitch - cam.pitch) * ky;
      cam.fov += (cam.tfov - cam.fov) * (1 - Math.exp(-dt * 2));
      const bob = Math.sin(cam.dist / .75 * Math.PI) * .022 * Math.min(1, speed / 1.2);
      camera.position.set(Math.sin(time * .3) * .03, EYE + bob + (inside ? 0 : .08), cam.z);
      camera.rotation.set(cam.pitch, cam.yaw, Math.sin(cam.dist / .75 * Math.PI / 2) * .004 * Math.min(1, speed));
      if (Math.abs(camera.fov - cam.fov) > .01) { camera.fov = cam.fov; camera.updateProjectionMatrix(); }
      off.x += (off.tx - off.x) * (1 - Math.exp(-dt * 4)); off.y += (off.ty - off.y) * (1 - Math.exp(-dt * 4));
      const cw = canvas.clientWidth, ch = canvas.clientHeight;
      if (Math.abs(off.x) + Math.abs(off.y) > .5) camera.setViewOffset(cw, ch, off.x, off.y, cw, ch); else if (camera.view && camera.view.enabled) camera.clearViewOffset();

      /* doors */
      doors.forEach((d) => {
        if (!d.target && controls && cam.z < d.z + 4.2 && cam.z > d.z - 1) openDoor(d);
        if (d.target && d.open < 1) {
          d.t = Math.min(1, d.t + dt * (d.speed || .62)); d.open = ease(d.t);
          d.leaves[0].rotation.y = d.open * 1.75; d.leaves[1].rotation.y = -d.open * 1.75;
          d.light.material.opacity = (d.grand ? .25 : .12) + Math.sin(d.open * Math.PI) * .55;
          d.seam.material.opacity = (1 - d.open) * (d.grand ? .9 : .5);
        }
      });
      if (!entrance.target) entrance.seam.material.opacity = .7 + Math.sin(time * 2.2) * .2;

      /* fog / outside lights blend as the guest walks in */
      const inK = Math.max(0, Math.min(1, (2.5 - cam.z) / 3.5));
      fogCol.copy(FOG_OUT).lerp(FOG_IN, inK); scene.fog.color.copy(fogCol);
      scene.fog.near = lerp(12, 5, inK); scene.fog.far = lerp(60, 30, inK);
      outLights.forEach((l) => { l.intensity = (1 - inK) * 6; });
      keyLight.position.set(0, 2.2, -1.5); if (!opening) keyLight.intensity = lerp(keyLight.intensity, (1 - inK) * 30 * (entrance.open), .05);
      sky.visible = stars.visible = cam.z > -6;

      /* point lights → nearest chandeliers */
      const near = lights.map((p) => [Math.abs(p.z - (cam.z - 3)), p]).sort((a, b) => a[0] - b[0]);
      pls.forEach((l, k) => { const n = near[k]; if (n) { l.position.copy(n[1]); l.intensity = inside || opening ? 26 : 0; } else l.intensity = 0; });
      amb.intensity = lerp(.06, .1, inK);

      /* particles near the guest */
      const pz0 = cam.z - 13;
      for (let k = 0; k < PN; k++) {
        const p = pState[k];
        p.y -= p.v * dt * (1 + burst * 2); p.x += Math.sin(time * .8 + p.w) * .12 * dt; p.r += dt * (1 + p.s);
        if (p.y < 0) { p.y = 3.6 + rnd() * 1.2; p.x = (rnd() - .5) * W * .9; p.z = pz0 + rnd() * 14; }
        if (p.z > cam.z + 1.5) p.z -= 14; else if (p.z < pz0) p.z += 14;
        pdummy.position.set(p.x, p.y, p.z); pdummy.rotation.set(p.r, p.r * .7, p.w); pdummy.scale.setScalar(p.s * 1.4); pdummy.updateMatrix();
        petals.setMatrixAt(k, pdummy.matrix);
      }
      petals.instanceMatrix.needsUpdate = true;
      burst = Math.max(0, burst - dt * .6);
      for (let k = 0; k < DN; k++) {
        let y = dpos[k * 3 + 1] + dt * .06, zz = dpos[k * 3 + 2];
        if (y > 4.6) y = .1;
        if (zz > cam.z + 1.5) zz -= 14; else if (zz < pz0) zz += 14;
        dpos[k * 3 + 1] = y; dpos[k * 3 + 2] = zz; dpos[k * 3] += Math.sin(time + k) * .0015;
      }
      dustGeo.attributes.position.needsUpdate = true;
      flames.material.opacity = .85 + Math.sin(time * 13) * .06 + Math.sin(time * 7.3) * .05;
      spinners.forEach((b) => { b.rotation.y += dt * .5; b.position.y = 1.24 + Math.sin(time * 1.6) * .04; });
      fades.forEach((f) => { if (f.t < 1) { f.t = Math.min(1, f.t + dt * 1.5); f.m.opacity = f.t; if (f.t === 1) f.m.transparent = false; } });
      medal.rotation.z = Math.sin(time * .5) * .02;

      /* which stop are we at */
      if (inside) {
        let a = 0;
        stopZ.forEach((s, k) => { if (cam.z <= s.view + .9) a = k; });
        setActive(a);
      }

      renderer.render(scene, camera);
      // adaptive resolution: drop the pixel ratio if frames are slow
      if (inside && ++frames % 90 === 0) {
        if (slow / 90 > 26 && pixelRatio > 1) { pixelRatio = Math.max(1, pixelRatio - .35); renderer.setPixelRatio(pixelRatio); resize(); }
        slow = 0;
      }
      slow += dt * 1000;
    };

    const pointer = { x: 0, y: 0 };
    window.addEventListener('pointermove', (e) => { pointer.x = e.clientX / innerWidth - .5; pointer.y = e.clientY / innerHeight - .5; });
    function petalBurst(n) { burst = Math.min(2.5, burst + n / 40); }

    // wait for the fonts the signs are painted with, then paint + start
    const loading = $('mh-loading');
    tick();
    requestAnimationFrame(() => loading.classList.add('is-done'));

    let arrivedOnce = false;
    const api = {
      openEntrance,
      hasArrived: () => arrivedOnce,
      arrived() {
        inside = true; controls = true; arrivedOnce = true;
        if (!opening) { cam.z = cam.tz = stopZ.length ? stopZ[0].view : -2; entrance.target = 1; entrance.t = 1; entrance.open = .999; }
        cam.tfov = cam.baseFov;
        const hint = $('mh-hint');
        const want = params.get('stop');
        if (want != null && stopZ[+want]) { cam.z = cam.tz = stopZ[+want].view; }
        hint.classList.add('show'); setTimeout(() => hint.classList.remove('show'), 4800);
        canvas.addEventListener('pointerdown', () => hint.classList.remove('show'), { once: true });
        kit.sfx('chime', .25);
        setTimeout(layoutOffset, 80);
      },
      pause() { running = false; cancelAnimationFrame(raf); },
      resume() { if (running) return; running = true; last = now(); resize(); tick(); },
      layoutOffset() { setTimeout(layoutOffset, 30); },
      petalBurst,
      signBook() { hallApi.signBook(); },
    };
    document.addEventListener('visibilitychange', () => { if (document.hidden) api.pause(); else if (!flat) api.resume(); });
    return api;
  }
})();

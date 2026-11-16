/* Rose Bloom invitation (resources/views/invitations/templates/anniversary-category/rose-bloom.blade.php).
   All content comes from the JSON in #rb-config, built from the invitation's $fields. */
(function () {
'use strict';

const CONFIG = JSON.parse(document.getElementById('rb-config').textContent);
const ROOT = document.querySelector('.rb');
const HAS_GSAP = !!window.gsap;
const HEARTS = { tip(){} };   // hearts hunt hooks, filled in by setupHearts()
document.body.classList.add('rb-page');
if (HAS_GSAP) { ROOT.classList.add('js'); document.body.classList.add('rb-locked'); }

/* ---------- basics ---------- */
history.scrollRestoration = 'manual';
const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
const DPR = Math.min(window.devicePixelRatio || 1, 2);
const MOBILE = Math.min(innerWidth, innerHeight) < 700;
const REDUCE = matchMedia('(prefers-reduced-motion: reduce)').matches;
const rand = (a, b) => a + Math.random() * (b - a);

/* ---------- sound effects (CONFIG.sfx = name → url). Decoded up front (the context starts suspended, so nothing
   plays) and woken by the guest's first tap; silent with reduced motion.
   VOL is each effect's loudness — they all sit well under the music. ---------- */
const SFX = (() => {
  const VOL = { chime:.45, envelope:.6, scratch:.28, sparkle:.3, cassette:.55, card:.4, pop:.3, finale:.4 };
  let ac = null, out = null, muted = false; const buf = {};
  const urls = CONFIG.sfx || {};
  const AC = window.AudioContext || window.webkitAudioContext;
  if (AC && !REDUCE) {
    ac = new AC(); out = ac.createGain(); out.connect(ac.destination);
    Object.keys(urls).forEach(k => fetch(urls[k]).then(r => r.arrayBuffer())
      .then(a => new Promise((ok, no) => ac.decodeAudioData(a, ok, no))).then(b => { buf[k] = b; }).catch(() => {}));
  }
  /* returns how long the effect lasts in seconds (0 when nothing plays), so music can wait for it */
  function play(k, rate){
    if (!ac || muted || !buf[k]) return 0;
    if (ac.state === 'suspended') ac.resume();
    const s = ac.createBufferSource(), g = ac.createGain();
    s.buffer = buf[k]; s.playbackRate.value = rate || 1; g.gain.value = VOL[k] ?? .4;
    s.connect(g); g.connect(out); s.start(); return buf[k].duration / (rate || 1);
  }
  /* scratch: one looping source that is only audible while the finger is moving */
  function scratcher(){
    let n = null, quiet = 0;
    return {
      start(){ if (n || !ac || muted || !buf.scratch) return;
        if (ac.state === 'suspended') ac.resume();
        n = { s: ac.createBufferSource(), g: ac.createGain() };
        n.s.buffer = buf.scratch; n.s.loop = true; n.g.gain.value = 0;
        n.s.connect(n.g); n.g.connect(out); n.s.start(0, Math.random() * buf.scratch.duration); },
      move(){ if (!n) return; n.g.gain.setTargetAtTime(VOL.scratch, ac.currentTime, .02);
        clearTimeout(quiet); quiet = setTimeout(() => n && n.g.gain.setTargetAtTime(0, ac.currentTime, .04), 90); },
      stop(){ if (!n) return; clearTimeout(quiet); const m = n; n = null;
        m.g.gain.setTargetAtTime(0, ac.currentTime, .03); setTimeout(() => { try { m.s.stop(); } catch (e) {} }, 200); },
    };
  }
  return { play, scratcher, get muted(){ return muted; }, ready(){ return !!ac && ac.state === 'running'; },
    setMuted(v){ muted = v; if (out) out.gain.setTargetAtTime(v ? 0 : 1, ac.currentTime, .05); } };
})();

/* play + pause inside a tap: phones then allow this element to play later (after a sound effect) without a new tap */
function unlock(a){ if(!a) return; const p=a.play(); if(p) p.catch(()=>{}); a.pause(); }

/* ---------- background piano (CONFIG.bgMusic): starts after the opening chime, stops when the cassette plays ---------- */
const BG = CONFIG.bgMusic ? Object.assign(new Audio(), { src: CONFIG.bgMusic, loop: true, preload: 'none', volume: .6 }) : null;
const BGM = (() => {
  let wanted = false, muted = false, wait = 0;
  const go = () => { if (BG && wanted && !muted) BG.play().catch(() => {}); };
  return {
    get started(){ return !!BG && !BG.paused; },
    startAfter(sec){ if (!BG) return; if (BG.paused) unlock(BG); wanted = true; clearTimeout(wait); wait = setTimeout(go, sec * 1000); },
    stop(){ wanted = false; clearTimeout(wait); if (BG) BG.pause(); },
    mute(m){ muted = m; if (!BG) return; if (m) BG.pause(); else go(); },
  };
})();

function rng(a){ return function(){ a|=0; a=a+0x6D2B79F5|0; let t=Math.imul(a^a>>>15,1|a); t=t+Math.imul(t^t>>>7,61|t)^t; return ((t^t>>>14)>>>0)/4294967296; }; }
function mk(w,h){ const c=document.createElement('canvas'); c.width=w; c.height=h; return c; }
function esc(s){ return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function hexA(hex,a){ const n=parseInt(hex.slice(1),16); return `rgba(${n>>16},${n>>8&255},${n&255},${a})`; }

const noiseC = mk(160,160);
(()=>{ const x=noiseC.getContext('2d'), d=x.createImageData(160,160);
  for(let i=0;i<d.data.length;i+=4){ const v=Math.random()*255|0; d.data[i]=d.data[i+1]=d.data[i+2]=v; d.data[i+3]=Math.random()*18|0; }
  x.putImageData(d,0,0); ROOT.style.setProperty('--noise',`url(${noiseC.toDataURL()})`); })();

/* film-photo placeholders for photos the customer hasn't added */
const THEMES={
  golden:{ sky:['#f9d48a','#ee9a6c','#8a4b4a'], bok:['#ffd38a','#ffb36b','#fff0c2'] },
  dusk:  { sky:['#f8bfcb','#c48bb4','#5b4a7a'], bok:['#ffc0d0','#ffd6a0','#e7b8ff'] },
  night: { sky:['#1f2a4a','#33406a','#6a4a6a'], bok:['#ffcf7a','#ff9fb3','#9fd8ff'] },
  sea:   { sky:['#cdeaf0','#86bfcd','#ecd9b6'], bok:['#ffffff','#fff1c9','#d5f3ff'] },
  blossom:{ sky:['#fde7ea','#f4bccb','#cf93a6'], bok:['#ffffff','#ffe0ea','#fff3d6'] }
};
function filmPhoto(theme,seed,w=420,h=520){
  const T=THEMES[theme]||THEMES.golden, c=mk(w,h), x=c.getContext('2d'), r=rng(seed);
  let g=x.createLinearGradient(0,0,0,h); T.sky.forEach((s,i)=>g.addColorStop(i/(T.sky.length-1),s)); x.fillStyle=g; x.fillRect(0,0,w,h);
  const sx=w*(.2+r()*.6), sy=h*(.15+r()*.3);
  g=x.createRadialGradient(sx,sy,0,sx,sy,w*.7); g.addColorStop(0,'rgba(255,245,220,.75)'); g.addColorStop(1,'rgba(255,245,220,0)'); x.fillStyle=g; x.fillRect(0,0,w,h);
  x.globalCompositeOperation='lighter';
  for(let i=0;i<34;i++){ const px=r()*w, py=r()*h*.85, rr=8+r()*46, col=T.bok[i%T.bok.length], a=+(.12+r()*.28).toFixed(2);
    g=x.createRadialGradient(px,py,rr*.6,px,py,rr); g.addColorStop(0,hexA(col,a)); g.addColorStop(.85,hexA(col,a*.8)); g.addColorStop(1,hexA(col,0));
    x.fillStyle=g; x.beginPath(); x.arc(px,py,rr,0,7); x.fill(); }
  x.globalCompositeOperation='source-over';
  const off=w*3, dark=theme==='night'?'rgba(10,10,25,.85)':'rgba(50,30,40,.62)', base=h*1.02;
  x.save(); x.shadowColor=dark; x.shadowBlur=w*.035; x.shadowOffsetX=off; x.fillStyle='#000';
  for(const [px,k] of [[w*(.4+r()*.04),1],[w*(.6+r()*.04),.93]]){
    x.beginPath(); x.ellipse(px-off,base,w*.2*k,h*.22*k,0,0,7); x.fill();
    x.beginPath(); x.arc(px-off,base-h*.3*k,w*.085*k,0,7); x.fill(); }
  x.restore();
  g=x.createRadialGradient(0,h*.4,0,0,h*.4,w*.8); g.addColorStop(0,'rgba(255,150,80,.3)'); g.addColorStop(1,'rgba(255,150,80,0)');
  x.globalCompositeOperation='screen'; x.fillStyle=g; x.fillRect(0,0,w,h); x.globalCompositeOperation='source-over';
  x.globalAlpha=.9; x.fillStyle=x.createPattern(noiseC,'repeat'); x.fillRect(0,0,w,h); x.fillRect(0,0,w,h); x.globalAlpha=1;
  g=x.createRadialGradient(w/2,h/2,w*.35,w/2,h/2,w*.85); g.addColorStop(0,'rgba(0,0,0,0)'); g.addColorStop(1,'rgba(20,10,30,.4)'); x.fillStyle=g; x.fillRect(0,0,w,h);
  x.font='bold 20px "Courier New",monospace'; x.textAlign='right'; x.fillStyle='#ffa04a'; x.shadowColor='rgba(255,120,40,.9)'; x.shadowBlur=6;
  x.fillText(`’2${4+(seed%3)}  ${1+seed%12}  ${10+seed%18}`,w-18,h-18);
  return c.toDataURL('image/jpeg',.86);
}

/* =========================================================
   THREE.JS: 3D roses + instanced petal storm
   ========================================================= */
let GL=null;
if (HAS_GSAP) { try{ GL=initGL(); }catch(e){ console.warn('WebGL unavailable',e); $('#gl').style.display='none'; } }
else $('#gl').style.display='none';

function petalGeo(w,h,cup,curl,base,tip,sx=8,sy=10){
  const g=new THREE.PlaneGeometry(1,1,sx,sy), p=g.attributes.position, cols=new Float32Array(p.count*3);
  const cb=new THREE.Color(base).convertSRGBToLinear(), ct=new THREE.Color(tip).convertSRGBToLinear(), c=new THREE.Color();
  for(let i=0;i<p.count;i++){
    const x=p.getX(i), v=p.getY(i)+.5, ex=2*x;
    const prof=Math.pow(Math.sin(Math.PI*(.08+.84*v)),.5)*(.25+.75*Math.min(1,v*1.8));
    const X=x*w*prof, Y=v*h;
    const Z=cup*w*.5*ex*ex*Math.min(1,v*1.5)
           +h*.1*Math.sin(Math.PI*v)*(1-curl)
           -curl*h*.38*Math.pow(Math.max(0,v-.5)/.5,2)
           -curl*w*.12*Math.pow(Math.abs(ex),3)*v;
    p.setXYZ(i,X,Y,Z);
    c.copy(cb).lerp(ct,Math.pow(v,.75));
    const ao=.55+.45*Math.min(1,v*2.2), edge=1-.08*Math.abs(ex);
    cols[i*3]=c.r*ao*edge; cols[i*3+1]=c.g*ao*edge; cols[i*3+2]=c.b*ao*edge;
  }
  g.setAttribute('color',new THREE.BufferAttribute(cols,3));
  g.computeVertexNormals();
  return g;
}

function initGL(){
  const canvas=$('#gl');
  const renderer=new THREE.WebGLRenderer({canvas,antialias:true,alpha:true,powerPreference:'high-performance'});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,MOBILE?1.75:2));
  renderer.setClearColor(0x000000,0);
  renderer.outputEncoding=THREE.sRGBEncoding;
  renderer.toneMapping=THREE.ACESFilmicToneMapping; renderer.toneMappingExposure=.95;
  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(38,innerWidth/innerHeight,.05,60);

  scene.add(new THREE.HemisphereLight(0xfff4f6,0x8a5a7a,.55));
  const key=new THREE.DirectionalLight(0xfff0e6,1.5); key.position.set(2.5,4,3); scene.add(key);
  const rim=new THREE.DirectionalLight(0xbcd4ff,.8); rim.position.set(-3,1.5,-2.5); scene.add(rim);
  const fill=new THREE.PointLight(0xffc6d4,.6,10); fill.position.set(0,-1,2.5); scene.add(fill);

  const LAYERS=[
    {n:3,r:.02,y:.07,tilt:-.06,w:.34,h:.42,cup:.95,curl:0},
    {n:4,r:.05,y:.05,tilt:-.18,w:.42,h:.5,cup:.85,curl:.08},
    {n:5,r:.08,y:.03,tilt:-.36,w:.5,h:.56,cup:.72,curl:.22},
    {n:6,r:.11,y:.01,tilt:-.58,w:.58,h:.6,cup:.62,curl:.42},
    {n:7,r:.14,y:-.01,tilt:-.86,w:.64,h:.62,cup:.52,curl:.66},
    {n:8,r:.16,y:-.03,tilt:-1.12,w:.7,h:.6,cup:.42,curl:.88}
  ];
  const ROSEPAL={
    pink: {base:'#7e1238',tip:'#f590ab',sheen:'#ffd0dc'},
    blush:{base:'#a8405f',tip:'#f9c6d2',sheen:'#ffe6ec'},
    peach:{base:'#a8473d',tip:'#f7b99c',sheen:'#ffe2d2'}
  };
  const leafMat=new THREE.MeshStandardMaterial({vertexColors:true,side:THREE.DoubleSide,roughness:.5});
  function makeRose(palKey,seed){
    const P=ROSEPAL[palKey], r=rng(seed), group=new THREE.Group(), petals=[];
    const mat=new THREE.MeshPhysicalMaterial({vertexColors:true,side:THREE.DoubleSide,roughness:.58,sheen:1,sheenRoughness:.4,sheenColor:new THREE.Color(P.sheen).convertSRGBToLinear()});
    LAYERS.forEach((L,li)=>{
      for(let k=0;k<L.n;k++){
        const a=k/L.n*Math.PI*2+li*.7+(r()-.5)*.3;
        const geo=petalGeo(L.w*(.9+r()*.2),L.h*(.9+r()*.15),L.cup,Math.min(1,L.curl*(.8+r()*.4)),P.base,P.tip);
        const m=new THREE.Mesh(geo,mat); m.rotation.order='YXZ';
        m.rotation.y=a+Math.PI; m.rotation.x=L.tilt+(r()-.5)*.12; m.rotation.z=(r()-.5)*.1;
        m.position.set(Math.sin(a)*L.r,L.y,Math.cos(a)*L.r); m.userData.layer=li;
        group.add(m); petals.push(m);
      }
    });
    for(let k=0;k<5;k++){ const a=k/5*Math.PI*2;
      const s=new THREE.Mesh(petalGeo(.14,.38,.3,.5,'#3f6b3a','#7ea866'),leafMat); s.rotation.order='YXZ';
      s.rotation.y=a+Math.PI; s.rotation.x=-1.85; s.position.set(Math.sin(a)*.08,-.05,Math.cos(a)*.08); group.add(s); }
    return {group,petals,mat};
  }
  function leaf(len,ang,tilt){
    const m=new THREE.Mesh(petalGeo(len*.48,len,.35,.15,'#2f5530','#86ad6c',6,10),leafMat);
    m.rotation.order='YXZ'; m.rotation.y=ang; m.rotation.x=tilt; return m;
  }

  /* bouquet */
  const bouquet=new THREE.Group(); scene.add(bouquet);
  const roses=[makeRose('pink',3),makeRose('blush',8),makeRose('peach',13),makeRose('blush',21),makeRose('pink',34)];
  const placements=[[0,.05,0,1.5,0,0],[-.82,-.28,.15,1.05,.42,.25],[.82,-.3,.12,1.05,-.42,.2],[-.42,-.5,.6,.85,.3,.55],[.46,-.52,.58,.85,-.3,.55]];
  roses.forEach((R,i)=>{ const [x,y,z,s,rz,rx]=placements[i]; R.group.position.set(x,y,z); R.group.scale.setScalar(s); R.group.rotation.set(rx,i*1.3,rz); bouquet.add(R.group); });
  const leaves=new THREE.Group(); bouquet.add(leaves);
  for(let i=0;i<9;i++){ const a=i/9*Math.PI*2+.3, l=leaf(.75+Math.random()*.25,a,-1.25-Math.random()*.25); l.position.set(Math.sin(a)*.35,-.35,Math.cos(a)*.35); leaves.add(l); }

  /* instanced petal field */
  const N=MOBILE?420:720;
  const pGeo=petalGeo(.13,.17,.55,.35,'#ffffff','#ffffff',5,6);
  { const c=pGeo.attributes.color.array, ps=pGeo.attributes.position; for(let i=0;i<ps.count;i++){ const v=ps.getY(i)/.17, s=.72+.28*Math.pow(Math.max(0,v),.6); c[i*3]=c[i*3+1]=c[i*3+2]=s; } }
  const pMat=new THREE.MeshStandardMaterial({vertexColors:true,side:THREE.DoubleSide,roughness:.55});
  const inst=new THREE.InstancedMesh(pGeo,pMat,N); inst.instanceMatrix.setUsage(THREE.DynamicDrawUsage); inst.frustumCulled=false; scene.add(inst);
  const PCOL=['#f39ab2','#e8708f','#f9c4d0','#fde3e3','#d94f78','#f5b29e','#fbd3dd'].map(c=>new THREE.Color(c).convertSRGBToLinear());
  const S={ t:new Float32Array(N*3), dl:new Float32Array(N), p:new Float32Array(N*3), v:new Float32Array(N*3), r:new Float32Array(N*3), w:new Float32Array(N*3), s:new Float32Array(N), ph:new Float32Array(N), mode:new Uint8Array(N) };
  const dummy=new THREE.Object3D();
  for(let i=0;i<N;i++){ inst.setColorAt(i,PCOL[i%PCOL.length]); S.ph[i]=Math.random()*6.28; hide(i); }
  inst.instanceColor.needsUpdate=true;
  function hide(i){ S.mode[i]=0; dummy.position.set(0,-99,0); dummy.scale.setScalar(0); dummy.updateMatrix(); inst.setMatrixAt(i,dummy.matrix); }
  function setP(i,x,y,z,vx,vy,vz,s,mode){ const j=i*3; S.p[j]=x; S.p[j+1]=y; S.p[j+2]=z; S.v[j]=vx; S.v[j+1]=vy; S.v[j+2]=vz;
    S.r[j]=Math.random()*6; S.r[j+1]=Math.random()*6; S.r[j+2]=Math.random()*6;
    S.w[j]=rand(-2,2); S.w[j+1]=rand(-2,2); S.w[j+2]=rand(-2,2); S.s[i]=s; S.mode[i]=mode; }
  function free(){ for(let i=0;i<N;i++) if(!S.mode[i]) return i; return -1; }
  let ambientTarget=0;
  function ambientSpawn(i,anywhere){ setP(i,rand(-3.5,3.5),anywhere?rand(-2.5,3):rand(2.6,3.4),rand(-3,1.2),rand(-.05,.12),rand(-.45,-.25),0,rand(.7,1.05),1); }
  function setAmbient(n){ ambientTarget=REDUCE?0:n; let have=0; for(let i=0;i<N;i++) if(S.mode[i]===1) have++;
    for(;have<ambientTarget;have++){ const i=free(); if(i<0) break; ambientSpawn(i,true); } }
  function storm(n,origin,power=1){ for(let k=0;k<n;k++){ const i=free(); if(i<0) break;
      const th=Math.random()*Math.PI*2, u=Math.random()*1.6-.6, sp=rand(1.2,3.4)*power, d=new THREE.Vector3(Math.cos(th)*Math.sqrt(1-u*u),u+.25,Math.sin(th)*Math.sqrt(1-u*u)+.9).normalize();
      setP(i,origin.x+rand(-.25,.25),origin.y+rand(-.15,.25),origin.z+rand(-.25,.25),d.x*sp,d.y*sp,d.z*sp,rand(.8,1.3),2); } }
  function shower(n){ if(REDUCE) return; for(let k=0;k<n;k++){ const i=free(); if(i<0) break; setP(i,rand(-3,3),rand(2.4,7),rand(-2,1.5),rand(-.1,.1),rand(-1.6,-.9),0,rand(.9,1.4),3); } }

  /* petals gather into a beating heart */
  const heart={s:1,y:0};
  function formHeart(){
    const t=Math.tan(THREE.MathUtils.degToRad(camera.fov/2)), hw=t*5*camera.aspect, hh=t*5;
    const R=Math.min(1.7,hw*.84,hh*.75), sc=R/16, n=MOBILE?300:520; heart.y=.1; heart.s=1;
    for(let k=0;k<n;k++){ const i=free(); if(i<0) break;
      const a=Math.random()*Math.PI*2, edge=Math.random()<.68, rf=edge?rand(.93,1.03):Math.sqrt(rand(.05,.85));
      const hx=16*Math.pow(Math.sin(a),3), hy=13*Math.cos(a)-5*Math.cos(2*a)-2*Math.cos(3*a)-Math.cos(4*a)+2.5;
      const th=Math.random()*Math.PI*2, sp=rand(1.5,3.6), u=Math.random()*1.4-.4;
      setP(i,rand(-.6,.6),rand(-.3,.6),rand(-.4,.4),Math.cos(th)*sp*.6,u*sp*.5+.8,Math.sin(th)*sp*.35+.3,rand(.65,1)*(MOBILE?.9:1),4);
      S.t[i*3]=hx*rf*sc; S.t[i*3+1]=hy*rf*sc; S.t[i*3+2]=rand(-.3,.3)+(edge?0:-.15); S.dl[i]=rand(.3,1.6); }
  }
  function vortex(){ for(let i=0;i<N;i++) if(S.mode[i]===4||S.mode[i]===2||S.mode[i]===3){ const j=i*3;
      S.mode[i]=5; S.v[j]=-(S.p[j+1]-heart.y)*1.5+S.p[j]*.4; S.v[j+1]=S.p[j]*1.5; S.v[j+2]=rand(.6,2); } }

  /* camera framing */
  const look=new THREE.Vector3(0,-.05,0); const cam={z:1.18};
  function frame(){ const t=Math.tan(THREE.MathUtils.degToRad(camera.fov/2)); const d=Math.max(1.75/t, 1.6/(t*camera.aspect));
    camera.position.set(0,d*.48*cam.z,d*.88*cam.z); camera.lookAt(look); }
  function resize(){ renderer.setSize(innerWidth,innerHeight,false); camera.aspect=innerWidth/innerHeight; camera.updateProjectionMatrix(); if(state==='intro') frame(); }
  let state='intro'; resize(); addEventListener('resize',resize);

  /* snapshot a rose for the letter and finale (rendered once, transparent) */
  function snapshot(){
    const keep=camera.position.clone();
    inst.visible=false; roses.forEach((R,i)=>R.group.visible=i===0); leaves.visible=false;
    renderer.setSize(512,512,false); camera.aspect=1; camera.updateProjectionMatrix();
    camera.position.set(0,2.3,2.7); camera.lookAt(0,.1,0); renderer.render(scene,camera);
    const url=renderer.domElement.toDataURL('image/png');
    inst.visible=true; roses.forEach(R=>R.group.visible=true); leaves.visible=true;
    camera.position.copy(keep); resize(); camera.lookAt(look);
    return url;
  }
  const roseURL=snapshot();

  /* close the roses into buds so they can bloom on screen */
  roses.forEach((R,ri)=>{
    R.petals.forEach(m=>{ const L=m.userData.layer; m.userData.open={rx:m.rotation.x,x:m.position.x,z:m.position.z};
      m.userData.open.ry=m.rotation.y; m.rotation.x=-.03-L*.015; m.rotation.y+=.35; m.position.x*=.28; m.position.z*=.28; });
    if(ri>0){ R.group.userData.s=R.group.scale.x; R.group.scale.setScalar(.001); }
  });
  leaves.scale.setScalar(.001);
  function bloom(){
    const tl=gsap.timeline();
    tl.from(bouquet.position,{y:-.4,duration:6,ease:'sine.out'},0);
    tl.from(bouquet.rotation,{y:-.6,duration:7,ease:'sine.out'},0);
    tl.to(cam,{z:1,duration:7,ease:'sine.inOut',onUpdate:()=>{ if(state==='intro') frame(); }},0);
    tl.to(leaves.scale,{x:1,y:1,z:1,duration:3,ease:'sine.out'},.3);
    roses.forEach((R,ri)=>{
      const t0=ri===0?.5:2.3+(ri-1)*.55;
      if(ri>0) tl.to(R.group.scale,{x:R.group.userData.s,y:R.group.userData.s,z:R.group.userData.s,duration:1.8,ease:'power3.out'},t0-.5);
      R.petals.forEach(m=>{ const o=m.userData.open, L=m.userData.layer, at=t0+(5-L)*(ri===0?.42:.22)+Math.random()*.15, d=ri===0?3.6:2.8;
        tl.to(m.rotation,{x:o.rx,y:o.ry,duration:d,ease:'sine.inOut'},at);
        tl.to(m.position,{x:o.x,z:o.z,duration:d,ease:'sine.inOut'},at); });
    });
    return tl;
  }

  /* loop */
  const clock=new THREE.Clock(); let T=0, raf=0;
  function tick(){
    const dt=Math.min(clock.getDelta(),.05); T+=dt;
    if(state==='intro'){ bouquet.rotation.y=Math.sin(T*.35)*.35; bouquet.position.y=Math.sin(T*.9)*.03; }
    let ambientHave=0;
    for(let i=0;i<N;i++){
      const m=S.mode[i]; if(!m) continue; const j=i*3;
      if(m===1){ ambientHave++;
        S.v[j]+=(Math.sin(T*.8+S.ph[i])*.35-S.v[j])*.8*dt; S.v[j+2]+=(Math.cos(T*.6+S.ph[i])*.12-S.v[j+2])*.8*dt;
      } else if(m===2){ const dr=1-1.15*dt; S.v[j]*=dr; S.v[j+1]=S.v[j+1]*dr-.55*dt; S.v[j+2]*=dr;
        S.v[j]+=Math.sin(T*2+S.ph[i])*.25*dt;
      } else if(m===4){
        if(S.dl[i]>0){ S.dl[i]-=dt; const dr=1-1.4*dt; S.v[j]*=dr; S.v[j+1]=S.v[j+1]*dr-.4*dt; S.v[j+2]*=dr; }
        else { const hs=heart.s, tx=S.t[j]*hs+Math.sin(T*1.7+S.ph[i])*.025, ty=S.t[j+1]*hs+heart.y+Math.cos(T*1.3+S.ph[i])*.025, tz=S.t[j+2];
          S.v[j]+=((tx-S.p[j])*5-S.v[j]*3.3)*dt; S.v[j+1]+=((ty-S.p[j+1])*5-S.v[j+1]*3.3)*dt; S.v[j+2]+=((tz-S.p[j+2])*5-S.v[j+2]*3.3)*dt; }
      } else if(m===5){
        const px=S.p[j], py=S.p[j+1]-heart.y; S.v[j]+=(-py*7+px*1.2)*dt; S.v[j+1]+=(px*7+py*1.2)*dt; S.v[j+2]+=3.2*dt;
      } else { S.v[j]+=Math.sin(T*1.3+S.ph[i])*.5*dt; }
      S.p[j]+=S.v[j]*dt; S.p[j+1]+=S.v[j+1]*dt; S.p[j+2]+=S.v[j+2]*dt;
      const spin=m===2?1.6:m===4?.35:m===5?2.2:1; S.r[j]+=S.w[j]*dt*spin; S.r[j+1]+=S.w[j+1]*dt*spin; S.r[j+2]+=S.w[j+2]*dt*spin;
      const y=S.p[j+1], z=S.p[j+2], cz=camera.position.z;
      if(y<-3.2||z>cz-1.4||Math.abs(S.p[j])>7){
        if(m===1 && ambientHave<=ambientTarget) ambientSpawn(i,false); else { hide(i); continue; }
      }
      if(m===2 && S.v[j+1]<-.2 && Math.abs(S.v[j])<.4) S.mode[i]=3;
      dummy.position.set(S.p[j],S.p[j+1],S.p[j+2]); dummy.rotation.set(S.r[j],S.r[j+1],S.r[j+2]); dummy.scale.setScalar(S.s[i]);
      dummy.updateMatrix(); inst.setMatrixAt(i,dummy.matrix);
    }
    inst.instanceMatrix.needsUpdate=true;
    renderer.render(scene,camera);
    raf=requestAnimationFrame(tick);
  }
  function start(){ if(!raf){ clock.getDelta(); raf=requestAnimationFrame(tick); } }
  document.addEventListener('visibilitychange',()=>{ if(document.hidden){ cancelAnimationFrame(raf); raf=0; } else start(); });
  setAmbient(MOBILE?40:70); start();

  /* the bloom-to-petals burst */
  function burst(){
    state='burst';
    const tl=gsap.timeline();
    tl.to(bouquet.scale,{x:1.08,y:1.08,z:1.08,duration:1.1,ease:'sine.inOut'},0)
      .to(bouquet.rotation,{y:'+=0.9',duration:3.5,ease:'sine.inOut'},0)
      .to(bouquet.position,{y:'+=0.12',duration:1.1,ease:'sine.inOut'},0);
    const c=new THREE.Vector3();
    roses.forEach((R,ri)=>{
      const rd=ri===0?1.5:.8+(ri-1)*.18;
      R.petals.forEach(m=>{
        const at=rd+(5-m.userData.layer)*.28+Math.random()*.35, dur=rand(2.8,3.8);
        tl.add(()=>{ scene.attach(m); const d=m.position.clone().sub(c).normalize(); d.y+=1.1; d.z-=.15; d.x*=1.2; d.normalize(); const dist=rand(1.4,2.8);
          gsap.to(m.position,{x:m.position.x+d.x*dist,y:m.position.y+d.y*dist,z:m.position.z+d.z*dist,duration:dur,ease:'sine.out'});
          gsap.to(m.rotation,{x:'+='+rand(-3,3),y:'+='+rand(-3,3),z:'+='+rand(-3,3),duration:dur,ease:'sine.inOut'});
          gsap.to(m.scale,{x:0,y:0,z:0,duration:1.4,delay:dur-1.5,ease:'sine.in'}); },at);
      });
    });
    tl.to(leaves.scale,{x:.001,y:.001,z:.001,duration:1.6,ease:'sine.in'},1.2);
    tl.add(()=>storm(MOBILE?40:70,new THREE.Vector3(0,.2,0),.7),1.1);
    tl.add(()=>storm(MOBILE?50:90,new THREE.Vector3(0,.2,0),.8),1.9);
    tl.add(()=>storm(MOBILE?40:70,new THREE.Vector3(0,.2,0),.9),2.6);
    tl.to(camera.position,{x:0,y:0,z:5,duration:3.6,ease:'sine.inOut'},1.2);
    tl.to(look,{x:0,y:0,z:0,duration:3.6,ease:'sine.inOut',onUpdate:()=>camera.lookAt(look)},1.2);
    tl.add(formHeart,2.6);
    tl.to(bouquet.scale,{x:.001,y:.001,z:.001,duration:1.2,ease:'sine.in'},3.4);
    tl.add(()=>{ scene.remove(bouquet); roses.forEach(R=>R.petals.forEach(m=>scene.remove(m))); state='free'; },6.2);
    return tl;
  }
  /* reduced motion: the bouquet simply fades away, no storm/heart/rush */
  function quietExit(){ state='free'; scene.remove(bouquet); camera.position.set(0,0,5); camera.lookAt(0,0,0); }
  function beat(){ return gsap.timeline().to(heart,{s:1.14,duration:.16,ease:'power2.out'}).to(heart,{s:1,duration:.34,ease:'power2.in'})
      .to(heart,{s:1.1,duration:.14,ease:'power2.out'}).to(heart,{s:1,duration:.5,ease:'power2.inOut'}); }
  function rush(){ vortex(); gsap.to(camera.position,{z:2.2,duration:1.8,ease:'power2.in'});
    gsap.delayedCall(2.4,()=>{ camera.position.set(0,0,5); camera.lookAt(0,0,0); }); }
  return { burst, bloom, beat, rush, shower, setAmbient, quietExit, roseURL, canvas };
}

/* =========================================================
   content
   ========================================================= */
$('#envTo').textContent=CONFIG.to; $('#letterTo').textContent=CONFIG.to; $('#letterFrom').textContent=CONFIG.from;
$('#letterBody').innerHTML=CONFIG.letter.map(p=>`<p>${esc(p)}</p>`).join('');
$('#songTitle').textContent=CONFIG.songTitle; $('#finaleBig').textContent=CONFIG.finaleBig; $('#finaleSmall').textContent=CONFIG.finaleSmall;
$('#heartText').textContent=CONFIG.heartLine;
if(GL) $('#finaleRose').src=GL.roseURL; else $('#finaleRose').remove();
/* photos the customer hasn't added: film-style placeholders */
$$('img[data-ph]').forEach(img=>{ if(!img.getAttribute('src')) img.src=filmPhoto(img.dataset.ph,+img.dataset.seed||1,420,480); });
const FL=(kind,cls)=>`<svg class="fl ${cls||''}" aria-hidden="true"><use href="#rb-${kind}"/></svg>`;
$('#stamps').innerHTML=CONFIG.moments.map((m,i)=>`
  <div class="stamp" tabindex="0" aria-label="Scratch to reveal photo ${i+1}">
    <div class="stamp-inner"><img class="zoom" data-cap="${esc(m.caption)}" alt="" src="${esc(m.photo||filmPhoto(m.theme,i*7+3))}"><canvas></canvas></div>
    <p class="cap"><span class="hint-t">scratch to see</span><span class="txt">${esc(m.caption)}</span></p>
  </div>`).join('');
$('#track').innerHTML=CONFIG.strip.map((s,i)=>`
  <figure class="pol" style="--r:${[-3,2.5,-1.5,3,-2][i%5]}deg">
    <span class="tape"></span><img class="zoom" data-cap="${esc(s.date?s.title+" — "+s.date:s.title)}" alt="" src="${esc(s.photo||filmPhoto(s.theme,90+i*13,420,420))}"><h3>${esc(s.title)}</h3><p>${esc(s.date)}</p>${i%2===0?FL('phlox'):''}${i===2?'<button type="button" class="hh" aria-label="A hidden heart"><svg aria-hidden="true"><use href="#rb-heart"/></svg></button>':''}
  </figure>`).join('');


/* no animation library (offline / blocked CDN): show the letter straight away, everything still works */
if(!HAS_GSAP){
  $('#intro').style.display='none'; $('#envScene').style.display='none'; $('#introBg').style.display='none';
  $('#book').classList.add('show'); document.body.classList.add('rb-reading');
  $$('.stamp').forEach(s=>s.classList.add('revealed'));
  setupDeck(); setupMap(); setupMusic(); setupFinale(); setupHearts(); setupPreview();
  return;
}

/* =========================================================
   flow
   ========================================================= */
let started=false, opened=false;
const bloomTL=gsap.timeline();
if(GL) bloomTL.add(GL.bloom(),0);
bloomTL.fromTo('#intro .whisper',{autoAlpha:0,y:16},{autoAlpha:1,y:0,duration:2,ease:'sine.out'},1);
/* the roses open quickly: the whole bloom is squeezed into BLOOM_SEC, then the petals burst straight away.
   No tap needed; a tap on the roses still skips ahead. */
const BLOOM_SEC=1.5;
bloomTL.timeScale(bloomTL.duration()/BLOOM_SEC);
bloomTL.eventCallback('onComplete',()=>openGift(true));
function openGift(auto){
  if(started) return; started=true;
  // browsers only allow sound after a tap: when it opens by itself the music waits for the envelope tap
  if(!(auto===true) || SFX.ready()) BGM.startAfter(SFX.play('chime'));
  let pre=0;
  if(bloomTL.progress()<1){ pre=1; gsap.to(bloomTL,{progress:1,duration:1,ease:'sine.inOut'}); }
  const tl=gsap.timeline({delay:pre});
  tl.to('#intro',{autoAlpha:0,duration:1,ease:'sine.inOut'},0);
  let e=1.7;
  if(GL && !REDUCE){
    GL.burst();
    tl.fromTo('#heartText',{autoAlpha:0,scale:.88},{autoAlpha:1,scale:1,duration:1.8,ease:'sine.out'},4.6);
    tl.add(()=>GL.beat(),5.8).add(()=>GL.beat(),7.1);
    tl.to('#heartText',{autoAlpha:0,scale:1.12,duration:.9,ease:'sine.in'},8.4);
    tl.add(()=>GL.rush(),8.7);
    e=9.7;
  } else if(GL){
    tl.to('#gl',{autoAlpha:0,duration:1},0).add(()=>{ GL.quietExit(); gsap.set('#gl',{autoAlpha:1}); },1);
    tl.fromTo('#heartText',{autoAlpha:0},{autoAlpha:1,duration:1},1).to('#heartText',{autoAlpha:0,duration:.8},3.4);
    e=4.4;
  }
  tl.to('#introBg',{autoAlpha:0,duration:1.3,ease:'power1.inOut'},e-.6);
  tl.set('#envScene',{visibility:'visible'},e);
  tl.fromTo('#envScene .title',{autoAlpha:0,y:24},{autoAlpha:1,y:0,duration:1,ease:'power3.out'},e);
  tl.fromTo('#envScene .sub',{autoAlpha:0},{autoAlpha:1,duration:.8},e+.3);
  tl.fromTo('#env',{autoAlpha:0,y:80,rotation:-8,scale:.85},{autoAlpha:1,y:0,rotation:0,scale:1,duration:1.2,ease:'back.out(1.4)'},e+.3);
  tl.fromTo('#envScene .hint',{autoAlpha:0},{autoAlpha:1,duration:.6},e+1.2);
  if(GL) tl.add(()=>GL.setAmbient(MOBILE?28:45),e+1);
}
$('#intro').addEventListener('click',openGift);
$('#intro').addEventListener('keydown',e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); openGift(); } });

$('#env').addEventListener('click',()=>{
  if(opened) return; opened=true;
  const envSec=SFX.play('envelope');
  if(!BGM.started) BGM.startAfter(envSec);   // first tap of the visit when the roses opened by themselves
  const tl=gsap.timeline();
  tl.to('#envScene .hint',{autoAlpha:0,duration:.3},0)
    .to('.rb .seal',{scale:1.3,autoAlpha:0,duration:.35,ease:'power2.in'},0)
    .to('.rb .flapw',{rotationX:180,duration:.8,ease:'power2.inOut'},.2)
    .set('.rb .flapw',{zIndex:1},.6)
    .to('.rb .env-letter',{yPercent:-62,duration:1,ease:'power3.out'},.95)
    .to('#env',{y:'+=40',duration:1,ease:'power2.inOut'},.95)
    .to('#envScene',{autoAlpha:0,duration:.8,ease:'power1.in'},2.05)
    .add(showBook,2.6);
});

function showBook(){
  $('#envScene').style.display='none';
  $('#book').classList.add('show'); document.body.classList.remove('rb-locked'); document.body.classList.add('rb-reading'); scrollTo(0,0);
  /* the poster lands, the name is "written" across it, then the flowers drop around it */
  const p=gsap.timeline();
  p.fromTo('#poster',{autoAlpha:0,y:90,rotation:-6,scale:.92},{autoAlpha:1,y:0,rotation:0,scale:1,duration:1.3,ease:'power3.out'},0)
   .fromTo('.p-photo img',{scale:1.25},{scale:1,duration:2.4,ease:'power2.out'},.1)
   .fromTo('.p-name',{clipPath:'inset(-30% 100% -30% -10%)'},{clipPath:'inset(-30% -10% -30% -10%)',duration:1.8,ease:'power2.inOut'},.7)
   .from('.p-date span',{autoAlpha:0,x:20,stagger:.12,duration:.7,ease:'power3.out'},1.1)
   .from('.p-info span, .p-foot span, .p-top > *',{autoAlpha:0,y:8,stagger:.04,duration:.5},1.2)
   .from('.poster-wrap > .fl',{scale:0,rotation:'-=120',autoAlpha:0,transformOrigin:'50% 50%',stagger:.1,duration:1,ease:'back.out(1.6)'},1)
   .from('.scroll-cue',{autoAlpha:0,duration:.8},2.2)
   .from('#hhCount',{autoAlpha:0,x:-20,duration:.6,ease:'back.out(2)'},2.2)
   .add(()=>HEARTS.tip(),2.6);
  initBook();
}

/* the letter section: "Dear" slides in, polaroid and bow drop, then the words write themselves in */
function letterIn(){
  $$('#letterBody p').forEach(p=>{ p.innerHTML=p.textContent.split(' ').map(w=>`<span class="w">${esc(w)}</span>`).join(' '); });
  const words=$$('#letter .w'), per=Math.min(.035,2.6/Math.max(1,words.length));
  const tl=gsap.timeline({scrollTrigger:{trigger:'.letter-sec',start:'top 62%'}});
  tl.from('.dear',{autoAlpha:0,x:-50,duration:1.2,ease:'power3.out'},0)
    .from('#letter',{autoAlpha:0,y:70,rotation:6,duration:1.1,ease:'power3.out'},.15)
    .from('#lpol',{autoAlpha:0,y:-120,rotation:-24,duration:1.1,ease:'back.out(1.3)'},.4)
    .from('#lpol .bow',{scale:0,rotation:-40,transformOrigin:'60% 20%',duration:.8,ease:'back.out(2)'},1.1)
    .from('.letter-wrap > .fl',{scale:0,rotation:'-=90',autoAlpha:0,stagger:.15,duration:.9,ease:'back.out(1.7)'},.9)
    .from('#letterTo',{autoAlpha:0,duration:.6},1)
    .from(words,{autoAlpha:0,y:6,duration:.4,stagger:per,ease:'power2.out'},1.2)
    .from('#letter .sign',{autoAlpha:0,duration:.9},1.2+words.length*per)
    .from('.pill',{scale:0,rotation:-40,duration:.6,ease:'back.out(2.4)'},1.4+words.length*per)
    .fromTo('.gname',{clipPath:'inset(-30% 100% -30% -10%)'},{clipPath:'inset(-30% -5% -30% -10%)',duration:1.6,ease:'power2.inOut'},1.5+words.length*per);
}

/* =========================================================
   book
   ========================================================= */
function initBook(){
  gsap.registerPlugin(ScrollTrigger);
  document.fonts.load('30px "Pinyon Script"').finally(()=>{ $$('.stamp').forEach(setupScratch); ScrollTrigger.refresh(); });
  setupMap(); setupDeck(); setupMusic(); setupFinale(); setupSound(); setupHearts(); setupPreview();
  if(!REDUCE) letterIn();

  $$('.rv').forEach(el=>gsap.from(el,{autoAlpha:0,y:26,duration:1,ease:'power3.out',scrollTrigger:{trigger:el,start:'top 88%'}}));
  gsap.from('.stamp',{scrollTrigger:{trigger:'#stamps',start:'top 82%'},y:-140,rotation:()=>gsap.utils.random(-28,28),autoAlpha:0,stagger:.14,duration:1,ease:'back.out(1.5)'});

  const track=$('#track');
  gsap.to(track,{x:()=>-(track.scrollWidth-innerWidth),ease:'none',
    scrollTrigger:{trigger:'#strip',pin:true,scrub:.8,anticipatePin:1,invalidateOnRefresh:true,end:()=>'+='+(track.scrollWidth-innerWidth)}});
  gsap.from('.pol',{scrollTrigger:{trigger:'#strip',start:'top 70%'},y:80,rotation:()=>gsap.utils.random(-12,12),autoAlpha:0,stagger:.1,duration:1,ease:'power3.out'});

  gsap.from('#cassette',{scrollTrigger:{trigger:'#cassette',start:'top 85%'},y:90,scale:.9,autoAlpha:0,duration:1.1,ease:'power3.out'});
  gsap.from('.rcard',{scrollTrigger:{trigger:'#deck',start:'top 85%'},y:60,rotation:()=>gsap.utils.random(-15,15),autoAlpha:0,stagger:.12,duration:.9,ease:'back.out(1.4)'});
  if($('#map')){
    gsap.from('#map',{scrollTrigger:{trigger:'#map',start:'top 85%'},scale:.9,rotation:-2,autoAlpha:0,duration:1,ease:'power3.out'});
    gsap.from('.pin',{scrollTrigger:{trigger:'#map',start:'top 65%'},scale:0,stagger:.15,duration:.6,ease:'back.out(2.5)',delay:.3});
  }
}

function setupScratch(stamp){
  // layout size, not getBoundingClientRect(): the stamps are still tilted by their drop-in tween here
  const cv=stamp.querySelector('canvas');
  cv.width=Math.round(cv.offsetWidth*DPR); cv.height=Math.round(cv.offsetHeight*DPR);
  const x=cv.getContext('2d',{willReadFrequently:true}), W=cv.width, H=cv.height;
  const g=x.createLinearGradient(0,0,W,H);
  ['#d9c7c0','#fbf3ef','#e8cfd2','#ffffff','#d4c2b8','#f6e7e4'].forEach((c,i,a)=>g.addColorStop(i/(a.length-1),c));
  x.fillStyle=g; x.fillRect(0,0,W,H);
  x.globalAlpha=.6; x.fillStyle=x.createPattern(noiseC,'repeat'); x.fillRect(0,0,W,H); x.globalAlpha=1;
  for(let i=0;i<160;i++){ x.fillStyle=`rgba(255,255,255,${Math.random()*.9})`; x.beginPath(); x.arc(Math.random()*W,Math.random()*H,Math.random()*1.7*DPR,0,7); x.fill(); }
  x.fillStyle='rgba(31,30,27,.62)'; x.textAlign='center'; x.textBaseline='middle';
  x.font=`${Math.round(W*.16)}px "Pinyon Script", cursive`; x.fillText('scratch me',W/2,H/2);
  x.font=`${Math.round(W*.12)}px serif`; x.fillStyle=hexA(CONFIG.accent,.75); x.fillText('♥',W/2,H/2+W*.2);
  x.lineCap='round'; x.lineJoin='round'; x.lineWidth=W*.17; x.globalCompositeOperation='destination-out';
  let down=false,last=null,moves=0,done=false;
  const pos=e=>{ const r=cv.getBoundingClientRect(); return [(e.clientX-r.left)*W/r.width,(e.clientY-r.top)*H/r.height]; };
  const line=(a,c)=>{ x.beginPath(); x.moveTo(a[0],a[1]); x.lineTo(c[0]+.01,c[1]); x.stroke(); };
  const snd=SFX.scratcher();
  const reveal=()=>{ if(done) return; done=true; snd.stop(); SFX.play('sparkle',1.3); stamp.classList.add('revealed');
    gsap.fromTo(stamp.querySelector('img'),{scale:1.15},{scale:1,duration:1.2,ease:'power3.out'}); };
  const check=()=>{ if(done) return; const d=x.getImageData(0,0,W,H).data; let cl=0,t=0; for(let i=3;i<d.length;i+=4*29){ t++; if(d[i]<40) cl++; } if(cl/t>.45) reveal(); };
  cv.addEventListener('pointerdown',e=>{ down=true; cv.setPointerCapture(e.pointerId); last=pos(e); line(last,last); if(!done) snd.start(); });
  cv.addEventListener('pointermove',e=>{ if(!down) return; const p=pos(e); line(last,p); last=p; if(!done) snd.move(); if(++moves%10===0) check(); });
  const up=()=>{ if(down){ down=false; snd.stop(); check(); } }; cv.addEventListener('pointerup',up); cv.addEventListener('pointercancel',up);
  stamp.addEventListener('keydown',e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); reveal(); } });
}

function setupDeck(){
  const R=CONFIG.reasons; let i=0, busy=false; const front=$('#rFront'), next=$('#rNext');
  const fill=(el,k)=>{ el.querySelector('.n').textContent=`Reason ${k%R.length+1} of ${R.length}`; el.querySelector('.t').textContent=R[k%R.length]; };
  fill(front,0); fill(next,1);
  const go=()=>{ if(busy) return;
    SFX.play('card',.94+Math.random()*.12);
    if(!HAS_GSAP){ i++; fill(front,i); fill(next,i+1); return; }
    busy=true;
    gsap.to(front,{x:'115%',y:'-12%',rotation:22,autoAlpha:0,duration:.55,ease:'power2.in',onComplete:()=>{
      i++; fill(front,i); fill(next,i+1); gsap.set(front,{x:0,y:0,rotation:0,autoAlpha:1}); busy=false; }}); };
  const deck=$('#deck'); deck.addEventListener('click',go);
  deck.addEventListener('keydown',e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); go(); } });
}

function drawMap(){
  const cv=$('#mapCanvas'), map=$('#map'), MH=Math.round(800*map.offsetHeight/Math.max(1,map.offsetWidth)), ys=MH/600;
  cv.width=800; cv.height=MH; const x=cv.getContext('2d'), r=rng(7); x.scale(1,ys);
  x.fillStyle='#f1ebdd'; x.fillRect(0,0,800,600);
  let g=x.createRadialGradient(400,300,100,400,300,520); g.addColorStop(0,'rgba(255,253,248,.55)'); g.addColorStop(1,'rgba(200,185,150,.4)'); x.fillStyle=g; x.fillRect(0,0,800,600);
  for(const [bx,by,br] of [[170,370,165],[380,180,150],[590,400,185],[700,150,105]]){
    for(let k=0;k<70;k++){ const a=r()*6.283, d=br*Math.sqrt(r())*.85, px=bx+Math.cos(a)*d, py=by+Math.sin(a)*d, rr=br*(.18+r()*.3);
      g=x.createRadialGradient(px,py,0,px,py,rr); g.addColorStop(0,'rgba(248,236,218,.45)'); g.addColorStop(1,'rgba(248,236,218,0)'); x.fillStyle=g; x.beginPath(); x.arc(px,py,rr,0,7); x.fill(); }
    for(let k=0;k<26;k++){ const a=r()*6.283, d=br*Math.sqrt(r())*.6, px=bx+Math.cos(a)*d, py=by+Math.sin(a)*d, rr=br*(.12+r()*.2);
      g=x.createRadialGradient(px,py,0,px,py,rr); g.addColorStop(0,'rgba(178,205,160,.3)'); g.addColorStop(1,'rgba(178,205,160,0)'); x.fillStyle=g; x.beginPath(); x.arc(px,py,rr,0,7); x.fill(); }
    for(let ring=0;ring<3;ring++){ x.strokeStyle=`rgba(95,80,50,${.2-ring*.05})`; x.lineWidth=1.4; x.beginPath();
      const N=28, base=br*(.55+ring*.22), ph=r()*6;
      for(let j=0;j<=N;j++){ const a=j/N*6.283, rr=base*(1+.12*Math.sin(a*3+ph)+.06*Math.sin(a*7+ph*2)); const px=bx+Math.cos(a)*rr, py=by+Math.sin(a)*rr*.8; j?x.lineTo(px,py):x.moveTo(px,py); }
      x.stroke(); }
  }
  x.strokeStyle='rgba(31,30,27,.45)'; x.lineWidth=1.5; x.fillStyle='rgba(31,30,27,.55)';
  const cx=730,cy=530; x.beginPath(); x.arc(cx,cy,26,0,7); x.stroke();
  x.beginPath(); x.moveTo(cx,cy-40); x.lineTo(cx+7,cy); x.lineTo(cx,cy+40); x.lineTo(cx-7,cy); x.closePath(); x.fill();
  x.font='italic 16px "Cormorant Garamond", serif'; x.textAlign='center'; x.fillText('N',cx,cy-48);
  x.font='italic 24px "Cormorant Garamond", serif'; x.fillStyle='rgba(31,30,27,.35)'; x.fillText('here be us',110,560);
  x.setTransform(1,0,0,1,0,0); x.globalAlpha=.8; x.fillStyle=x.createPattern(noiseC,'repeat'); x.fillRect(0,0,800,MH); x.globalAlpha=1;
}

function setupMap(){
  const P=CONFIG.places; if(!P.length || !$('#map')) return;
  drawMap();
  let lastW=innerWidth, t=0; addEventListener('resize',()=>{ clearTimeout(t); t=setTimeout(()=>{ if(innerWidth!==lastW){ lastW=innerWidth; drawMap(); } },200); });
  let d=`M${P[0].x} ${P[0].y}`;
  for(let i=1;i<P.length;i++){ const a=P[i-1], b=P[i]; d+=` Q${(a.x+b.x)/2} ${Math.min(a.y,b.y)-14} ${b.x} ${b.y}`; }
  $('#route').setAttribute('d',d);
  const map=$('#map'), pola=$('#pola');
  const showPola=()=>{ if(HAS_GSAP) gsap.fromTo(pola,{xPercent:-50,yPercent:-50,scale:.5,rotation:-12,autoAlpha:0},{scale:1,rotation:-2.5,autoAlpha:1,duration:.6,ease:'back.out(1.8)'});
    else Object.assign(pola.style,{opacity:1,visibility:'visible',transform:'translate(-50%,-50%) rotate(-2.5deg)'}); };
  const hidePola=()=>{ if(HAS_GSAP) gsap.to(pola,{autoAlpha:0,scale:.8,duration:.25}); else Object.assign(pola.style,{opacity:0,visibility:'hidden'}); };
  const photos=P.map((p,i)=>p.photo||filmPhoto(p.theme,40+i*11,420,420));
  P.forEach((p,i)=>{
    const b=document.createElement('button'); b.type='button'; b.className='pin'; b.style.left=p.x+'%'; b.style.top=p.y+'%'; b.textContent='♥'; b.setAttribute('aria-label',p.name);
    const l=document.createElement('span'); l.className='pin-l'; l.style.left=p.x+'%'; l.style.top=p.y+'%'; l.textContent=p.name;
    b.addEventListener('click',e=>{ e.stopPropagation(); b.classList.add('seen'); SFX.play('pop',.95+Math.random()*.1);
      pola.querySelector('img').src=photos[i]; pola.querySelector('img').dataset.cap=p.name; pola.querySelector('h3').textContent=p.name; pola.querySelector('.d').textContent=p.date; pola.querySelector('.nt').textContent=p.note;
      showPola(); });
    map.append(b,l);
  });
  map.addEventListener('click',hidePola);
}

/* ---------- cassette: its click first, then the customer's song (file or YouTube); without one, the piano ---------- */
function setupMusic(){
  const M=CONFIG.music||{}, cas=$('#cassette'), btn=$('#playBtn');
  let playing=false, audio=null, yt=null, wait=0;

  function setUI(on){ playing=on; cas.classList.toggle('playing',on); btn.setAttribute('aria-pressed',on);
    $('#playIcon').textContent=on?'❚❚':'▶'; $('#playTxt').textContent=on?'Pause':'Play'; }
  function stop(){ clearTimeout(wait); if(audio) audio.pause(); if(yt){ yt.remove(); yt=null; } setUI(false); }

  btn.addEventListener('click',()=>{
    if(playing){ stop(); return; }
    if(!M.file && !M.youtube && !BG) return;
    BGM.stop();
    setUI(true);
    if(!M.youtube || M.file){
      audio=audio || (M.file ? Object.assign(new Audio(),{preload:'none',loop:true,src:M.file}) : BG);
      unlock(audio);   // inside the tap, so it may start after the click without another tap
    }
    const start=()=>{
      if(audio){ if(audio===BG) BG.currentTime=0; audio.play().catch(()=>setUI(false)); return; }
      yt=document.createElement('iframe'); yt.className='yt'; yt.allow='autoplay';
      yt.src=`https://www.youtube.com/embed/${encodeURIComponent(M.youtube)}?autoplay=1&loop=1&playlist=${encodeURIComponent(M.youtube)}&playsinline=1`;
      ROOT.appendChild(yt);
    };
    wait=setTimeout(start,SFX.play('cassette')*1000);
  });
}

/* ---------- mute: silences the effects and every song ---------- */
/* ---------- hidden hearts hunt: small hearts around the page; all found = the secret card ---------- */
function setupHearts(){
  const hs=$$('.hh'), total=hs.length, cnt=$('#hhCount'), toastEl=$('#hhToast'), left=$('#hhLeft');
  if(!total||!cnt){ if(cnt) cnt.style.display='none'; return; }
  $('#hhTotal').textContent=total;
  let found=0, hideT=0;
  const TIP=['Psst… a little game!',`${total} tiny hearts are hiding on this page. Find them all to unlock a secret ♡`];
  /* cute card in the middle of the screen: bouncing heart, title, message, a row of hearts that fill as they're found.
     Tap it to close; it also closes by itself. */
  const hide=()=>{ clearTimeout(hideT); if(HAS_GSAP) gsap.to(toastEl,{autoAlpha:0,scale:.85,duration:.3,ease:'power2.in'}); else Object.assign(toastEl.style,{opacity:0,visibility:'hidden'}); };
  function toast([title,msg],ms){
    $('#hhToastT').textContent=title; $('#hhToastM').textContent=msg;
    $('#hhDots').innerHTML=Array.from({length:total},(_,i)=>`<svg class="${i<found?'on':''}" aria-hidden="true"><use href="#rb-heart"/></svg>`).join('');
    clearTimeout(hideT);
    if(HAS_GSAP){ gsap.killTweensOf(toastEl); gsap.fromTo(toastEl,{autoAlpha:0,scale:.6,xPercent:-50,yPercent:-50,rotation:-4},{autoAlpha:1,scale:1,rotation:0,duration:.6,ease:'back.out(2.2)'}); }
    else Object.assign(toastEl.style,{opacity:1,visibility:'visible'});
    hideT=setTimeout(hide,ms||3600);
  }
  toastEl.addEventListener('click',hide);
  HEARTS.tip=()=>{ if(!found) toast(TIP,5200); };
  const updateLeft=()=>{ left.textContent=found===total?'You found every hidden heart ♡':`You’ve found ${found} of ${total} hidden hearts. A secret is still waiting…`; };
  updateLeft();

  /* the found heart flies into the counter, with a little burst where it was */
  const heartSvg='<svg aria-hidden="true" style="width:100%;height:100%;display:block"><use href="#rb-heart"/></svg>';
  function fly(h){
    if(!HAS_GSAP||REDUCE) return Promise.resolve();
    const a=h.getBoundingClientRect(), b=cnt.querySelector('svg').getBoundingClientRect();
    for(let i=0;i<8;i++){ const sp=document.createElement('span'); sp.className='hh-spark'; sp.innerHTML=heartSvg; ROOT.appendChild(sp);
      const ang=i/8*Math.PI*2; gsap.set(sp,{left:a.left+a.width/2-6,top:a.top+a.height/2-6});
      gsap.to(sp,{x:Math.cos(ang)*rand(30,52),y:Math.sin(ang)*rand(30,52),scale:rand(.5,1),autoAlpha:0,duration:.8,ease:'power2.out',onComplete:()=>sp.parentNode&&sp.parentNode.removeChild(sp)}); }
    const f=document.createElement('span'); f.className='hh-fly'; f.innerHTML=heartSvg; ROOT.appendChild(f);
    gsap.set(f,{left:a.left,top:a.top,width:a.width,height:a.height});
    return new Promise(done=>gsap.timeline({onComplete:()=>{ f.parentNode&&f.parentNode.removeChild(f); done(); }})
      .to(f,{scale:2,duration:.25,ease:'power2.out'})
      .to(f,{left:b.left,top:b.top,width:b.width,height:b.height,scale:1,duration:.7,ease:'power3.inOut'}));
  }
  hs.forEach(h=>{
    h.addEventListener('click',e=>{ e.stopPropagation(); if(h.classList.contains('found')) return;
      found++; SFX.play('pop',1.15); SFX.play('sparkle',1.6);
      const p=fly(h); h.classList.add('found');
      p.then(()=>{ $('#hhN').textContent=found; cnt.setAttribute('aria-label',`Hidden hearts found: ${found} of ${total}`);
        if(HAS_GSAP) gsap.fromTo(cnt,{scale:1.25},{scale:1,duration:.5,ease:'back.out(3)'});
        if(found===total){ cnt.classList.add('all'); setTimeout(openSecret,350); }
        else toast(found===1?['Yay, you found one!',`${total-1} more to go ♡`]
                 :total-found===1?['Almost there!','Just one more heart to find…']
                 :[`${found} of ${total}, so good!`,`${total-found} more hiding somewhere ♡`]);
        updateLeft(); });
    });
    h.addEventListener('keydown',e=>e.stopPropagation());       // Enter on a heart shouldn't also flip the reasons card
  });
  cnt.addEventListener('click',()=>found===total?openSecret():toast(found?[`${found} of ${total} found`,'Keep looking, they’re close ♡']:TIP));

  const secret=$('#secret');
  function openSecret(){
    SFX.play('finale');
    if(!HAS_GSAP){ Object.assign(secret.style,{opacity:1,visibility:'visible'}); return; }
    gsap.to(secret,{autoAlpha:1,duration:.6});
    gsap.fromTo('#secret .secret-card',{y:60,scale:.9,rotation:4},{y:0,scale:1,rotation:0,duration:.9,ease:'back.out(1.5)'});
    gsap.fromTo('#secret .secret-pol img',{filter:'brightness(2.2) saturate(0) blur(6px)'},{filter:'brightness(1) saturate(1) blur(0px)',duration:2.6,delay:.4,ease:'power1.out'});
    gsap.fromTo(['#secret .secret-k','#secret .secret-big','#secret .secret-small','#secretClose'],{autoAlpha:0,y:14},{autoAlpha:1,y:0,stagger:.15,delay:.8,duration:.7,ease:'power3.out'});
    if(GL){ GL.canvas.classList.add('top'); GL.shower(MOBILE?160:260); }
  }
  $('#secretClose').addEventListener('click',()=>{
    if(HAS_GSAP) gsap.to(secret,{autoAlpha:0,duration:.5}); else Object.assign(secret.style,{opacity:0,visibility:'hidden'});
    if(GL) setTimeout(()=>GL.canvas.classList.remove('top'),600);
  });
}

/* ---------- photo preview: tap any photo (img.zoom) → it flies into a big taped polaroid; swipe/arrows through
   every photo on the page, tap outside / ✕ / Esc and it flies back. Stamps only count once scratched. ---------- */
function setupPreview(){
  const lb=$('#lb'), pol=$('#lbPol'), img=$('#lbImg'), cap=$('#lbCap'), count=$('#lbCount');
  if(!lb) return;
  let list=[], at=0, src=null, open=false, busy=false;
  const usable=el=>el.getAttribute('src') && !(el.closest('.stamp') && !el.closest('.stamp.revealed')) && !(el.closest('#pola') && getComputedStyle($('#pola')).visibility==='hidden');
  const gather=()=>$$('img.zoom').filter(usable);
  function fill(el){
    img.src=el.currentSrc||el.src; cap.textContent=el.dataset.cap||'♡';
    count.textContent=`${at+1} / ${list.length}`;
    lb.classList.toggle('single',list.length<2);
  }
  function hearts(){
    if(!HAS_GSAP||REDUCE) return;
    const r=pol.getBoundingClientRect();
    for(let i=0;i<12;i++){ const h=document.createElement('span'); h.className='lb-heart';
      h.innerHTML='<svg aria-hidden="true"><use href="#rb-heart"/></svg>'; lb.appendChild(h);
      gsap.set(h,{left:r.left+rand(0,r.width),top:r.bottom-rand(10,60),scale:rand(.5,1.2),rotation:rand(-25,25)});
      gsap.to(h,{y:-rand(140,320),x:rand(-40,40),autoAlpha:0,duration:rand(1.4,2.3),delay:rand(0,.4),ease:'power1.out',onComplete:()=>h.parentNode&&h.parentNode.removeChild(h)}); }
  }
  /* FLIP: start the polaroid exactly over the tapped photo, then let it fly to the centre */
  function fromRect(el){
    const a=el.getBoundingClientRect();
    if(!a.width||a.bottom<0||a.top>innerHeight) return {x:0,y:60,scale:.6,rotation:-8,autoAlpha:0};
    const keep={x:gsap.getProperty(pol,'x'),y:gsap.getProperty(pol,'y'),scale:gsap.getProperty(pol,'scale'),rotation:gsap.getProperty(pol,'rotation')};
    gsap.set(pol,{x:0,y:0,scale:1,rotation:0});                       // measure the resting layout
    const b=img.getBoundingClientRect(), p=pol.getBoundingClientRect();
    gsap.set(pol,keep);
    const s=Math.max(.12,a.width/b.width), cx=p.left+p.width/2, cy=p.top+p.height/2;
    const ox=(b.left+b.width/2)-cx, oy=(b.top+b.height/2)-cy;         // photo centre relative to the frame centre
    return { x:(a.left+a.width/2)-cx-s*ox, y:(a.top+a.height/2)-cy-s*oy, scale:s, rotation:0 };
  }
  function show(el){
    list=gather(); at=Math.max(0,list.indexOf(el)); src=el; open=true;
    fill(el); document.documentElement.style.overflow='hidden';
    SFX.play('card',1.08);
    if(!HAS_GSAP){ Object.assign(lb.style,{opacity:1,visibility:'visible'}); return; }
    gsap.killTweensOf([lb,pol]);
    gsap.set(lb,{autoAlpha:1}); gsap.fromTo('.lb-bg',{autoAlpha:0},{autoAlpha:1,duration:.45});
    const go=()=>{ const f=fromRect(el);
      gsap.fromTo(pol,{...f,autoAlpha:1},{x:0,y:0,scale:1,rotation:-2.5,duration:REDUCE?0:.75,ease:'back.out(1.25)',onComplete:hearts});
      gsap.fromTo(['.lb-x','.lb-bar'],{autoAlpha:0,y:16},{autoAlpha:1,y:0,stagger:.08,delay:.35,duration:.45,ease:'back.out(2)'}); };
    if(img.complete) go(); else { gsap.set(pol,{autoAlpha:0}); img.onload=()=>{ img.onload=null; go(); }; }
  }
  function hide(){
    if(!open) return; open=false; document.documentElement.style.overflow='';
    if(!HAS_GSAP){ Object.assign(lb.style,{opacity:0,visibility:'hidden'}); return; }
    const el=list[at]||src, f=fromRect(el);
    gsap.to(['.lb-x','.lb-bar'],{autoAlpha:0,duration:.2});
    gsap.to(pol,{...f,duration:REDUCE?0:.55,ease:'power3.inOut'});
    gsap.to('.lb-bg',{autoAlpha:0,duration:.45,delay:.15,onComplete:()=>gsap.set(lb,{autoAlpha:0})});
  }
  function step(d){
    if(busy||list.length<2) return; busy=true;
    at=(at+d+list.length)%list.length; SFX.play('card',.95+Math.random()*.1);
    if(!HAS_GSAP||REDUCE){ fill(list[at]); busy=false; return; }
    gsap.to(pol,{x:-d*innerWidth*.7,rotation:-d*18,autoAlpha:0,duration:.32,ease:'power2.in',onComplete:()=>{
      fill(list[at]);
      gsap.fromTo(pol,{x:d*innerWidth*.6,rotation:d*14,autoAlpha:0},{x:0,rotation:d>0?2.5:-2.5,autoAlpha:1,duration:.5,ease:'back.out(1.4)',onComplete:()=>{ busy=false; hearts(); }}); }});
  }
  /* capture phase: a photo tap opens the preview instead of e.g. closing the map memory */
  document.addEventListener('click',e=>{ const el=e.target.closest && e.target.closest('img.zoom');
    if(!el||open||!ROOT.contains(el)||!usable(el)) return; e.stopPropagation(); e.preventDefault(); show(el); },true);
  $('#lbX').addEventListener('click',hide);
  $('.lb-bg').addEventListener('click',hide);
  $('#lbPrev').addEventListener('click',()=>step(-1));
  $('#lbNext').addEventListener('click',()=>step(1));
  addEventListener('keydown',e=>{ if(!open) return; if(e.key==='Escape') hide(); else if(e.key==='ArrowRight') step(1); else if(e.key==='ArrowLeft') step(-1); });
  let sx=null; pol.addEventListener('pointerdown',e=>{ sx=e.clientX; });
  pol.addEventListener('pointerup',e=>{ if(sx===null) return; const dx=e.clientX-sx; sx=null; if(Math.abs(dx)>45) step(dx<0?1:-1); });
}

function setupSound(){
  const b=$('#soundBtn'); if(!b) return;
  b.addEventListener('click',()=>{
    const m=!SFX.muted; SFX.setMuted(m); BGM.mute(m);
    b.setAttribute('aria-pressed',m); b.setAttribute('aria-label',m?'Turn sound on':'Mute sound');
    const play=$('#playBtn'); if(m && play.getAttribute('aria-pressed')==='true') play.click();
  });
}

/* ---------- finale ---------- */
function setupFinale(){
  const fin=$('#finale');
  $$('[data-yes]').forEach(b=>b.addEventListener('click',()=>{
    SFX.play('finale');
    if(!HAS_GSAP){ Object.assign(fin.style,{opacity:1,visibility:'visible'}); return; }
    gsap.to(fin,{autoAlpha:1,duration:.9});
    if($('#finaleRose')) gsap.fromTo('#finaleRose',{scale:.3,rotation:-40,autoAlpha:0},{scale:1,rotation:0,autoAlpha:1,duration:1.4,delay:.3,ease:'elastic.out(1,.6)'});
    gsap.fromTo(['#finaleBig','#finaleSmall','#again'],{y:20,autoAlpha:0},{y:0,autoAlpha:1,stagger:.18,delay:.7,duration:.9,ease:'power3.out'});
    if(GL){ GL.canvas.classList.add('top'); GL.shower(MOBILE?260:420); }
  }));
  $('#again').addEventListener('click',()=>{
    if(HAS_GSAP) gsap.to(fin,{autoAlpha:0,duration:.6}); else Object.assign(fin.style,{opacity:0,visibility:'hidden'});
    if(GL) GL.canvas.classList.remove('top');
    scrollTo({top:0,behavior:REDUCE?'auto':'smooth'});
  });
  $('#replay').addEventListener('click',()=>location.reload());
}
})();

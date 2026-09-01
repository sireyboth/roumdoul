import * as THREE from 'three'

// Ambient 3D hero backdrop: sakura petals fall straight down through the
// banner while a few butterflies wander it with real flight-like movement —
// a slow horizontal cruise plus a wing-beat bob, not a fall. Both shapes are
// drawn at runtime onto a <canvas> and used as sprite textures — no image
// assets to ship. Purely decorative: pointer-events stay off, and it
// degrades to a single static frame under prefers-reduced-motion.
//
// Follows the project's Three.js guidelines: one renderer for the page's
// lifetime (torn down and rebuilt on re-init rather than stacked), shared
// BufferGeometry/Points per shape, alpha canvas composited over the existing
// CSS/image background, ResizeObserver on the container instead of the
// window resize event, disposal of every geometry/material/texture on
// teardown, and real (clamped) delta-time so speed reads the same at 60fps
// and 120fps instead of racing on high-refresh displays.

// --- Tuning -----------------------------------------------------------
// Every value below is deliberately mid-range ("medium" pace) — nudge a
// range up/down to taste after seeing it move; nothing else needs to change.
const SAKURA_COUNT = 70
const BUTTERFLY_COUNT = 8
const FALL_BOUND = 4.5 // vertical half-extent sakura fall through
const DRIFT_BOUND = 7 // horizontal half-extent butterflies cruise within

const SAKURA_FALL_SPEED = [0.55, 0.95] // units/sec, straight down
const SAKURA_SWAY_AMOUNT = [0.35, 0.9]
const SAKURA_SWAY_FREQ = [0.25, 0.55] // Hz-ish

const BUTTERFLY_CRUISE_SPEED = [0.45, 0.75] // units/sec horizontal
const BUTTERFLY_BOB_AMOUNT = [0.5, 1.0]
const BUTTERFLY_BOB_FREQ = [0.7, 1.3] // faster than sway = reads as wingbeat/flutter
// ------------------------------------------------------------------------

const MAX_DELTA = 0.05 // clamp huge frame gaps (tab was backgrounded, etc.)

let renderer = null
let scene = null
let camera = null
let sakura = null
let butterflies = null
let sakuraTexture = null
let butterflyTexture = null
let resizeObserver = null
let pointerHandler = null
let motionQuery = null
let motionChangeHandler = null
let rafId = null
let container = null
let elapsed = 0
let lastFrameTime = 0

const pointer = { x: 0, y: 0 }
const pointerTarget = { x: 0, y: 0 }

function rand(min, max) {
  return min + Math.random() * (max - min)
}

function prefersReducedMotion() {
  return Boolean(motionQuery && motionQuery.matches)
}

function createSakuraTexture() {
  const size = 64
  const canvas = document.createElement('canvas')
  canvas.width = size
  canvas.height = size
  const ctx = canvas.getContext('2d')
  const cx = size / 2
  const cy = size / 2
  const petalCount = 5
  const petalLength = size * 0.34
  const petalWidth = size * 0.22

  for (let i = 0; i < petalCount; i++) {
    ctx.save()
    ctx.translate(cx, cy)
    ctx.rotate((i / petalCount) * Math.PI * 2)
    const grad = ctx.createRadialGradient(0, -petalLength * 0.5, 2, 0, -petalLength * 0.5, petalLength)
    grad.addColorStop(0, 'rgba(255,255,255,0.95)')
    grad.addColorStop(0.55, 'rgba(240,180,205,0.9)')
    grad.addColorStop(1, 'rgba(224,112,159,0)')
    ctx.fillStyle = grad
    ctx.beginPath()
    ctx.ellipse(0, -petalLength * 0.55, petalWidth / 2, petalLength / 2, 0, 0, Math.PI * 2)
    ctx.fill()
    ctx.restore()
  }

  const centerGrad = ctx.createRadialGradient(cx, cy, 0, cx, cy, size * 0.09)
  centerGrad.addColorStop(0, 'rgba(217,182,106,0.95)')
  centerGrad.addColorStop(1, 'rgba(217,182,106,0)')
  ctx.fillStyle = centerGrad
  ctx.beginPath()
  ctx.arc(cx, cy, size * 0.09, 0, Math.PI * 2)
  ctx.fill()

  const texture = new THREE.CanvasTexture(canvas)
  texture.needsUpdate = true
  return texture
}

function createButterflyTexture() {
  const size = 64
  const canvas = document.createElement('canvas')
  canvas.width = size
  canvas.height = size
  const ctx = canvas.getContext('2d')

  ctx.save()
  ctx.translate(size / 2, size / 2)

  const wingGrad = ctx.createLinearGradient(-size * 0.4, 0, size * 0.4, 0)
  wingGrad.addColorStop(0, 'rgba(224,112,159,0.92)')
  wingGrad.addColorStop(1, 'rgba(217,182,106,0.92)')
  ctx.fillStyle = wingGrad

  const wings = [
    [-size * 0.17, -size * 0.12, size * 0.17, size * 0.13, -0.3],
    [-size * 0.14, size * 0.1, size * 0.13, size * 0.1, 0.3],
    [size * 0.17, -size * 0.12, size * 0.17, size * 0.13, 0.3],
    [size * 0.14, size * 0.1, size * 0.13, size * 0.1, -0.3],
  ]
  wings.forEach(([x, y, rx, ry, rot]) => {
    ctx.beginPath()
    ctx.ellipse(x, y, rx, ry, rot, 0, Math.PI * 2)
    ctx.fill()
  })

  ctx.fillStyle = 'rgba(53,10,30,0.85)'
  ctx.beginPath()
  ctx.ellipse(0, 0, size * 0.02, size * 0.16, 0, 0, Math.PI * 2)
  ctx.fill()

  ctx.restore()

  const texture = new THREE.CanvasTexture(canvas)
  texture.needsUpdate = true
  return texture
}

function buildSakura(texture) {
  const geometry = new THREE.BufferGeometry()
  const positions = new Float32Array(SAKURA_COUNT * 3)
  const baseX = new Float32Array(SAKURA_COUNT)
  const fallSpeed = new Float32Array(SAKURA_COUNT)
  const swayFreq = new Float32Array(SAKURA_COUNT)
  const swayAmp = new Float32Array(SAKURA_COUNT)
  const swayPhase = new Float32Array(SAKURA_COUNT)

  for (let i = 0; i < SAKURA_COUNT; i++) {
    const i3 = i * 3
    const x = rand(-7.5, 7.5)
    positions[i3] = x
    positions[i3 + 1] = rand(-FALL_BOUND, FALL_BOUND)
    positions[i3 + 2] = rand(-3.5, 3.5)

    baseX[i] = x
    fallSpeed[i] = rand(...SAKURA_FALL_SPEED)
    swayFreq[i] = rand(...SAKURA_SWAY_FREQ)
    swayAmp[i] = rand(...SAKURA_SWAY_AMOUNT)
    swayPhase[i] = rand(0, Math.PI * 2)
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3))
  geometry.userData = { baseX, fallSpeed, swayFreq, swayAmp, swayPhase }

  const material = new THREE.PointsMaterial({
    size: 0.55,
    map: texture,
    transparent: true,
    alphaTest: 0.05,
    depthWrite: false,
    sizeAttenuation: true,
  })

  return new THREE.Points(geometry, material)
}

function buildButterflies(texture) {
  const geometry = new THREE.BufferGeometry()
  const positions = new Float32Array(BUTTERFLY_COUNT * 3)
  const baseX = new Float32Array(BUTTERFLY_COUNT)
  const baseY = new Float32Array(BUTTERFLY_COUNT)
  const cruiseSpeed = new Float32Array(BUTTERFLY_COUNT)
  const bobFreq = new Float32Array(BUTTERFLY_COUNT)
  const bobFreq2 = new Float32Array(BUTTERFLY_COUNT)
  const bobAmp = new Float32Array(BUTTERFLY_COUNT)
  const bobPhase = new Float32Array(BUTTERFLY_COUNT)

  for (let i = 0; i < BUTTERFLY_COUNT; i++) {
    const i3 = i * 3
    const x = rand(-DRIFT_BOUND, DRIFT_BOUND)
    const y = rand(-2.5, 3)
    positions[i3] = x
    positions[i3 + 1] = y
    positions[i3 + 2] = rand(-3, 3)

    baseX[i] = x
    baseY[i] = y
    // Roughly half cruise left, half cruise right, so they cross paths
    // instead of all marching the same direction.
    cruiseSpeed[i] = rand(...BUTTERFLY_CRUISE_SPEED) * (i % 2 === 0 ? 1 : -1)
    bobFreq[i] = rand(...BUTTERFLY_BOB_FREQ)
    bobFreq2[i] = bobFreq[i] * 0.37
    bobAmp[i] = rand(...BUTTERFLY_BOB_AMOUNT)
    bobPhase[i] = rand(0, Math.PI * 2)
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3))
  geometry.userData = { baseX, baseY, cruiseSpeed, bobFreq, bobFreq2, bobAmp, bobPhase }

  const material = new THREE.PointsMaterial({
    size: 0.7,
    map: texture,
    transparent: true,
    alphaTest: 0.05,
    depthWrite: false,
    sizeAttenuation: true,
  })

  return new THREE.Points(geometry, material)
}

function disposeScene() {
  if (rafId !== null) {
    cancelAnimationFrame(rafId)
    rafId = null
  }
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  if (container && pointerHandler) {
    container.removeEventListener('pointermove', pointerHandler)
    pointerHandler = null
  }
  if (motionQuery && motionChangeHandler) {
    motionQuery.removeEventListener('change', motionChangeHandler)
    motionChangeHandler = null
  }
  ;[sakura, butterflies].forEach((system) => {
    if (!system) return
    system.geometry.dispose()
    system.material.dispose()
  })
  sakura = null
  butterflies = null
  if (sakuraTexture) {
    sakuraTexture.dispose()
    sakuraTexture = null
  }
  if (butterflyTexture) {
    butterflyTexture.dispose()
    butterflyTexture = null
  }
  if (renderer) {
    renderer.dispose()
    renderer.domElement.remove()
    renderer = null
  }
  scene = null
  camera = null
  container = null
  elapsed = 0
  lastFrameTime = 0
}

function resize() {
  if (!renderer || !camera || !container) return
  const width = container.clientWidth
  const height = container.clientHeight
  if (width === 0 || height === 0) return
  camera.aspect = width / height
  camera.updateProjectionMatrix()
  renderer.setSize(width, height, false)
}

function renderStaticFrame() {
  if (!renderer || !scene || !camera) return
  renderer.render(scene, camera)
}

// Sakura: straight fall + gentle sway, wrapping back to the top.
function stepSakura(dt) {
  const { baseX, fallSpeed, swayFreq, swayAmp, swayPhase } = sakura.geometry.userData
  const positions = sakura.geometry.attributes.position

  for (let i = 0; i < SAKURA_COUNT; i++) {
    const i3 = i * 3
    let y = positions.array[i3 + 1] - fallSpeed[i] * dt
    if (y < -FALL_BOUND) {
      y = FALL_BOUND
      baseX[i] = rand(-7.5, 7.5)
      positions.array[i3 + 2] = rand(-3.5, 3.5)
    }
    positions.array[i3 + 1] = y
    positions.array[i3] = baseX[i] + Math.sin(elapsed * swayFreq[i] + swayPhase[i]) * swayAmp[i]
  }
  positions.needsUpdate = true
}

// Butterflies: slow horizontal cruise (wrapping left<->right) plus a
// two-frequency vertical bob — reads as wandering flight, never falls.
function stepButterflies(dt) {
  const { baseX, baseY, cruiseSpeed, bobFreq, bobFreq2, bobAmp, bobPhase } = butterflies.geometry.userData
  const positions = butterflies.geometry.attributes.position

  for (let i = 0; i < BUTTERFLY_COUNT; i++) {
    const i3 = i * 3
    let x = baseX[i] + cruiseSpeed[i] * dt
    if (x > DRIFT_BOUND) x = -DRIFT_BOUND
    if (x < -DRIFT_BOUND) x = DRIFT_BOUND
    baseX[i] = x

    const bob =
      Math.sin(elapsed * bobFreq[i] + bobPhase[i]) * bobAmp[i] +
      Math.sin(elapsed * bobFreq2[i] + bobPhase[i] * 1.7) * bobAmp[i] * 0.4

    positions.array[i3] = x
    positions.array[i3 + 1] = baseY[i] + bob
  }
  positions.needsUpdate = true
}

function animate(now) {
  if (!renderer || !scene || !camera) return

  if (prefersReducedMotion()) {
    rafId = null
    renderStaticFrame()
    return
  }

  const dt = lastFrameTime ? Math.min((now - lastFrameTime) / 1000, MAX_DELTA) : 1 / 60
  lastFrameTime = now
  elapsed += dt

  pointer.x += (pointerTarget.x - pointer.x) * 0.04
  pointer.y += (pointerTarget.y - pointer.y) * 0.04
  camera.position.x = pointer.x * 0.6
  camera.position.y = pointer.y * 0.35
  camera.lookAt(0, 0, 0)

  if (sakura) stepSakura(dt)
  if (butterflies) stepButterflies(dt)

  renderer.render(scene, camera)
  rafId = requestAnimationFrame(animate)
}

function startLoop() {
  if (rafId !== null) return
  if (prefersReducedMotion()) {
    renderStaticFrame()
    return
  }
  lastFrameTime = 0
  rafId = requestAnimationFrame(animate)
}

export function initHeroParticles() {
  // Re-entrant: livewire:navigated re-runs this on every SPA navigation, so
  // always tear down whatever instance exists before (maybe) building a new
  // one — never stack multiple WebGLRenderers on the same page.
  disposeScene()

  const section = document.getElementById('hero-slider')
  const mount = document.getElementById('hero-particles')
  if (!section || !mount) return

  if (!('WebGLRenderingContext' in window)) return

  container = mount

  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')

  scene = new THREE.Scene()
  camera = new THREE.PerspectiveCamera(55, 1, 0.1, 30)
  camera.position.z = 6

  renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2))
  renderer.domElement.className = 'block h-full w-full'
  container.appendChild(renderer.domElement)

  sakuraTexture = createSakuraTexture()
  butterflyTexture = createButterflyTexture()

  sakura = buildSakura(sakuraTexture)
  butterflies = buildButterflies(butterflyTexture)
  scene.add(sakura, butterflies)

  resize()
  resizeObserver = new ResizeObserver(resize)
  resizeObserver.observe(container)

  pointerHandler = (event) => {
    const rect = section.getBoundingClientRect()
    pointerTarget.x = ((event.clientX - rect.left) / rect.width - 0.5) * 2
    pointerTarget.y = -((event.clientY - rect.top) / rect.height - 0.5) * 2
  }
  section.addEventListener('pointermove', pointerHandler)

  motionChangeHandler = () => {
    if (prefersReducedMotion()) {
      if (rafId !== null) {
        cancelAnimationFrame(rafId)
        rafId = null
      }
      renderStaticFrame()
    } else {
      startLoop()
    }
  }
  motionQuery.addEventListener('change', motionChangeHandler)

  startLoop()
}

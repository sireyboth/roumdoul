// Lightweight 3D pointer-tilt for cards: rotates [data-tilt] elements toward
// the cursor with a real CSS perspective transform, then eases back to flat
// on pointerleave. No dependency — a handful of lines per the interaction
// guideline in the UI/UX Pro Max motion data. Skipped entirely under
// prefers-reduced-motion, and safely ignores touch (no pointermove trail).

const MAX_TILT_DEG = 7
const LIFT_SCALE = 1.02

function prefersReducedMotion() {
  return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function bind(el) {
  el.setAttribute('data-tilt-bound', '')

  el.addEventListener('pointerenter', (event) => {
    if (event.pointerType !== 'mouse') return
    el.style.transition = 'none'
  })

  el.addEventListener('pointermove', (event) => {
    if (event.pointerType !== 'mouse') return
    const rect = el.getBoundingClientRect()
    const px = (event.clientX - rect.left) / rect.width - 0.5
    const py = (event.clientY - rect.top) / rect.height - 0.5
    const rotateY = px * MAX_TILT_DEG * 2
    const rotateX = -py * MAX_TILT_DEG * 2
    el.style.transform =
      `perspective(700px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(${LIFT_SCALE}, ${LIFT_SCALE}, ${LIFT_SCALE})`
  })

  el.addEventListener('pointerleave', (event) => {
    if (event.pointerType !== 'mouse') return
    el.style.transition = 'transform 0.5s cubic-bezier(0.22, 1, 0.36, 1)'
    el.style.transform = 'perspective(700px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)'
  })
}

export function initTilt() {
  if (prefersReducedMotion()) return

  const targets = document.querySelectorAll('[data-tilt]:not([data-tilt-bound])')
  targets.forEach(bind)
}

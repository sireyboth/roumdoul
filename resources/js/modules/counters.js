let observer = null

const prefersReducedMotion = () =>
  window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

function animateCount(el) {
  const target = Number(el.dataset.counterTarget)
  const suffix = el.dataset.counterSuffix || ''
  if (!Number.isFinite(target)) return

  if (prefersReducedMotion()) {
    el.textContent = target.toLocaleString('en-US') + suffix
    return
  }

  const duration = 1200
  const start = performance.now()

  function tick(now) {
    const progress = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3)
    el.textContent = Math.round(target * eased).toLocaleString('en-US') + suffix
    if (progress < 1) requestAnimationFrame(tick)
  }

  requestAnimationFrame(tick)
}

// Animates [data-counter] elements (with a numeric data-counter-target) from 0
// up to their target once they scroll into view. Mirrors scrollReveal.js's
// singleton-observer pattern so it plays nicely with Livewire's SPA
// navigation (mountApp/initCounters re-runs on every livewire:navigated).
export function initCounters() {
  const targets = document.querySelectorAll('[data-counter]:not([data-counted])')
  if (targets.length === 0) return

  if (!observer) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return
          entry.target.setAttribute('data-counted', '')
          animateCount(entry.target)
          observer.unobserve(entry.target)
        })
      },
      { threshold: 0.4 },
    )
  }

  targets.forEach((el) => observer.observe(el))
}

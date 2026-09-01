let intervalId = null
let pointerSection = null
let pointerHandler = null
let motionQuery = null

function prefersReducedMotion() {
  return Boolean(motionQuery && motionQuery.matches)
}

function disposePointerParallax() {
  if (pointerSection && pointerHandler) {
    pointerSection.removeEventListener('pointermove', pointerHandler)
  }
  pointerSection = null
  pointerHandler = null
}

export function initHeroSlider() {
  if (intervalId) clearInterval(intervalId)
  disposePointerParallax()

  const section = document.getElementById('hero-slider')
  if (!section) return

  const slides = section.querySelectorAll('.hero-slide')
  const bgLayers = section.querySelectorAll('.hero-slide-bg')
  const contentLayers = section.querySelectorAll('.hero-slide-content')
  const dotsContainer = document.getElementById('hero-dots')
  const prevButton = document.getElementById('hero-prev')
  const nextButton = document.getElementById('hero-next')

  dotsContainer.innerHTML = ''

  let currentIndex = 0

  const dots = Array.from(slides).map((_, index) => {
    const dot = document.createElement('button')
    dot.type = 'button'
    dot.setAttribute('aria-label', `ស្លាយទី ${index + 1}`)
    dot.className = 'h-2.5 w-2.5 rounded-full transition-colors'
    dot.addEventListener('click', () => goToSlide(index))
    dotsContainer.appendChild(dot)
    return dot
  })

  // Background crossfade + dot state (instant) and the Ken Burns restart —
  // separated from applyContent() below so the very first paint can delay
  // just the text entrance by a frame without also delaying the background.
  function applyBackgroundAndDots() {
    slides.forEach((slide, index) => {
      slide.classList.toggle('opacity-100', index === currentIndex)
      slide.classList.toggle('opacity-0', index !== currentIndex)
    })
    dots.forEach((dot, index) => {
      dot.classList.toggle('bg-white', index === currentIndex)
      dot.classList.toggle('bg-white/50', index !== currentIndex)
    })
    bgLayers.forEach((bg, index) => {
      // Force a reflow before re-adding so the Ken Burns keyframe restarts
      // from 0% every activation instead of resuming mid-animation.
      bg.classList.remove('is-active')
      if (index === currentIndex) {
        void bg.offsetWidth
        bg.classList.add('is-active')
      }
    })
  }

  function applyContent() {
    contentLayers.forEach((content, index) => {
      content.classList.toggle('is-active', index === currentIndex)
    })
  }

  function render() {
    applyBackgroundAndDots()
    applyContent()
  }

  function goToSlide(index) {
    currentIndex = index
    render()
  }

  function goToPrev() {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length
    render()
  }

  function goToNext() {
    currentIndex = (currentIndex + 1) % slides.length
    render()
  }

  function startAutoplay() {
    intervalId = setInterval(goToNext, 5000)
  }

  prevButton.addEventListener('click', goToPrev)
  nextButton.addEventListener('click', goToNext)

  // Background/dots apply immediately (no fade-in needed — the first slide
  // is already correct in the server-rendered HTML). Content is delayed by
  // two animation frames so its opacity/translate transition actually plays
  // on first load instead of being coalesced into the initial style flush.
  applyBackgroundAndDots()
  requestAnimationFrame(() => requestAnimationFrame(applyContent))
  startAutoplay()

  // Cursor-driven depth parallax — mouse-only, skipped under reduced motion.
  // Writes normalized pointer position to CSS custom properties consumed by
  // the `translate` rules on .hero-slide-bg/.hero-slide-content in app.css.
  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  if (!prefersReducedMotion()) {
    pointerHandler = (event) => {
      if (event.pointerType !== 'mouse') return
      const rect = section.getBoundingClientRect()
      const px = (event.clientX - rect.left) / rect.width - 0.5
      const py = (event.clientY - rect.top) / rect.height - 0.5
      section.style.setProperty('--hero-px', px.toFixed(4))
      section.style.setProperty('--hero-py', py.toFixed(4))
    }
    section.addEventListener('pointermove', pointerHandler)
    pointerSection = section
  }
}

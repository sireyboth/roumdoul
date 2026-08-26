let intervalId = null

export function initHeroSlider() {
  if (intervalId) clearInterval(intervalId)

  const section = document.getElementById('hero-slider')
  if (!section) return

  const slides = section.querySelectorAll('.hero-slide')
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

  function render() {
    slides.forEach((slide, index) => {
      slide.classList.toggle('opacity-100', index === currentIndex)
      slide.classList.toggle('opacity-0', index !== currentIndex)
    })
    dots.forEach((dot, index) => {
      dot.classList.toggle('bg-white', index === currentIndex)
      dot.classList.toggle('bg-white/50', index !== currentIndex)
    })
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

  render()
  startAutoplay()
}

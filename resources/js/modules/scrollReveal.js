let observer = null

export function initScrollReveal() {
  const targets = document.querySelectorAll('[data-reveal]:not([data-revealed])')
  if (targets.length === 0) return

  if (!observer) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return
          entry.target.setAttribute('data-revealed', '')
          observer.unobserve(entry.target)
        })
      },
      { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
    )
  }

  targets.forEach((el, index) => {
    el.style.transitionDelay = `${Math.min(index % 6, 5) * 80}ms`
    observer.observe(el)
  })
}

import { companyPhone, telegramLink, companyPhones, socialLinks } from './config.js'

export function initFooter() {
  const phoneLink = document.getElementById('footer-phone-link')
  const phoneText = document.getElementById('footer-phone-text')
  const telegramChatLink = document.getElementById('footer-telegram-chat-link')
  const year = document.getElementById('footer-year')

  // Some pages (dashboard, invitation views) render without the site footer.
  if (phoneLink) phoneLink.href = telegramLink
  if (phoneText) phoneText.textContent = companyPhone
  if (telegramChatLink) telegramChatLink.href = telegramLink
  if (year) year.textContent = new Date().getFullYear()

  document.querySelectorAll('[data-social]').forEach((link) => {
    const url = socialLinks[link.getAttribute('data-social')]
    if (url) link.href = url
    else link.hidden = true // no profile yet: don't show an icon that goes nowhere
  })

  document.querySelectorAll('[data-footer-phone]').forEach((link, index) => {
    const number = companyPhones[index]
    if (!number) return
    link.href = `tel:${number.replace(/[^\d+]/g, '')}`
    link.textContent = number
  })
}
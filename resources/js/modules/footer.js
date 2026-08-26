import { companyEmail, companyPhone, gmailLink, telegramLink, companyPhones, socialLinks } from './config.js'

export function initFooter() {
  const emailLink = document.getElementById('footer-email-link')
  const emailText = document.getElementById('footer-email-text')
  const phoneLink = document.getElementById('footer-phone-link')
  const phoneText = document.getElementById('footer-phone-text')

  emailLink.href = gmailLink
  emailText.textContent = companyEmail
  phoneLink.href = telegramLink
  phoneText.textContent = companyPhone

  document.getElementById('footer-year').textContent = new Date().getFullYear()

  document.querySelectorAll('[data-social]').forEach((link) => {
    const url = socialLinks[link.getAttribute('data-social')]
    if (url) link.href = url
  })

  document.querySelectorAll('[data-footer-phone]').forEach((link, index) => {
    const number = companyPhones[index]
    if (!number) return
    link.href = `tel:${number.replace(/[^\d+]/g, '')}`
    link.textContent = number
  })
}
export const companyEmail = 'we dont have Email yet'
export const companyPhone = '+855 15 57 87 07'
export const gmailLink = `https://mail.google.com/mail/?view=cm&fs=1&to=${companyEmail}`
export const telegramLink = `https://t.me/${companyPhone.replace(/[^\d+]/g, '')}`

// Placeholder — replace with the real numbers.
export const companyPhones = ['+855 15 57 87 07 ', '+855 71 26 000 78']

// Social profiles in the site footer. An empty link hides that icon (keep in sync with config/roumdoul.php).
export const socialLinks = {
  facebook: 'https://www.facebook.com/roumdoulofficial',
  instagram: '',
  tiktok: 'https://www.tiktok.com/@roumdoul1',
  telegram: telegramLink,
}

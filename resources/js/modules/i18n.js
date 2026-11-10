// Interface strings for JS-only messages (toasts, upload errors, aria-labels). The layout
// fills window.__i18n from lang/{km,en}.json via <x-js-translations />, so JS uses the same
// dictionaries as Blade. Keys are the English source strings; `:name` placeholders are
// replaced from `replace`, just like Laravel's __().
export function t(key, replace = {}) {
  let text = (window.__i18n && window.__i18n[key]) || key
  for (const [name, value] of Object.entries(replace)) {
    text = text.split(`:${name}`).join(String(value))
  }
  return text
}

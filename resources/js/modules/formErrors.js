/*
 * Clear a field's red error message as soon as the customer edits that field.
 *
 * Forms use deferred `wire:model`, so Livewire only re-checks on submit. Without this, an old
 * message («The email has already been taken.») stays under the field after the customer has
 * already typed a different value, which reads as if the new value were wrong too. The server
 * still validates everything again on submit, so nothing is skipped.
 *
 * An error is the `.text-red-600` element(s) next to the input, inside the same wrapper —
 * the markup every form uses: `@error('x') <p class="mt-1 text-xs text-red-600 …">…</p> @enderror`.
 */
const isModelInput = (el) =>
  el instanceof Element && Array.from(el.attributes).some((a) => a.name.startsWith('wire:model'))

function clearErrorFor(input) {
  const wrapper = input.parentElement
  if (!wrapper) return
  wrapper.querySelectorAll(':scope > .text-red-600').forEach((p) => {
    p.hidden = true
  })
  input.removeAttribute('aria-invalid')
}

const onEdit = (e) => {
  if (isModelInput(e.target)) clearErrorFor(e.target)
}

document.addEventListener('input', onEdit, true)
document.addEventListener('change', onEdit, true)

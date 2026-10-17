// Tiny event-based toast notifications — shown by <ToastHost />.
// Usage: showToast('Saved.'), showToast('Something failed.', 'danger')

export function showToast(message, type = 'success') {
  window.dispatchEvent(new CustomEvent('adhub:toast', { detail: { message, type } }));
}

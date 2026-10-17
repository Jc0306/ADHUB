// Thin fetch wrapper for the PHP API.
// In dev, Vite proxies /api to Apache (see vite.config.js).

const BASE = '/api';

async function request(path, options = {}) {
  const res = await fetch(BASE + path, options);

  const data = await res.json().catch(() => ({}));

  // Expired session on a protected endpoint -> let AuthContext sign the user out.
  if (res.status === 401 && !path.startsWith('/auth')) {
    window.dispatchEvent(new Event('adhub:unauthorized'));
  }

  if (!res.ok) {
    throw new Error(data.error || `Request failed (${res.status})`);
  }

  return data;
}

export const apiGet = (path) => request(path);

export const apiPost = (path, body) =>
  request(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body ?? {}),
  });

/** Multipart upload — the browser sets the Content-Type boundary itself. */
export const apiUpload = (path, formData) =>
  request(path, { method: 'POST', body: formData });

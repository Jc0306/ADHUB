import { useEffect, useRef, useState } from 'react';

const TYPE_CLASSES = {
  success: 'text-bg-success',
  danger: 'text-bg-danger',
  warning: 'text-bg-warning',
  info: 'text-bg-info',
};

/** Renders toasts dispatched via showToast() from anywhere in the app. */
export default function ToastHost() {
  const [toasts, setToasts] = useState([]);
  const nextId = useRef(0);

  useEffect(() => {
    function onToast(e) {
      const id = (nextId.current += 1);
      setToasts((list) => [...list, { id, ...e.detail }]);

      setTimeout(() => {
        setToasts((list) => list.filter((t) => t.id !== id));
      }, 4200);
    }

    window.addEventListener('adhub:toast', onToast);
    return () => window.removeEventListener('adhub:toast', onToast);
  }, []);

  if (toasts.length === 0) {
    return null;
  }

  function dismiss(id) {
    setToasts((list) => list.filter((t) => t.id !== id));
  }

  return (
    <div
      className="toast-container position-fixed top-0 end-0 p-3"
      aria-live="polite"
      aria-atomic="true"
    >
      {toasts.map((t) => (
        <div
          key={t.id}
          role="alert"
          className={`toast show mb-2 ${TYPE_CLASSES[t.type] ?? 'text-bg-secondary'}`}
        >
          <div className="d-flex align-items-center">
            <div className="toast-body">{t.message}</div>
            <button
              type="button"
              className="btn-close btn-close-white me-2 ms-auto"
              aria-label="Close"
              onClick={() => dismiss(t.id)}
            />
          </div>
        </div>
      ))}
    </div>
  );
}

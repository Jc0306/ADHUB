import { useEffect, useRef } from 'react';

/**
 * usePoll — call `fn` every `ms` milliseconds while the tab is visible.
 * Stops on unmount or when `enabled` is false.
 */
export function usePoll(fn, ms, enabled = true) {
  const timerRef = useRef(null);
  const fnRef = useRef(fn);
  fnRef.current = fn;

  useEffect(() => {
    if (!enabled) return;

    const tick = () => {
      if (!document.hidden) fnRef.current();
    };

    tick();
    timerRef.current = setInterval(tick, ms);
    return () => clearInterval(timerRef.current);
  }, [ms, enabled]);
}
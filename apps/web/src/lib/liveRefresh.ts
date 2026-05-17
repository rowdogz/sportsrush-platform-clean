import { useEffect, useRef, useState } from "react";

export function useLiveRefresh(
  callback: () => void | Promise<void>,
  enabled: boolean,
  intervalMs = 30000,
): { readonly lastRefreshAt: string | null; readonly refreshNow: () => void } {
  const callbackRef = useRef(callback);
  const [lastRefreshAt, setLastRefreshAt] = useState<string | null>(null);

  callbackRef.current = callback;

  async function refreshNow(): Promise<void> {
    await callbackRef.current();
    setLastRefreshAt(new Date().toISOString());
  }

  useEffect(() => {
    if (!enabled) return undefined;
    const timer = window.setInterval(() => {
      void refreshNow();
    }, intervalMs);
    return () => window.clearInterval(timer);
  }, [enabled, intervalMs]);

  return { lastRefreshAt, refreshNow: () => void refreshNow() };
}
